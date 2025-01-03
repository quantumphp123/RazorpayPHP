<?php

namespace App\Middleware\RateLimiter;

class SessionRateLimiter implements RateLimitStorage {
    public function check($ip, $route, $limit, $window) {
        $now = time();
        $sessionKey = "rate_limit_{$ip}_{$route}";

        // Get the current session data for the IP and route
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // Clean up old records
        $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function ($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });

        // Count the number of attempts for the given route and IP
        if (count($_SESSION[$sessionKey]) >= $limit) {
            return false;
        }

        // Log the current attempt
        $_SESSION[$sessionKey][] = $now;

        return true;
    }

}
