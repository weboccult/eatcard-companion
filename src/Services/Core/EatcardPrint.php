<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Services\Common\Prints\BasePrint;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\ProtocolGenerator;
use Weboccult\EatcardCompanion\Services\Common\Prints\Generators\SqsGenerator;

class EatcardPrint
{
    protected BasePrint $printGenerator;

    /**
     * @param string $printType
     *
     * @throws Exception
     *
     * @return EatcardPrint
     */
    public function via(string $printType): self
    {
        switch (strtoupper($printType)) {
            case PrintTypes::SQS:
                $this->printGenerator = new SqsGenerator();

                return $this;
            case PrintTypes::PROTOCOL:
                $this->printGenerator = new ProtocolGenerator();

                return $this;
            default:
                throw new Exception($printType.' - Print type not supported yet.!', 422);
        }
    }

    /**
     * @return mixed|array
     */
    public function toJson()
    {
        return $this->printGenerator->dispatch();
    }
}
