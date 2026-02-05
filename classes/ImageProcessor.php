<?php

class ImageProcessor
{
    private int $targetSize;

    public function __construct(int $targetSize = 200)
    {
        $this->targetSize = $targetSize;
    }

    /**
     * Process an image: resize, crop, and add overlay
     */
    public function process(GdImage $image, string $overlayText = ''): GdImage
    {
        $resized = $this->resizeToHeight($image);
        $cropped = $this->centerCrop($resized);

        if (!empty($overlayText)) {
            $this->addTextOverlay($cropped, $overlayText);
        }

        return $cropped;
    }

    /**
     * Resize image so height becomes target size (maintaining aspect ratio)
     */
    private function resizeToHeight(GdImage $image): GdImage
    {
        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $newHeight = $this->targetSize;
        $newWidth = (int)($origWidth * ($this->targetSize / $origHeight));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        $this->preserveTransparency($resized);

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

        return $resized;
    }

    /**
     * Center crop image to target size square
     */
    private function centerCrop(GdImage $image): GdImage
    {
        $width = imagesx($image);

        $final = imagecreatetruecolor($this->targetSize, $this->targetSize);
        $this->preserveTransparency($final);

        $cropX = max(0, (int)(($width - $this->targetSize) / 2));
        $cropY = 0;

        imagecopy($final, $image, 0, 0, $cropX, $cropY, $this->targetSize, $this->targetSize);
        imagedestroy($image);

        return $final;
    }

    /**
     * Add text overlay to image
     */
    private function addTextOverlay(GdImage $image, string $text): void
    {
        imagealphablending($image, true);

        // Calculate font size based on text length
        $fontSize = min(5, max(2, 6 - (int)(strlen($text) / 10)));

        $textWidth = imagefontwidth($fontSize) * strlen($text);
        $textHeight = imagefontheight($fontSize);

        // Position text at bottom center
        $x = max(5, (int)(($this->targetSize - $textWidth) / 2));
        $y = $this->targetSize - $textHeight - 10;

        // Semi-transparent background
        $bgColor = imagecolorallocatealpha($image, 0, 0, 0, 64);
        imagefilledrectangle($image, 0, $y - 5, $this->targetSize, $this->targetSize, $bgColor);

        // White text
        $textColor = imagecolorallocate($image, 255, 255, 255);
        imagestring($image, $fontSize, $x, $y, $text, $textColor);
    }

    /**
     * Preserve transparency for PNG images
     */
    private function preserveTransparency(GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
    }

    /**
     * Check if image meets minimum dimension requirements
     */
    public function meetsMinimumDimensions(GdImage $image, int $minWidth, int $minHeight): bool
    {
        return imagesx($image) >= $minWidth && imagesy($image) >= $minHeight;
    }
}
