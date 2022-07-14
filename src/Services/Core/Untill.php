<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Weboccult\EatcardCompanion\Exceptions\StoreEmptyException;
use Weboccult\EatcardCompanion\Exceptions\TableEmptyException;
use Weboccult\EatcardCompanion\Exceptions\UntillSettingNotFoundException;
use Weboccult\EatcardCompanion\Models\Store;
use Illuminate\Support\Facades\File;
use Weboccult\EatcardCompanion\Models\Table;
use Weboccult\EatcardCompanion\Services\Common\Untill\Requests\CloseOrder;
use Weboccult\EatcardCompanion\Services\Common\Untill\Requests\CreateOrder;
use Weboccult\EatcardCompanion\Services\Common\Untill\Requests\GetActiveTableInfo;
use Weboccult\EatcardCompanion\Services\Common\Untill\Requests\GetPaymentsInfo;
use Weboccult\EatcardCompanion\Services\Common\Untill\Requests\GetTableItemsInfoRequest;
use Weboccult\EatcardCompanion\Services\Common\Untill\Response\PropertyAccessor;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @author Darshit Hedpara
 */
class Untill
{
    use GetTableItemsInfoRequest;
    use GetActiveTableInfo;
    use CreateOrder;
    use GetPaymentsInfo;
    use CloseOrder;
    use PropertyAccessor;

    /** @var Store|Model|null */
    private $store = null;

    /** @var Table|Model|null */
    private $table = null;

    private string $xmlData = '';

    /**
     * @param Store|Model $store
     *
     * @return Untill
     */
    public function store(Model $store): self
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @param Table|Model $table
     *
     * @return Untill
     */
    public function table(Model $table): self
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param string $template
     * @param array $parameters
     *
     * @return Untill
     */
    public function build(string $template, array $parameters = []): self
    {
        $xmlData = $this->getTemplateXML($template);
        $this->xmlData = $this->replacer($xmlData, $parameters);
        companionLogger('Template name : ', $template, ' | Payment info xmlData : ', $this->xmlData, ' | Parameters : ', $parameters);

        return $this;
    }

    /**
     * @return Untill
     */
    public function setCredentials(): self
    {
        if (empty($this->store)) {
            throw new StoreEmptyException();
        }
        if (empty($this->store->untillSetting)) {
            throw new UntillSettingNotFoundException();
        }
        $parameters = [
            'USER_NAME' => $this->store->untillSetting->untill_username,
            'PASSWORD' => $this->store->untillSetting->untill_password,
            'APP_TOKEN' => config('eatcardCompanion.untill.app_token'),
            'APP_NAME' => config('eatcardCompanion.untill.app_name'),
        ];
        companionLogger('1. Set utill credentials : ', $parameters, ' | XML data : ', $this->xmlData);
        $this->xmlData = $this->replacer($this->xmlData, $parameters);
        companionLogger('2. Set utill credentials : ', $parameters, ' | XML data : ', $this->xmlData);

        return $this;
    }

    /**
     * @return Untill
     */
    public function setTableNumber(): self
    {
        if (empty($this->table)) {
            throw new TableEmptyException();
        }
        $parameters = [
            'TABLE_NUMBER' => $this->table->name,
        ];
        $this->xmlData = $this->replacer($this->xmlData, $parameters);

        return $this;
    }

    /**
     * @return array|bool|mixed
     */
    public function dispatch()
    {
        if (empty($this->store)) {
            throw new StoreEmptyException();
        }
        if (empty($this->store->untillSetting)) {
            throw new UntillSettingNotFoundException();
        }
        if (empty($this->table)) {
            throw new TableEmptyException();
        }

        return $this->fireAPI();
    }

    /**
     * @return false|mixed
     */
    private function fireAPI()
    {
        try {
            companionLogger('Untill API payload', ['xmlData' => $this->xmlData], 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));

            $headers = [
                'Content-type: text/xml;charset=utf-8',
                'Accept: text/xml',
                'Cache-Control: no-cache',
                'Pragma: no-cache',
                'Content-length: '.strlen($this->xmlData),
            ];
            $soap_request = curl_init();
            curl_setopt($soap_request, CURLOPT_URL, $this->store->untillSetting->untill_host_name.':3063/soap/ITPAPIPOS');
            curl_setopt($soap_request, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($soap_request, CURLOPT_POST, true);
            // 		curl_setopt($soap_request, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap_request, CURLOPT_POSTFIELDS, $this->xmlData);
            curl_setopt($soap_request, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($soap_request, CURLOPT_TIMEOUT, 10000); //timeout in seconds
            $soapResponse = curl_exec($soap_request);
            // Check the return value of curl_exec(), too
            if ($soapResponse == false) {
                throw new Exception(curl_error($soap_request), curl_errno($soap_request));
            }
            curl_close($soap_request);
            /*convert xml to json*/
            $plainXML = $this->parseXMLData(trim($soapResponse));

            return json_decode(json_encode(simplexml_load_string($plainXML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        } catch (\Exception $e) {
            companionLogger('Untill API call error', '#Error : '.$e->getMessage(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));

            return false;
        }
    }

    /**
     * @param string $xml
     *
     * @return string|string[]|null
     */
    private function parseXMLData(string $xml)
    {
        $obj = simplexml_load_string($xml);
        if ($obj === false) {
            return $xml;
        }
        // GET NAMESPACES, IF ANY
        $nss = $obj->getNamespaces(true);
        if (empty($nss)) {
            return $xml;
        }
        // CHANGE ns: INTO ns_
        $nsm = array_keys($nss);
        foreach ($nsm as $key) {
            // A REGULAR EXPRESSION TO MUNG THE XML
            $rgx = '#'               // REGEX DELIMITER
                .'('               // GROUP PATTERN 1
                .'\<'              // LOCATE A LEFT WICKET
                .'/?'              // MAYBE FOLLOWED BY A SLASH
                .preg_quote($key)  // THE NAMESPACE
                .')'               // END GROUP PATTERN
                .'('               // GROUP PATTERN 2
                .':{1}'            // A COLON (EXACTLY ONE)
                .')'               // END GROUP PATTERN
                .'#'               // REGEX DELIMITER
;
            // INSERT THE UNDERSCORE INTO THE TAG NAME
            $rep = '$1'          // BACKREFERENCE TO GROUP 1
                .'_'           // LITERAL UNDERSCORE IN PLACE OF GROUP 2
;
            // PERFORM THE REPLACEMENT
            $xml = preg_replace($rgx, $rep, $xml);
        }

        return $xml;
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    public function getTemplateXML(string $templateName): string
    {
        return File::get(__DIR__.'/../Common/Untill/XMLRequests/'.$templateName);
    }

    /**
     * @param string $xmlData
     * @param array $parameters
     *
     * @return string
     */
    public function replacer(string $xmlData, array $parameters): string
    {
        return preg_replace_callback('/@(.*?)@/', function ($preg) use ($parameters) {
            return $parameters[$preg[1]] ?? $preg[0];
        }, $xmlData);
    }
}
