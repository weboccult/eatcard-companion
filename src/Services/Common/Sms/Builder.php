<?php

namespace Weboccult\EatcardCompanion\Services\Common\Sms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Exception;
use Throwable;
use Weboccult\EatcardCompanion\Models\Store;

/**
 * Class Builder.
 *
 * @author Darshit Hedpara
 */
class Builder
{
    protected array $recipients = [];

    protected string $body;

    protected string $type;

    protected string $channel;

    protected ?Model $responsible = null;

    protected ?string $storeId = null;

    protected int $reTryCount = 0;

    protected $driver = null;

    /**
     * @param $recipients
     *
     * @return Builder
     */
    public function to($recipients): self
    {
        $this->recipients = Arr::wrap($recipients);

        return $this;
    }

    /**
     * @param $type
     *
     * @return Builder
     */
    public function type($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param $channel
     *
     * @return Builder
     */
    public function channel($channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * @param $model
     *
     * @return Builder
     */
    public function responsible($model): self
    {
        if ($model instanceof Model) {
            $this->responsible = $model;
        }

        return $this;
    }

    /**
     * @return Model|null
     */
    public function getResponsible(): ?Model
    {
        return $this->responsible;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param $store
     *
     * @return Builder
     */
    public function storeId($store): self
    {
        if ($store instanceof Store) {
            $this->storeId = $store->id;
        } else {
            $this->storeId = $store;
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param $reTryCount
     *
     * @return Builder
     */
    public function setReTryCount($reTryCount): self
    {
        $this->reTryCount = $reTryCount;

        return $this;
    }

    /**
     * @return int
     */
    public function getReTryCount(): int
    {
        return $this->reTryCount;
    }

    /**
     * @param $body
     *
     * @return Builder
     */
    public function send($body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @param $driver
     *
     * @return Builder
     */
    public function via($driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @return array
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    /**
     * @return null
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @throws Throwable
     */
    public function validate()
    {
        $conditions = [
            'Invalid data for sms notification.'    => ! is_a($this, self::class),
            'Message body could not be empty.'      => empty($this->body),
            'StoreId could not be empty.'           => empty($this->storeId),
            'Message type could not be empty.'      => empty($this->type),
            'Message channel could not be empty.'   => empty($this->channel),
            'Message recipient could not be empty.' => empty($this->recipients),
        ];
        foreach ($conditions as $ex => $condition) {
            throw_if($condition, new Exception($ex));
        }
    }
}
