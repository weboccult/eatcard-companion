<?php

namespace Weboccult\EatcardCompanion\Helpers;

if (! function_exists('reverseRouteGenerator')) {
    /**
     * @param string $path
     * @param ?array $parameters
     * @param ?array $queryParam
     * @param ?string $system
     * @param ?bool $withoutDomain
     *
     * @return string
     *
     * @author Darshit Hedpara
     */
    function reverseRouteGenerator(string $path, ?array $parameters, ?array $queryParam = [], string $system = null, ?bool $withoutDomain = false): string
    {
        $preparedUrl = preg_replace_callback('/<%(.*?)%>/', function ($preg) use ($parameters) {
            return $parameters[$preg[1]] ?? $preg[0];
        }, config('eatcardCompanion.'.$path));

        if ($withoutDomain) {
            return $preparedUrl.buildQueryParams($queryParam);
        } else {
            $domain = config('eatcardCompanion.system_endpoints.'.strtolower($system));

            return $domain.$preparedUrl.buildQueryParams($queryParam);
        }
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
