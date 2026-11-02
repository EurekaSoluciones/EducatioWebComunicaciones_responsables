<?php

namespace App\Http\Middleware;

use Inertia\Middleware;

class HandleEurekaRequests extends Middleware
{
    protected $rootView = 'eureka';
}
