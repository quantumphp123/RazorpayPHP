<?php

namespace App\Middleware\RateLimiter;

class FileRateLimiter implements RateLimitStorage
{
    private $filePath = __DIR__ . '/../../storage/rate_limits.json';

    public function check($ip, $route, $limit, $window)
    {
        // Create storage directory if it doesn't exist
        if (!file_exists(dirname($this->filePath))) {
            mkdir(dirname($this->filePath), 0755, true);
        }

        // Read existing data from file
        $data = file_exists($this->filePath) ? json_decode(file_get_contents($this->filePath), true) : [];
        $now = time();

        // Clean up old records
        $data = array_filter($data, function ($entry) use ($now, $window) {
            return ($now - $entry['timestamp']) < $window;
        });

        // Filter attempts for the given IP and route
        $attempts = array_filter($data, function ($entry) use ($ip, $route) {
            return $entry['ip'] === $ip && $entry['route'] === $route;
        });

        if (count($attempts) >= $limit) {
            return false;
        }

        // Add new attempt
        $data[] = ['ip' => $ip, 'route' => $route, 'timestamp' => $now];

        // Save updated data to file
        file_put_contents($this->filePath, json_encode($data));

        return true;
    }
}
