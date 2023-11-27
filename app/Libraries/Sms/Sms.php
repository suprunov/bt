<?php

namespace App\Libraries\Sms;

use App\Libraries\Sms\Providers\SmsCenter;

class Sms
{
    /**
     * The SMS provider alias to use for sms request.
     */
    protected ?string $alias = null;

    protected array $providers = [ // TODO move to config
        'smsCenter' => SmsCenter::class,
    ];

    protected string $defaultProvider = 'smsCenter'; // TODO move to config

    /**
     * Instantiated SMS provider objects,
     * stored by SMS provider alias.
     *
     * @var array<string, SmsInterface> [SMS_provider_alias => SMS_provider_instance]
     */
    protected array $instances = [];

    /**
     * Sets the SMS provider alias that should be used for sms request.
     *
     * @return $this
     */
    public function setProvider(?string $alias = null): self
    {
        if (! empty($alias)) {
            $this->alias = $alias;
        }

        return $this;
    }

    /**
     * Provide magic function-access to SMS provider
     *
     * @param string[] $args
     */
    public function __call(string $method, array $args)
    {
        $provider = $this->factory($this->alias);

        if (method_exists($provider, $method)) {
            return $provider->{$method}(...$args);
        }
    }

    /**
     * Returns an instance of the specified Sms provider.
     *
     * You can pass 'null' as the Sms provider and it
     * will return an instance of default Sms provider specified
     * in the Sms config file.
     *
     * @param string|null $alias Sms provider alias
     *
     * @throws \Exception
     */
    public function factory(?string $alias = null): SmsInterface
    {
        // Determine actual SMS Provider alias
        $alias ??= $this->defaultProvider;

        // Return the cached instance
        if (! empty($this->instances[$alias])) {
            return $this->instances[$alias];
        }

        // Otherwise, try to create a new instance.
        if (! array_key_exists($alias, $this->providers)) {
            throw new \Exception('Should not happen'); // TODO throw normal exception
        }

        $className = $this->providers[$alias];

        $this->instances[$alias] = new $className();

        return $this->instances[$alias];
    }
}