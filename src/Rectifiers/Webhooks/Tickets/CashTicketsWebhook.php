<?php

namespace Weboccult\EatcardCompanion\Rectifiers\Webhooks\Tickets;

use Carbon\Carbon;
use Exception;
use Weboccult\EatcardCompanion\Rectifiers\Webhooks\BaseWebhook;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

class CashTicketsWebhook extends BaseWebhook
{
    use TicketsWebhookCommonActions;

    /**
     * @throws Exception
     *
     * @return mixed
     */
    public function handle()
    {
        companionLogger('Cash Tickets webhook request started', 'ReservationId #'.$this->reservationId, 'PaymentId #'.$this->paymentId, 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));
        companionLogger('Cash Tickets payload', json_encode(['payload' => $this->payload], JSON_PRETTY_PRINT), 'IP address : '.request()->ip(), 'browser : '.request()->header('User-Agent'));

        $this->fetchAndSetStore();
        $this->fetchAndSetReservation();
        $this->fetchAndSePaymentDetails();

        $update_data = [];
        $update_payment_data = [];

        $update_data['payment_status'] = $update_payment_data['payment_status'] = 'pending';
        $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'pending';
        if ($this->payload['status'] == 'paid') {
            $update_data['payment_status'] = $update_payment_data['payment_status'] = 'paid';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'paid';
            $update_data['paid_on'] = $update_payment_data['paid_on'] = Carbon::now()->format('Y-m-d H:i:s');
            $update_payment_data['transaction_receipt'] = 'fake-bop payment';
        } elseif ($this->payload['status'] == 'failed') {
            $update_data['payment_status'] = $update_payment_data['payment_status'] = 'failed';
            $update_data['local_payment_status'] = $update_payment_data['local_payment_status'] = 'failed';
        } else {
            companionLogger('invalid cash payment status', $this->payload);
        }

        $this->afterStatusGetProcess($update_data, $update_payment_data);

        return true;
    }
}
