<?php

namespace App\Middleware\RateLimiter;

class DatabaseRateLimiter implements RateLimitStorage
{
    public function check($ip, $route, $limit, $window)
    {
        global $db;
        $now = time();

        // Remove old attempts from database
        $db->query("DELETE FROM rate_limits WHERE timestamp < ?", [date('Y-m-d H:i:s', $now - $window)]);

        // Count current attempts in the database for this IP and route
        $attempts = $db->query(
            "SELECT COUNT(*) as count FROM rate_limits WHERE ip = ? AND route = ? AND timestamp > ?",
            [$ip, $route, date('Y-m-d H:i:s', $now - $window)]
        )->find();

        if ($attempts['count'] >= $limit) {
            return false;
        }

        // Log the new attempt into the database
        $db->query(
            "INSERT INTO rate_limits (ip, route, timestamp) VALUES (?, ?, ?)",
            [$ip, $route, date('Y-m-d H:i:s', $now)]
        );

        return true;

    }

}
