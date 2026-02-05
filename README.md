# Image Loader & Processor

A PHP web application that fetches images from any webpage, processes them with intelligent filtering and duplicate detection, and displays them in a responsive gallery.

## Features

- **Web Scraping**: Extract all images from any webpage URL
- **Smart Filtering**: Set minimum width/height requirements
- **Duplicate Detection**: Perceptual hashing (dHash) to skip similar images
- **Image Processing**:
  - Resize to 200px height (maintains aspect ratio)
  - Center crop to 200x200px square
  - Optional text overlay with semi-transparent background
- **AJAX Interface**: Process and display images without page reload
- **Persistent Storage**: Images saved locally and displayed after reload
- **Delete Functionality**: Remove individual images with confirmation
- **Responsive Design**: Clean, modern UI that works on all devices

## Requirements

- PHP 8.0 or higher
- GD Library (for image processing)
- Apache web server (or similar with `.htaccess` support)
- Write permissions for `/processed/` directory

## Installation

1. **Clone or download the project**:
   ```bash
   git clone <repository-url>
   cd image_loader
   ```

2. **Ensure PHP GD extension is enabled**:
   ```bash
   php -m | grep -i gd
   ```

3. **Set directory permissions**:
   ```bash
   chmod 755 processed/
   ```

4. **Configure your web server**:
   - Point document root to the project directory
   - Ensure `.htaccess` files are processed (Apache `AllowOverride All`)

5. **Access the application**:
   ```
   http://localhost/image_loader/
   ```

## Usage

### Basic Workflow

1. **Enter Webpage URL**: Provide the URL of any webpage containing images
2. **Set Dimensions**: Specify minimum width and height (default: 100x100px)
3. **Add Text (Optional)**: Enter text to overlay on images
4. **Process**: Click "Process Images" button
5. **View Results**: Processed images appear in the gallery below
6. **Delete**: Hover over any image and click the × button to remove it

### Example

```
URL: https://example.com/gallery
Minimum Width: 200px
Minimum Height: 200px
Overlay Text: Sample 2024
```

The application will:
- Fetch all images from the page
- Filter out images smaller than 200×200px
- Skip duplicate/similar images
- Resize and crop to 200×200px squares
- Add "Sample 2024" text overlay
- Save as PNG files in `/processed/`

## Project Structure

```
image_loader/
├── index.php                 # Frontend interface
├── process.php              # Backend API endpoint
├── README.md                # Documentation
├── .htaccess                # Apache configuration
├── .gitignore               # Git ignore rules
├── classes/                 # PHP classes (OOP architecture)
│   ├── ImageLoaderApp.php   # Main application controller
│   ├── WebPageFetcher.php   # Webpage fetching & URL extraction
│   ├── ImageProcessor.php   # Image manipulation
│   ├── ImageStorage.php     # File system operations
│   ├── ImageHasher.php      # Duplicate detection
│   └── Response.php         # JSON response utility
└── processed/               # Stored processed images
    └── .gitkeep
```

## How It Works

### 1. Webpage Fetching (WebPageFetcher)
- Downloads HTML content using `file_get_contents()`
- Parses `<img>` tags to extract `src` and `srcset` URLs
- Resolves relative URLs to absolute URLs
- Handles protocol-relative and root-relative paths

### 2. Image Download
- Downloads each image with custom User-Agent
- Creates GD image resource from binary data
- 15-second timeout per image

### 3. Filtering & Deduplication
- **Dimension Check**: Filters images by minimum width/height
- **Duplicate Detection**: Uses difference hashing (dHash)
  - Resizes to 9×8 grayscale thumbnail
  - Compares adjacent pixels to create 64-bit hash
  - Calculates Hamming distance between hashes
  - Threshold: 10 bits (allows slight variations)

### 4. Image Processing
- **Resize**: Scale height to 200px, maintain aspect ratio
- **Crop**: Center crop width to 200px (creates square)
- **Overlay**: Add text with semi-transparent black background
  - Dynamic font sizing based on text length
  - Positioned at bottom center
  - White text color for readability

### 5. Storage
- Saves as PNG format (compression level 6)
- Filename format: `img_{uniqid}_{timestamp}.png`
- Stored in `/processed/` directory
- Automatically creates directory if missing

## Configuration

Edit `process.php` to customize:

```php
$storageDir = __DIR__ . '/processed/';  // Storage directory
$targetSize = 200;                       // Target image size (px)
```

Edit `ImageHasher.php` constructor to adjust duplicate detection:

```php
public function __construct(int $threshold = 10)  // Hamming distance threshold
```

## API Endpoints

### POST /process.php

**Process Images**
```javascript
FormData {
  url: string,           // Webpage URL
  minWidth: number,      // Minimum width in pixels
  minHeight: number,     // Minimum height in pixels
  overlayText: string    // Text to overlay (optional)
}
```

**Delete Image**
```javascript
FormData {
  action: 'delete',
  filename: string       // Image filename to delete
}
```

**Response Format**
```json
{
  "success": true,
  "message": "Successfully processed 5 image(s)",
  "images": [
    "processed/img_abc123_1234567890.png",
    "processed/img_def456_1234567891.png"
  ]
}
```

## Security Features

- Input validation (URL, integer filters)
- Filename validation (alphanumeric, underscore, hyphen only)
- Path traversal protection using `realpath()` checks
- Directory listing prevention (`.htaccess`)
- Hidden file access denial
- XSS protection via `htmlspecialchars()`

## Security Considerations

⚠️ **Note**: This application has SSL peer verification disabled for HTTP requests. For production use, consider:
- Enabling SSL verification in `WebPageFetcher.php`
- Adding authentication/authorization
- Implementing rate limiting
- Adding CSRF protection
- Restricting allowed domains

## Browser Support

- Chrome/Edge 90+
- Firefox 88+
- Safari 14+
- Any modern browser with ES6 support

## Troubleshooting

### Images not displaying
- Check `/processed/` directory permissions (755 or 775)
- Verify GD library is installed: `php -i | grep -i gd`
- Check Apache error logs

### "Failed to fetch webpage"
- Verify URL is accessible
- Check firewall/network restrictions
- Ensure PHP `allow_url_fopen` is enabled

### Text overlay not showing
- Ensure GD library supports `imagestring()`
- Try shorter text (font size auto-adjusts)

### Duplicate images still appearing
- Adjust threshold in `ImageHasher.php` (lower = stricter)
- Clear `/processed/` directory to reset

## Technologies Used

- **PHP 8.0+**: Server-side processing
- **GD Library**: Image manipulation
- **JavaScript ES6**: Frontend interactivity
- **Fetch API**: AJAX requests
- **CSS Grid**: Responsive layout
- **HTML5**: Modern markup

## License

This project is provided as-is for educational and demonstration purposes.

## Contributing

Feel free to submit issues, fork the repository, and create pull requests for any improvements.

## Author

Developed as a demonstration of PHP image processing capabilities with intelligent filtering and duplicate detection.
