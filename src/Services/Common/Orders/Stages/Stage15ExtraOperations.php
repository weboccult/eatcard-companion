<?php

namespace Weboccult\EatcardCompanion\Services\Common\Orders\Stages;

use Spatie\Newsletter\NewsletterFacade as Newsletter;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * @description Stag 15
 *
 * @author Darshit Hedpara
 */
trait Stage15ExtraOperations
{
    protected function setNewLetterSubscriptionData()
    {
        if ($this->system === SystemTypes::TAKEAWAY) {
            if (isset($this->settings['news_letter']) && $this->settings['news_letter']['status'] === true) {
                try {
                    if ($this->store->mailchimp_api_key && $this->store->mailchimp_list_id) {
                        config()->set('newsletter.apiKey', $this->store->mailchimp_api_key);
                        config()->set('newsletter.lists.subscribers.id', $this->store->mailchimp_list_id);
                        /*check if particular email is subscribe or not*/
                        if (! Newsletter::isSubscribed($this->orderData['email'])) {
                            $newsletter = Newsletter::subscribe($this->orderData['email'], [
                                'FNAME' => $this->orderData['first_name'],
                                'LNAME' => $this->orderData['last_name'],
                                'PHONE' => $this->orderData['contact_no'],
                            ]);
                        }
                    }
                } catch (\Exception $exception) {
                    companionLogger('Newsletter Subscription error', 'Error - '.$exception->getMessage(), 'Line - '.$exception->getLine(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'), );
                }
            }
        }
    }

    protected function setKioskOrderAnswerChoiceLogic()
    {
    }
}
