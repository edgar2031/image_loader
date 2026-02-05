<?php

class ImageStorage
{
    private string $directory;

    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/') . '/';
        $this->ensureDirectoryExists();
    }

    /**
     * Save a processed image to disk
     */
    public function save(GdImage $image): string
    {
        $filename = 'img_' . uniqid() . '_' . time() . '.png';
        $filepath = $this->directory . $filename;

        imagepng($image, $filepath, 6);
        imagedestroy($image);

        return 'processed/' . $filename;
    }

    /**
     * Delete an image by filename
     */
    public function delete(string $filename): bool
    {
        // Validate filename format
        if (!$this->isValidFilename($filename)) {
            return false;
        }

        $filepath = $this->directory . $filename;

        // Security check: ensure file is within the processed directory
        $realPath = realpath($filepath);
        $realDir = realpath($this->directory);

        if (!$realPath || strpos($realPath, $realDir) !== 0) {
            return false;
        }

        if (!file_exists($realPath)) {
            return false;
        }

        return unlink($realPath);
    }

    /**
     * Get all stored images sorted by modification time (newest first)
     */
    public function getAll(): array
    {
        $images = glob($this->directory . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        if (empty($images)) {
            return [];
        }

        usort($images, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        return array_map(function ($path) {
            return basename($path);
        }, $images);
    }

    /**
     * Validate filename format
     */
    private function isValidFilename(string $filename): bool
    {
        return (bool)preg_match('/^[a-zA-Z0-9_\-]+\.(png|jpg|jpeg|gif)$/', $filename);
    }

    /**
     * Ensure the storage directory exists
     */
    private function ensureDirectoryExists(): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0755, true);
        }
    }

    /**
     * Get the directory path
     */
    public function getDirectory(): string
    {
        return $this->directory;
    }
}
