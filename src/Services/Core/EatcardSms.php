<?php

namespace Weboccult\EatcardCompanion\Services\Core;

use Exception;
use ReflectionClass;
use ReflectionException;
use Throwable;
use Weboccult\EatcardCompanion\Services\Common\Sms\Builder;
use Weboccult\EatcardCompanion\Services\Common\Sms\Driver;

/**
 * @author Darshit Hedpara
 */
class EatcardSms
{
    protected array $config;

    protected array $settings;

    protected ?string $driver = null;

    protected ?Builder $builder = null;

    /**
     * @return string
     */
    public function test(): string
    {
        return 'It\'s working';
    }

    /**
     * Sms constructor.
     *
     * @param array $config
     *
     * @throws Throwable
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setBuilder(new Builder());
        $this->via($this->config['default']);
    }

    /**
     * @param $recipients
     *
     * @return EatcardSms
     */
    public function to($recipients): self
    {
        $this->builder->to($recipients);

        return $this;
    }

    /**
     * @param $type
     *
     * @return EatcardSms
     */
    public function type($type): self
    {
        $this->builder->type($type);

        return $this;
    }

    /**
     * @param $channel
     *
     * @return EatcardSms
     */
    public function channel($channel): self
    {
        $this->builder->channel($channel);

        return $this;
    }

    /**
     * @param $modelOrId
     *
     * @return EatcardSms
     */
    public function responsible($modelOrId): self
    {
        $this->builder->responsible($modelOrId);

        return $this;
    }

    /**
     * @param $storeId
     *
     * @return EatcardSms
     */
    public function storeId($storeId): self
    {
        $this->builder->storeId($storeId);

        return $this;
    }

    /**
     * @param $reTryCount
     *
     * @return EatcardSms
     */
    public function reTryCount($reTryCount): self
    {
        $this->builder->setReTryCount($reTryCount);

        return $this;
    }

    /**
     * @param $driver
     *
     * @throws Throwable
     *
     * @return EatcardSms
     */
    public function via($driver): self
    {
        $this->driver = $driver;
        $this->validateDriver();
        $this->builder->via($driver);
        $this->settings = $this->config['drivers'][$driver];

        return $this;
    }

    /**
     * @param $message
     *
     * @throws Exception
     * @throws Throwable
     *
     * @return EatcardSms
     */
    public function send($message): self
    {
        if ($message instanceof Builder) {
            return $this->setBuilder($message)->dispatch();
        }
        $this->builder->send($message);

        return $this;
    }

    /**
     * @throws Throwable
     *
     * @return mixed
     */
    public function dispatch()
    {
        $this->driver = $this->builder->getDriver() ?: $this->driver;
        if (empty($this->driver)) {
            $this->via($this->config['default']);
        }
        $driver = $this->getDriverInstance();
        $driver->message($this->builder->getBody());
        $driver->type($this->builder->getType());
        $driver->channel($this->builder->getChannel());
        $driver->responsible($this->builder->getResponsible());
        $driver->storeId($this->builder->getStoreId());
        $driver->to($this->builder->getRecipients());
        $driver->reTryCount($this->builder->getReTryCount());

        return $driver->send();
    }

    /**
     * @param Builder $builder
     *
     * @return EatcardSms
     */
    protected function setBuilder(Builder $builder): self
    {
        $this->builder = $builder;

        return $this;
    }

    /**
     * @throws Throwable
     *
     * @return mixed
     */
    protected function getDriverInstance()
    {
        $this->validateDriver();
        $class = $this->config['map'][$this->driver];

        return new $class($this->settings);
    }

    /**
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function validateDriver()
    {
        $conditions = [
            'Driver not selected or default driver does not exist.'      => empty($this->driver),
            'Driver not found in config file. Try updating the package.' => empty($this->config['drivers'][$this->driver]) || empty($this->config['map'][$this->driver]),
            'Driver source not found. Please update the package.'        => ! class_exists($this->config['map'][$this->driver]),
            'Driver must be an instance of Contracts\Driver.'            => ! (new ReflectionClass($this->config['map'][$this->driver]))->isSubclassOf(Driver::class),
        ];
        foreach ($conditions as $ex => $condition) {
            throw_if($condition, new Exception($ex));
        }
    }
}
