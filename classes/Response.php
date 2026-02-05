<?php

class Response
{
    /**
     * Send a JSON response and exit
     */
    public static function json(bool $success, string $message, array $images = []): void
    {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'images' => $images
        ]);
        exit;
    }

    /**
     * Send a success response
     */
    public static function success(string $message, array $images = []): void
    {
        self::json(true, $message, $images);
    }

    /**
     * Send an error response
     */
    public static function error(string $message): void
    {
        self::json(false, $message);
    }
}
