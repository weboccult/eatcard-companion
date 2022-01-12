<?php

namespace Weboccult\EatcardCompanion\Helpers;

if (! function_exists('reverseRouteGenerator')) {
    /**
     * @param $path
     * @param $parameters
     * @param $system
     *
     * @return string
     *
     * @author Darshit Hedpara
     */
    function reverseRouteGenerator($path, $parameters, $system): string
    {
        $domain = config('eatcardCompanion.system_endpoints.'.strtolower($system));
        $preparedUrl = preg_replace_callback('/<%(.*?)%>/', function ($preg) use ($parameters) {
            return $parameters[$preg[1]] ?? $preg[0];
        }, config('eatcardCompanion.'.$path));

        return $domain.$preparedUrl;
    }
}
