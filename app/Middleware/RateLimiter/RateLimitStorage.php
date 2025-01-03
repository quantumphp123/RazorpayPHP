<?php

namespace App\Middleware\RateLimiter;

interface RateLimitStorage {
    public function check($ip, $route, $limit, $window);
}
