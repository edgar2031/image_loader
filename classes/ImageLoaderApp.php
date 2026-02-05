<?php

class ImageLoaderApp
{
    private WebPageFetcher $fetcher;
    private ImageProcessor $processor;
    private ImageStorage $storage;
    private ImageHasher $hasher;

    public function __construct(string $storageDir, int $targetSize = 200)
    {
        $this->fetcher = new WebPageFetcher();
        $this->processor = new ImageProcessor($targetSize);
        $this->storage = new ImageStorage($storageDir);
        $this->hasher = new ImageHasher();
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Invalid request method');
        }

        $action = $_POST['action'] ?? 'process';

        switch ($action) {
            case 'delete':
                $this->handleDelete();
                break;
            case 'process':
            default:
                $this->handleProcess();
                break;
        }
    }

    /**
     * Handle image deletion
     */
    private function handleDelete(): void
    {
        $filename = $_POST['filename'] ?? '';

        if (empty($filename)) {
            Response::error('No filename provided');
        }

        if ($this->storage->delete($filename)) {
            Response::success('Image deleted successfully');
        } else {
            Response::error('Failed to delete image');
        }
    }

    /**
     * Handle image processing from URL
     */
    private function handleProcess(): void
    {
        $params = $this->validateProcessParams();

        // Fetch the webpage
        $html = $this->fetcher->fetch($params['url']);

        if ($html === null) {
            Response::error('Failed to fetch the webpage. Please check the URL.');
        }

        // Extract image URLs
        $imageUrls = $this->fetcher->extractImageUrls($html, $params['url']);

        if (empty($imageUrls)) {
            Response::error('No images found on the webpage');
        }

        // Process images
        $result = $this->processImages($imageUrls, $params);

        if (empty($result['images'])) {
            Response::error(
                "Found " . count($imageUrls) . " images, but none met the minimum dimensions " .
                "({$params['minWidth']}x{$params['minHeight']}px)"
            );
        }

        Response::success(
            "Successfully processed {$result['count']} image(s) meeting minimum dimensions",
            $result['images']
        );
    }

    /**
     * Validate and return processing parameters
     */
    private function validateProcessParams(): array
    {
        $url = filter_input(INPUT_POST, 'url', FILTER_VALIDATE_URL);

        if (!$url) {
            Response::error('Please provide a valid URL');
        }

        $minWidth = filter_input(INPUT_POST, 'minWidth', FILTER_VALIDATE_INT);
        $minHeight = filter_input(INPUT_POST, 'minHeight', FILTER_VALIDATE_INT);
        $overlayText = trim($_POST['overlayText'] ?? '');

        return [
            'url' => $url,
            'minWidth' => $minWidth && $minWidth > 0 ? $minWidth : 100,
            'minHeight' => $minHeight && $minHeight > 0 ? $minHeight : 100,
            'overlayText' => $overlayText
        ];
    }

    /**
     * Process all images from URLs
     */
    private function processImages(array $imageUrls, array $params): array
    {
        $processedImages = [];
        $qualifyingCount = 0;

        $this->hasher->reset();

        foreach ($imageUrls as $imageUrl) {
            $image = $this->fetcher->downloadImage($imageUrl);

            if ($image === null) {
                continue;
            }

            // Check for duplicates
            $hash = $this->hasher->generateHash($image);
            if ($this->hasher->isDuplicate($hash)) {
                imagedestroy($image);
                continue;
            }

            // Check dimensions
            if (!$this->processor->meetsMinimumDimensions($image, $params['minWidth'], $params['minHeight'])) {
                imagedestroy($image);
                continue;
            }

            // Mark as seen and process
            $this->hasher->markAsSeen($hash);
            $qualifyingCount++;

            $processed = $this->processor->process($image, $params['overlayText']);
            imagedestroy($image);

            $savedPath = $this->storage->save($processed);
            $processedImages[] = $savedPath;
        }

        return [
            'count' => $qualifyingCount,
            'images' => $processedImages
        ];
    }

    /**
     * Get the storage instance for use in views
     */
    public function getStorage(): ImageStorage
    {
        return $this->storage;
    }
}
