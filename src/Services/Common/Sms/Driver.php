<?php

namespace Weboccult\EatcardCompanion\Services\Common\Sms;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Weboccult\EatcardCompanion\Enums\SmsTypes;
use Weboccult\EatcardCompanion\Enums\SystemTypes;
use Weboccult\EatcardCompanion\Models\AppSetting;
use Weboccult\EatcardCompanion\Models\SmsHistory;
use Weboccult\EatcardCompanion\Models\Store;
use Weboccult\EatcardCompanion\Models\StoreBillingCharges;
use Weboccult\EatcardCompanion\Models\UserWallet;
use function Weboccult\EatcardCompanion\Helpers\companionLogger;

/**
 * Class Driver.
 *
 * @author Darshit Hedpara
 */
abstract class Driver
{
    protected array $recipients = [];

    protected string $body = '';

    protected string $type = '';

    protected string $channel = '';

    protected ?Model $responsible = null;

    protected ?string $storeId = null;

    protected int $reTryCount = 0;

    /**
     * Driver constructor.
     *
     * @param array $settings
     */
    abstract public function __construct(array $settings);

    /**
     * @param $numbers
     *
     * @throws Exception
     *
     * @return Driver
     */
    public function to($numbers): self
    {
        $recipients = is_array($numbers) ? $numbers : [$numbers];
        $recipients = array_map(function ($item) {
            return trim($item);
        }, array_merge($this->recipients, $recipients));
        $this->recipients = array_values(array_filter($recipients));
        if (count($this->recipients) < 1) {
            throw new Exception('Message recipient could not be empty.');
        }

        return $this;
    }

    /**
     * @param $type
     *
     * @throws Exception
     *
     * @return Driver
     */
    public function type($type): self
    {
        if (trim($type) == '') {
            throw new Exception('Message Type could not be empty');
        }
        if (! is_string($type)) {
            throw new Exception('Message Type should be a string');
        }
        if (! SmsTypes::isValidName($type)) {
            throw new Exception('Message Type must be valid type of SmsTypes');
        }
        $this->type = $type;

        return $this;
    }

    /**
     * @param $channel
     *
     * @throws Exception
     *
     * @return Driver
     */
    public function channel($channel): self
    {
        if (trim($channel) == '') {
            throw new Exception('Message Channel could not be empty');
        }
        if (! is_string($channel)) {
            throw new Exception('Message Channel should be a string');
        }
        if (! SystemTypes::isValidName($channel)) {
            throw new Exception('Message Channel must be valid type of SystemTypes');
        }
        $this->channel = $channel;

        return $this;
    }

    /**
     * @param $model
     *
     * @return Driver
     */
    public function responsible($model): self
    {
        if (! empty($model) && $model instanceof Model) {
            $this->responsible = $model;
        }

        return $this;
    }

    /**
     * @param $store
     *
     * @throws Exception
     *
     * @return Driver
     */
    public function storeId($store): self
    {
        if (! empty($store) && $store instanceof Model) {
            $this->storeId = $store->id;
        } elseif (! empty($store)) {
            $this->storeId = $store;
        } else {
            throw new Exception('StoreId could not be empty.');
        }

        return $this;
    }

    /**
     * @param $reTryCount
     *
     * @return Driver
     */
    public function reTryCount($reTryCount): self
    {
        $this->reTryCount = $reTryCount;

        return $this;
    }

    /**
     * @param $message
     *
     * @throws Exception
     *
     * @return Driver
     */
    public function message($message): self
    {
        if (trim($message) == '') {
            throw new Exception('Message text could not be empty');
        }
        if (! is_string($message)) {
            throw new Exception('Message text should be a string.');
        }
        $this->body = $message;

        return $this;
    }

    /**
     * @param $response
     */
    public function keepHistory($response)
    {
        if ($response instanceof Collection) {
            $response->each(function ($value, $key) {
                $this->saveToDb($value['formatted_response'], $key);
            });
        }
        if (is_array($response)) {
            foreach ($response as $key => $value) {
                $this->saveToDb($value['formatted_response'], $key);
            }
        }
    }

    /**
     * @param $value
     * @param $key
     */
    public function saveToDb($value, $key)
    {
        try {
            $driverName = (new \ReflectionClass($this))->getShortName();
        } catch (\Exception $e) {
            $driverName = null;
        }
        $history = SmsHistory::create([
            'message'         => $this->body,
            'type'            => $this->type,
            'channel'         => $this->channel,
            'driver'          => (new \ReflectionClass($this))->getShortName(),
            'recipient'       => $key,
            'store_id'        => $this->storeId,
            'is_sent'         => $value['is_sent'],
            'is_error'        => $value['is_error'],
            'status'          => $value['is_error'] ? 'failed' : (($driverName == 'File') ? 'sent' : 'pending'),
            'sid'             => $value['sid'] ?? null,
            'failed_reason'   => $value['error_reason'] ?? null,
            'resend_required' => $value['resend_required'] ?? 0,
            'resend_count'    => $value['resend_count'] ?? 0,
        ]);
        try {
            $smsApp = AppSetting::query()->where('name', 'sms')->first();
            if ($smsApp && $value['is_sent']) {
                $store = Store::with('store_owner')->findOrFail($this->storeId);
                $storeOwner = $store->store_owner;
                $wallet = UserWallet::query()->where('user_id', $storeOwner->user_id)->first();
                if (! $wallet) {
                    $wallet = UserWallet::create(['user_id' => $storeOwner->user_id]);
                }
                $currentWalletBalance = $wallet->balance ? $wallet->balance : 0;
                StoreBillingCharges::create([
                    'store_id'              => $history->store_id,
                    'app_id'                => $smsApp->id,
                    'amount'                => $smsApp->price,
                    'current_wallet_amount' => $currentWalletBalance,
                ]);
                $currentWalletBalance = $wallet->balance - $smsApp->price;
                $wallet->update(['balance' => $currentWalletBalance]);
            }
        } catch (\Exception $e) {
            companionLogger('SMS StoreBillingCharges entry save failed', 'Error : '.$e->getMessage(), 'Line : '.$e->getLine(), 'File : '.$e->getFile(), 'IP address : '.request()->ip(), 'Browser : '.request()->header('User-Agent'));
        }
        if (! empty($this->responsible) && $this->responsible instanceof Model) {
            $history->responsible()->associate($this->responsible)->save();
        }
    }

    abstract public function send();
}
