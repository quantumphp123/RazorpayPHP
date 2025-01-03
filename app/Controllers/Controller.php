<?php

namespace App\Controllers;

use Exception;

class Controller
{
    // Render a view and pass the data to it
    protected function view(string $viewName, array $data = []): void
    {
        // Replace dot notation (e.g., 'pages.success') with folder path (e.g., 'pages/success')
        $viewName = str_replace('.', DIRECTORY_SEPARATOR, $viewName);

        // Construct the full path to the view file
        $filePath = VIEW_PATH . $viewName . '.php';

        // Check if the view file exists
        if (file_exists($filePath)) {
            // Extract data variables to make them accessible in the view
            extract($data);

            // Include the view file
            include $filePath;
        } else {
            // Handle the error if the view file does not exist
            throw new Exception("View '$viewName' not found.");
        }
    }
}
