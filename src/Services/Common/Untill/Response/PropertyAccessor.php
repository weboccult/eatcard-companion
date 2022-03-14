<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Response;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Close Order API
 * @mixin Untill
 *
 * @author Darshit Hedpara
 */
trait PropertyAccessor
{
    /**
     * @param string $requestName
     * @param array $response
     *
     * @return array|mixed
     */
    public static function getReturnCode(string $requestName, array $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.ReturnCode");
    }

    /**
     * @param string $requestName
     * @param array $response
     *
     * @return array|mixed
     */
    public static function getReturnMessage(string $requestName, array $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.ReturnMessage");
    }

    /**
     * @param array $response
     *
     * @return array|mixed
     */
    public static function getFaultCode(array $response)
    {
        return data_get($response, 'SOAP-ENV_Body.SOAP-ENV_Fault.faultcode');
    }

    /**
     * @param string $requestName
     * @param $responsePath
     * @param $response
     *
     * @return array|mixed
     */
    public static function getOutput(string $requestName, $responsePath, $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.{$responsePath}");
    }
}
