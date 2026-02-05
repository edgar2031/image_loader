<?php

class ImageHasher
{
    private int $threshold;
    private array $seenHashes = [];

    public function __construct(int $threshold = 10)
    {
        $this->threshold = $threshold;
    }

    /**
     * Generate a difference hash (dHash) for robust image deduplication
     */
    public function generateHash($image): string
    {
        $width = imagesx($image);
        $height = imagesy($image);

        // Create a 9x8 grayscale version (9 wide to get 8 horizontal differences)
        $tiny = imagecreatetruecolor(9, 8);
        imagecopyresampled($tiny, $image, 0, 0, 0, 0, 9, 8, $width, $height);

        // Build hash by comparing adjacent pixels
        $hash = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $leftRgb = imagecolorat($tiny, $x, $y);
                $rightRgb = imagecolorat($tiny, $x + 1, $y);

                // Convert to grayscale using luminosity method
                $leftGray = $this->toGrayscale($leftRgb);
                $rightGray = $this->toGrayscale($rightRgb);

                $hash .= ($leftGray > $rightGray) ? '1' : '0';
            }
        }

        imagedestroy($tiny);

        return $hash;
    }

    /**
     * Convert RGB value to grayscale
     */
    private function toGrayscale(int $rgb): float
    {
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        return $r * 0.299 + $g * 0.587 + $b * 0.114;
    }

    /**
     * Calculate Hamming distance between two binary hash strings
     */
    public function hammingDistance(string $hash1, string $hash2): int
    {
        if (strlen($hash1) !== strlen($hash2)) {
            return PHP_INT_MAX;
        }

        $distance = 0;
        for ($i = 0; $i < strlen($hash1); $i++) {
            if ($hash1[$i] !== $hash2[$i]) {
                $distance++;
            }
        }

        return $distance;
    }

    /**
     * Check if an image hash is similar to any previously seen hash
     */
    public function isDuplicate(string $hash): bool
    {
        foreach ($this->seenHashes as $seenHash) {
            if ($this->hammingDistance($hash, $seenHash) <= $this->threshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mark a hash as seen
     */
    public function markAsSeen(string $hash): void
    {
        $this->seenHashes[] = $hash;
    }

    /**
     * Reset seen hashes
     */
    public function reset(): void
    {
        $this->seenHashes = [];
    }
}
