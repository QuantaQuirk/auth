<?php

namespace QuantaQuirk\Auth\Middleware;

use Closure;
use QuantaQuirk\Auth\AuthenticationException;
use QuantaQuirk\Contracts\Auth\Factory as Auth;
use QuantaQuirk\Contracts\Auth\Middleware\AuthenticatesRequests;
use QuantaQuirk\Http\Request;

class Authenticate implements AuthenticatesRequests
{
    /**
     * The authentication factory instance.
     *
     * @var \QuantaQuirk\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \QuantaQuirk\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Specify the guards for the middleware.
     *
     * @param  string  $guard
     * @param  string  $others
     * @return string
     */
    public static function using($guard, ...$others)
    {
        return static::class.':'.implode(',', [$guard, ...$others]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \QuantaQuirk\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \QuantaQuirk\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \QuantaQuirk\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \QuantaQuirk\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        $this->unauthenticated($request, $guards);
    }

    /**
     * Handle an unauthenticated user.
     *
     * @param  \QuantaQuirk\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \QuantaQuirk\Auth\AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthenticated.', $guards, $this->redirectTo($request)
        );
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \QuantaQuirk\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo(Request $request)
    {
        //
    }
}
