<?php

namespace QuantaQuirk\Auth\Events;

use QuantaQuirk\Http\Request;

class Lockout
{
    /**
     * The throttled request.
     *
     * @var \QuantaQuirk\Http\Request
     */
    public $request;

    /**
     * Create a new event instance.
     *
     * @param  \QuantaQuirk\Http\Request  $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
}
