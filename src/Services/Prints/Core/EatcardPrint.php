<?php

namespace Weboccult\EatcardCompanion\Services\Prints\Core;

use Exception;
use Weboccult\EatcardCompanion\Enums\PrintTypes;
use Weboccult\EatcardCompanion\Services\Prints\SqsPrint;

class EatcardPrint
{
    protected $printGenerator;

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
                $this->printGenerator = new SqsPrint();

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
