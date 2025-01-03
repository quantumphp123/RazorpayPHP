<?php

namespace App\Core;

class Redirect
{
    /**
     * Redirects to the specified URL.
     *
     * @param string $url The URL to redirect to
     * @param int $statusCode The HTTP status code (default is 302)
     */
    public static function to(string $url, int $statusCode = 302): void
    {
        header("Location: $url", true, $statusCode);
        exit;
    }
}
