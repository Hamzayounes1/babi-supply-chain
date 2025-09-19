<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Middleware\TrustProxies as Middleware;
 // older Laravel used Fideloper, but the base alias still works for many installs

// If your app uses the core class in newer Laravel versions, use:
// use Illuminate\Http\Middleware\TrustProxies as Middleware;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Set to '*' for local/dev behind proxies (or set to an array of IPs).
     *
     * @var array|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * Use the REQUEST constants provided by Illuminate\Http\Request.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_ALL;
}
