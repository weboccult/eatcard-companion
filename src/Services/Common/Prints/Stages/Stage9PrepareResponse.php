<?php

namespace Weboccult\EatcardCompanion\Services\Common\Prints\Stages;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
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
            $this->returnResponseData = $this->jsonFormatFullReceipt;
        }

    }

    /**
     * @return \Illuminate\Contracts\View\View|void
     */
    protected function htmlResponse()
    {
        if ($this->printMethod == PrintMethod::HTML && ! empty($this->jsonFormatFullReceipt)) {
            $this->returnResponseData = __companionViews($this->advanceData['viewPath'], ['data'=>$this->jsonFormatFullReceipt, 'order' => $this->order, 'store'=> $this->store, 'kiosk'=>$this->kiosk]);
        }

    }

    /**
     * @return \Illuminate\Http\Response|void
     */
    protected function pdfResponse()
    {
        if ($this->printMethod == PrintMethod::PDF && ! empty($data)) {
            $this->returnResponseData = __companionPDF($this->advanceData['viewPath'], ['data'=>$data, 'order' => $this->order, 'store'=> $this->store, 'kiosk'=>$this->kiosk])
                   ->download('orderno-'.$this->globalOrderId.'.pdf');
        }
    }

}
