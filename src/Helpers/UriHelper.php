<?php

namespace Weboccult\EatcardCompanion\Helpers;

if (! function_exists('webhookGenerator')) {
    /**
     * @param string $path
     * @param array|null $parameters
     * @param array|null $queryParam
     * @param string|null $system
     *
     * @return string
     */
    function webhookGenerator(string $path, ?array $parameters, ?array $queryParam = [], string $system = null): string
    {
        $preparedUrl = reverseRouteGenerator($path, $parameters, $queryParam);
        $domain = config('eatcardCompanion.system_endpoints.'.strtolower($system));
        $isWebhookExposed = config('eatcardCompanion.exposed_webhook.settings.enable_exposed_webhook');
        if ($isWebhookExposed) {
            $domain = config('eatcardCompanion.exposed_webhook.'.strtolower($system));
        }

        return $domain.$preparedUrl;
    }
}

if (! function_exists('generalUrlGenerator')) {
    /**
     * @param string $path
     * @param array|null $parameters
     * @param array|null $queryParam
     * @param string|null $system
     *
     * @return string
     */
    function generalUrlGenerator(string $path, ?array $parameters, ?array $queryParam = [], string $system = null): string
    {
        $preparedUrl = reverseRouteGenerator($path, $parameters, $queryParam);
        $domain = config('eatcardCompanion.system_endpoints.'.strtolower($system));

        return $domain.$preparedUrl;
    }
}

if (! function_exists('reverseRouteGenerator')) {
    /**
     * @param string $path
     * @param ?array $parameters
     * @param ?array $queryParam
     *
     * @return string
     *
     * @author Darshit Hedpara
     */
    function reverseRouteGenerator(string $path, ?array $parameters, ?array $queryParam = []): string
    {
        $preparedUrl = preg_replace_callback('/<%(.*?)%>/', function ($preg) use ($parameters) {
            return $parameters[$preg[1]] ?? $preg[0];
        }, config('eatcardCompanion.'.$path));

        return $preparedUrl.buildQueryParams($queryParam);
    }
}

if (! function_exists('buildQueryParams')) {
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
