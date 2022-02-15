<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Weboccult\EatcardCompanion\Enums\PrintMethod;
use Weboccult\EatcardCompanion\Exceptions\ClassNotFoundException;
use Weboccult\EatcardCompanion\Services\Common\Revenue\BaseGenerator;
use function Weboccult\EatcardCompanion\Helpers\extractRevenuePayload;

class EatcardRevenue
{
    protected BaseGenerator $generator;

    /**
     * @param string $revenueGenerator
     *
     * @return $this
     */
    public function generator(string $revenueGenerator): self
    {
        if (class_exists($revenueGenerator)) {
            $this->generator = new $revenueGenerator();
        } else {
            throw new ClassNotFoundException($revenueGenerator);
        }

        return $this;
    }

    /**
     * @param string $revenueMethod
     *
     * @return $this
     */
    public function method(string $revenueMethod): self
    {
        $this->generator->setRevenueMethod(strtoupper($revenueMethod));

        return $this;
    }

    /**
     * @param array $payload
     *
     * @return $this
     */
    public function payload(array $payload): self
    {
        $payload = extractRevenuePayload($payload);
//        dd($payload);
        //set generate  or based on payload request details for protocol print
        if (isset($payload['requestType']) && ! empty($payload['requestType'])) {
            self::generator($payload['generator']);
            self::date($payload['date']);
            self::month($payload['month']);
            self::year($payload['year']);
            self::method(PrintMethod::PROTOCOL);
        }

        $this->generator->setStoreId($payload['storeId'] ?? '0');
        $this->generator->setPayload($payload);

        return $this;
    }

    /**
     * @param string $date
     *
     * @return $this
     */
    public function date(string $date): self
    {
        $this->generator->setDate($date);

        return $this;
    }

    /**
     * @param string $month
     *
     * @return $this
     */
    public function month(string $month): self
    {
        $this->generator->setMonth($month);

        return $this;
    }

    /**
     * @param string $year
     *
     * @return $this
     */
    public function year(string $year): self
    {
        $this->generator->setYear($year);

        return $this;
    }

    /**
     * @throws \Throwable
     *
     * @return array|void
     */
    public function generate()
    {
        return $this->generator->dispatch();
    }
}
