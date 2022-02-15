<?php

namespace Weboccult\EatcardCompanion\Services\Common\Sms\Drivers;

use Exception;
use Illuminate\Support\Collection;
use Twilio\Rest\Client;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\RestException;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\BillingInformation;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\UserWallet;
use Weboccult\EatcardCompanion\Services\Common\Sms\Driver;
use function Weboccult\EatcardCompanion\Helpers\webhookGenerator;

/**
 * Driver Twilio.
 *
 * @author Darshit Hedpara
 */
class Twilio extends Driver
{
    protected array $settings;

    protected ?Client $client;

    /**
     * Twilio constructor.
     *
     * @param array $settings
     *
     * @throws ConfigurationException
     */
    public function __construct(array $settings)
    {
        $this->settings = $settings;
        $this->client = new Client(data_get($this->settings, 'sid'), data_get($this->settings, 'token'));
    }

    /**
     * @throws Exception
     *
     * @return Collection|mixed
     */
    public function send()
    {
        $response = collect();
        foreach ($this->recipients as $recipient) {
            /**
             * @psalm-suppress UndefinedMagicPropertyFetch
             */
            $resend_required = false;
            try {
                $from = data_get($this->settings, 'from');
                // For indian numbers
                $checkIndian = (strpos($recipient, '+91') > -1);
                if ($checkIndian) {
                    $from = data_get($this->settings, 'indian_from');
                }
                if ($this->validateMessage($this->body) == -1) {
                    throw new Exception('Message text should be valid GSM encoded string', 400);
                }
                if ($this->checkBalance() == false) {
                    $resend_required = true;
                    throw new Exception('Insufficient balance in wallet', 400);
                }
                $result = $this->client->account->messages->create($recipient, [
                        'from'           => $from,
                        'body'           => $this->body,
                        'statusCallback' => webhookGenerator('sms.webhook.admin', [], [], SystemTypes::ADMIN),
                    ]);
                $result = $result->toArray();
                $result['error'] = false;
                $result['error_reason'] = '';
            } catch (RestException $exception) {
                $result['error'] = true;
                $result['error_reason'] = $exception->getMessage();
                $result['code'] = $exception->getCode();
                $result['statusCode'] = $exception->getStatusCode();
            } catch (Exception $exception) {
                $result['error'] = true;
                $result['error_reason'] = $exception->getMessage();
                $result['code'] = $exception->getCode();
                $result['statusCode'] = 400;
                if ($resend_required) {
                    $store = Store::findOrFail($this->storeId);
                    $storeOwner = $store->store_owner;
                    $billingInfo = BillingInformation::query()->where('user_id', $storeOwner->user_id)->first();
                    if ($billingInfo && $billingInfo->auto_recharge_enable == 1) {
                        $result['resend_required'] = 1;
                        $result['resend_count'] = $this->reTryCount;
                    }
                }
            }
            $currentData['actual_response'] = $result;
            $currentData['formatted_response'] = [
                'sid'             => $result['sid'] ?? null,
                'status'          => $result['error'] ? 'error' : 'success',
                'is_sent'         => $result['error'] ? 0 : 1,
                'is_error'        => $result['error'] ? 1 : 0,
                'error_reason'    => $result['error_reason'],
                'resend_required' => $result['resend_required'] ?? 0,
                'resend_count'    => $result['resend_count'] ?? 0,
            ];
            $response->put($recipient, $currentData);
        }
        $this->keepHistory($response);

        return (count($this->recipients) == 1) ? $response->first() : $response;
    }

    /**
     * @param $message
     *
     * @return int
     */
    public function validateMessage($message): int
    {
        // Basic GSM character set (one 7-bit encoded char each)
        $gsm_7bit_basic = "@£¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
        // Extended set (requires escape code before character thus 2x7-bit encodings per)
        $gsm_7bit_extended = '^{}\\[~]|€';
        $len = 0;
        for ($i = 0; $i < mb_strlen($message); $i++) {
            $c = mb_substr($message, $i, 1);
            if (mb_strpos($gsm_7bit_basic, $c) !== false) {
                $len++;
            } elseif (mb_strpos($gsm_7bit_extended, $c) !== false) {
                $len += 2;
            } else {
                return -1; // cannot be encoded as GSM, immediately return -1
            }
        }

        return $len;
    }

    /**
     * @return bool
     */
    public function checkBalance(): bool
    {
        try {
            $store = Store::findOrFail($this->storeId);
            $storeOwner = $store->store_owner;
            $wallet = UserWallet::query()->where('user_id', $storeOwner->user_id)->first();
            if (! $wallet) {
                $wallet = UserWallet::query()->create(['user_id' => $storeOwner->user_id]);
            }
            if ($wallet->balance <= 0.5) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
