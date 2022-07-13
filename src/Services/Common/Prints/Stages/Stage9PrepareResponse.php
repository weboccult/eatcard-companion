<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Weboccult\EatcardCompanion\Enums\OrderTypes;
use Weboccult\EatcardCompanion\Enums\PrintMethod;
use function Weboccult\EatcardCompanion\Helpers\__companionPDF;
use function Weboccult\EatcardCompanion\Helpers\__companionViews;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 9
 *
 * @author Darshit Hedpara
 */
trait Stage9PrepareResponse
{
    /**
     * @return array|void
     */
    protected function jsonResponce()
    {
        if ($this->printMethod == PrintMethod::PROTOCOL || $this->printMethod == PrintMethod::SQS) {
            companionLogger('----Companion Print Final print json : ', $this->jsonFormatFullReceipt);
            $this->returnResponseData = $this->jsonFormatFullReceipt;
        }
    }

    /**
     * @return \Illuminate\Contracts\View\View|void
     */
    protected function htmlResponse()
    {
        $orderDetails = [];
        if ($this->orderType == OrderTypes::RUNNING) {
            $orderDetails = $this->reservation;
        }

        if ($this->orderType == OrderTypes::PAID) {
            $orderDetails = $this->order;
        }

        if ($this->printMethod == PrintMethod::HTML && ! empty($this->jsonFormatFullReceipt)) {
            $this->returnResponseData = __companionViews($this->advanceData['viewPath'], ['data'=>$this->jsonFormatFullReceipt, 'order' => $orderDetails, 'store'=> $this->store, 'kiosk'=>$this->kiosk]);
        }
    }

    /**
     * @return \Illuminate\Http\Response|void
     */
    protected function pdfResponse()
    {
        $orderDetails = [];
        if ($this->orderType == OrderTypes::RUNNING) {
            $orderDetails = $this->reservation;
        }

        if ($this->orderType == OrderTypes::PAID) {
            $orderDetails = $this->order;
        }
        if ($this->printMethod == PrintMethod::PDF && ! empty($this->jsonFormatFullReceipt)) {
            $this->returnResponseData = __companionPDF($this->advanceData['viewPath'], ['data'=>$this->jsonFormatFullReceipt, 'order' => $orderDetails, 'store'=> $this->store, 'kiosk'=>$this->kiosk])
                   ->download('orderno-'.$this->globalOrderId.'.pdf');
        }
    }
}
