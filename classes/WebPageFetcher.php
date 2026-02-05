<?php

class WebPageFetcher
{
    private string $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
    private int $timeout;

    public function __construct(int $timeout = 30)
    {
        $this->timeout = $timeout;
    }

    /**
     * Fetch webpage content
     */
    public function fetch(string $url): ?string
    {
        $context = $this->createContext($this->timeout);
        $content = @file_get_contents($url, false, $context);

        return $content !== false ? $content : null;
    }

    /**
     * Extract all image URLs from HTML content
     */
    public function extractImageUrls(string $html, string $baseUrl): array
    {
        $images = [];
        $urlParser = new UrlResolver($baseUrl);

        // Find all img src attributes
        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $matches);

        foreach ($matches[1] as $src) {
            $imageUrl = $urlParser->resolve($src);
            if ($imageUrl && !in_array($imageUrl, $images)) {
                $images[] = $imageUrl;
            }
        }

        // Also check for srcset attributes
        preg_match_all('/<img[^>]+srcset=["\']([^"\']+)["\'][^>]*>/i', $html, $srcsetMatches);

        foreach ($srcsetMatches[1] as $srcset) {
            $parts = explode(',', $srcset);
            foreach ($parts as $part) {
                $part = trim($part);
                $urlPart = preg_split('/\s+/', $part)[0];
                $imageUrl = $urlParser->resolve($urlPart);
                if ($imageUrl && !in_array($imageUrl, $images)) {
                    $images[] = $imageUrl;
                }
            }
        }

        return $images;
    }

    /**
     * Download an image and return as GD resource
     */
    public function downloadImage(string $url): ?GdImage
    {
        $context = $this->createContext(15);
        $imageData = @file_get_contents($url, false, $context);

        if ($imageData === false) {
            return null;
        }

        $image = @imagecreatefromstring($imageData);

        return $image ?: null;
    }

    /**
     * Create stream context for HTTP requests
     */
    private function createContext(int $timeout): mixed
    {
        return stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: {$this->userAgent}\r\n",
                'timeout' => $timeout,
                'follow_location' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
    }
}

class UrlResolver
{
    private string $scheme;
    private string $host;
    private string $basePath;

    public function __construct(string $baseUrl)
    {
        $parsed = parse_url($baseUrl);
        $this->scheme = $parsed['scheme'] ?? 'https';
        $this->host = $parsed['host'] ?? '';
        $this->basePath = isset($parsed['path']) ? dirname($parsed['path']) : '';
    }

    /**
     * Resolve a potentially relative URL to an absolute URL
     */
    public function resolve(string $url): ?string
    {
        $url = trim($url);

        // Skip data URIs and empty URLs
        if (empty($url) || strpos($url, 'data:') === 0) {
            return null;
        }

        // Already absolute URL
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }

        // Protocol-relative URL
        if (strpos($url, '//') === 0) {
            return $this->scheme . ':' . $url;
        }

        // Root-relative URL
        if (strpos($url, '/') === 0) {
            return $this->scheme . '://' . $this->host . $url;
        }

        // Relative URL
        return $this->scheme . '://' . $this->host . $this->basePath . '/' . $url;
    }
}
