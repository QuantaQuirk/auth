<?php

namespace QuantaQuirk\Auth\Passwords;

use QuantaQuirk\Contracts\Auth\PasswordBrokerFactory as FactoryContract;
use InvalidArgumentException;

/**
 * @mixin \QuantaQuirk\Contracts\Auth\PasswordBroker
 */
class PasswordBrokerManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \QuantaQuirk\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of created "drivers".
     *
     * @var array
     */
    protected $brokers = [];

    /**
     * Create a new PasswordBroker manager instance.
     *
     * @param  \QuantaQuirk\Contracts\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to get the broker from the local cache.
     *
     * @param  string|null  $name
     * @return \QuantaQuirk\Contracts\Auth\PasswordBroker
     */
    public function broker($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->brokers[$name] ?? ($this->brokers[$name] = $this->resolve($name));
    }

    /**
     * Resolve the given broker.
     *
     * @param  string  $name
     * @return \QuantaQuirk\Contracts\Auth\PasswordBroker
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Password resetter [{$name}] is not defined.");
        }

        // The password broker uses a token repository to validate tokens and send user
        // password e-mails, as well as validating that password reset process as an
        // aggregate service of sorts providing a convenient interface for resets.
        return new PasswordBroker(
            $this->createTokenRepository($config),
            $this->app['auth']->createUserProvider($config['provider'] ?? null)
        );
    }

    /**
     * Create a token repository instance based on the given configuration.
     *
     * @param  array  $config
     * @return \QuantaQuirk\Auth\Passwords\TokenRepositoryInterface
     */
    protected function createTokenRepository(array $config)
    {
        $key = $this->app['config']['app.key'];

        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $connection = $config['connection'] ?? null;

        return new DatabaseTokenRepository(
            $this->app['db']->connection($connection),
            $this->app['hash'],
            $config['table'],
            $key,
            $config['expire'],
            $config['throttle'] ?? 0
        );
    }

    /**
     * Get the password broker configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig($name)
    {
        return $this->app['config']["auth.passwords.{$name}"];
    }

    /**
     * Get the default password broker name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['auth.defaults.passwords'];
    }

    /**
     * Set the default password broker name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver($name)
    {
        $this->app['config']['auth.defaults.passwords'] = $name;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->broker()->{$method}(...$parameters);
    }
}
