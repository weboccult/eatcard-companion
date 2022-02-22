<?php

namespace Weboccult\EatcardCompanion\Services\Common\Untill\Response;

use Weboccult\EatcardCompanion\Services\Core\Untill;

/**
 * @description Prepare Close Order API
 * @mixin Untill
 * @author Darshit Hedpara
 */
trait PropertyAccessor
{
    public static function getReturnCode(string $requestName, array $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.ReturnCode");
    }

    public static function getReturnMessage(string $requestName, array $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.ReturnMessage");
    }

    public static function getOutput(string $requestName, $responsePath, $response)
    {
        return data_get($response, "SOAP-ENV_Body.NS1_{$requestName}Response.return.{$responsePath}");
    }
}
