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
    function reverseRouteGenerator($path, $parameters, $queryParam, $system): string
    {
        $domain = config('eatcardCompanion.system_endpoints.'.strtolower($system));
        $preparedUrl = preg_replace_callback('/<%(.*?)%>/', function ($preg) use ($parameters) {
            return $parameters[$preg[1]] ?? $preg[0];
        }, config('eatcardCompanion.'.$path));

        return $domain.$preparedUrl.buildQueryParams($queryParam);
    }

    /**
     * @param $params
     *
     * @return string
     *
     * @author Darshit Hedpara
     */
    function buildQueryParams($params): string
    {
        if (! empty($params)) {
            $paramsJoined = [];
            foreach ($params as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }

            return '?'.implode('&', $paramsJoined);
        } else {
            return '';
        }
    }
}
