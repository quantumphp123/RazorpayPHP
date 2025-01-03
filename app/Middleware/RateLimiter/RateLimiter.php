<?php
namespace App\Middleware\RateLimiter;

class RateLimiter
{
    private $storage;

    public function __construct($storageType = 'session')
    {
        $this->storage = $this->getStorageHandler($storageType);
    }

    // Returns the appropriate storage handler.
    private function getStorageHandler($storageType)
    {
        switch ($storageType) {
            case 'session':
                return new SessionRateLimiter();
            case 'file':
                return new FileRateLimiter();
            case 'database':
                return new DatabaseRateLimiter();
            default:
                throw new \InvalidArgumentException("Invalid storage type: $storageType");
        }
    }

    // General check method for rate limiting.
    public function check($ip, $route, $limit = 5, $window = 300)
    {
        return $this->storage->check($ip, $route, $limit, $window);
    }

}
