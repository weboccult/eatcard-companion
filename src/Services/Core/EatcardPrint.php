<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Prints\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\extractRequestType;

class EatcardPrint
{
    protected BaseGenerator $generator;

    /**
     * @param string $printGenerator
     *
     * @return $this
     */
    public function generator(string $printGenerator): self
    {
        if (class_exists($printGenerator)) {
            $this->generator = new $printGenerator();
        } else {
            throw new ClassNotFoundException($printGenerator);
        }

        return $this;
    }

    /**
     * @param array $payload
     *
     * @return $this
     */
    public function payload(array $payload): self
    {
        //set generator based on payload request details for protocol print
        if (isset($payload['request_type']) && ! empty($payload['request_type'])) {
            $requestDetails = extractRequestType($payload['request_type']);
            self::generator($requestDetails['generator']);
            $this->generator->setPayloadRequestDetails($requestDetails);
            self::method($requestDetails['printMethod']);
            self::type($requestDetails['systemPrintType']);
            self::system($requestDetails['systemType']);
        }

        $this->generator->setPayload($payload);

        return $this;
    }

    /**
     * @param string $printMethod
     *
     * @return $this
     */
    public function method(string $printMethod): self
    {
        $this->generator->setPrintMethod(strtoupper($printMethod));

        return $this;
    }

//    /**
//     * @param string $orderType
//     *
//     * @return $this
//     */
//    public function order(string $orderType): self
//    {
//        $this->generator->setOrderType(strtoupper($orderType));
//
//        return $this;
//    }

    /**
     * @param string $printType
     *
     * @return $this
     */
    public function type(string $printType): self
    {
        $this->generator->setPrintType(strtoupper($printType));

        return $this;
    }

    /**
     * @param string $printType
     *
     * @return $this
     */
    public function system(string $systemType): self
    {
        $this->generator->setSystemType(strtoupper($systemType));

        return $this;
    }

    /**
     * @return array
     */
    public function toJson()
    {
        return $this->generator->dispatch();
    }
}
