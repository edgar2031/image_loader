<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Loader & Processor</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        h1 {
            color: #333;
            margin-bottom: 20px;
        }

        .form-container {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }

        input[type="url"],
        input[type="number"],
        input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        input:focus {
            outline: none;
            border-color: #007bff;
        }

        .inline-fields {
            display: flex;
            gap: 15px;
        }

        .inline-fields .form-group {
            flex: 1;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background: #0056b3;
        }

        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        #status {
            margin-top: 15px;
            padding: 10px;
            border-radius: 4px;
            display: none;
        }

        #status.loading {
            display: block;
            background: #e7f3ff;
            color: #0056b3;
        }

        #status.success {
            display: block;
            background: #d4edda;
            color: #155724;
        }

        #status.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
        }

        .images-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .images-section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .image-item {
            position: relative;
            display: inline-block;
        }

        .image-item img {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            padding: 0;
            background: rgba(220, 53, 69, 0.9);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s;
        }

        .image-item:hover .delete-btn {
            opacity: 1;
        }

        .delete-btn:hover {
            background: #c82333;
        }

        .no-images {
            color: #888;
            text-align: center;
            padding: 40px;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <h1>Image Loader & Processor</h1>

    <div class="form-container">
        <form id="imageForm">
            <div class="form-group">
                <label for="url">Webpage URL</label>
                <input type="url" id="url" name="url" placeholder="https://example.com" required>
            </div>

            <div class="inline-fields">
                <div class="form-group">
                    <label for="minWidth">Minimum Width (px)</label>
                    <input type="number" id="minWidth" name="minWidth" value="100" min="1" required>
                </div>
                <div class="form-group">
                    <label for="minHeight">Minimum Height (px)</label>
                    <input type="number" id="minHeight" name="minHeight" value="100" min="1" required>
                </div>
            </div>

            <div class="form-group">
                <label for="overlayText">Overlay Text</label>
                <input type="text" id="overlayText" name="overlayText" placeholder="Enter text to overlay on images">
            </div>

            <button type="submit" id="submitBtn">Process Images</button>
        </form>

        <div id="status"></div>
    </div>

    <div class="images-section">
        <h2>Processed Images</h2>
        <div id="imageGrid" class="image-grid">
            <?php
            require_once __DIR__ . '/classes/ImageStorage.php';

            $storage = new ImageStorage(__DIR__ . '/processed/');
            $images = $storage->getAll();

            if (empty($images)) {
                echo '<div class="no-images">No processed images yet. Submit a URL to get started!</div>';
            } else {
                foreach ($images as $filename) {
                    echo '<div class="image-item">';
                    echo '<img src="processed/' . htmlspecialchars($filename) . '" alt="Processed image">';
                    echo '<button class="delete-btn" onclick="deleteImage(this, \'' . htmlspecialchars($filename) . '\')" title="Delete image">&times;</button>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </div>

    <script>
        function deleteImage(btn, filename) {
            if (!confirm('Delete this image?')) {
                return;
            }

            btn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('filename', filename);

            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the image item from DOM
                    const imageItem = btn.closest('.image-item');
                    imageItem.remove();

                    // Show "no images" message if grid is empty
                    const imageGrid = document.getElementById('imageGrid');
                    if (imageGrid.querySelectorAll('.image-item').length === 0) {
                        imageGrid.innerHTML = '<div class="no-images">No processed images yet. Submit a URL to get started!</div>';
                    }
                } else {
                    alert(data.message || 'Failed to delete image');
                    btn.disabled = false;
                }
            })
            .catch(error => {
                alert('Delete failed: ' + error.message);
                btn.disabled = false;
            });
        }

        document.getElementById('imageForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const submitBtn = document.getElementById('submitBtn');
            const status = document.getElementById('status');
            const imageGrid = document.getElementById('imageGrid');

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner"></span>Processing...';
            status.className = 'loading';
            status.textContent = 'Fetching webpage and processing images...';

            // Gather form data
            const formData = new FormData();
            formData.append('url', document.getElementById('url').value);
            formData.append('minWidth', document.getElementById('minWidth').value);
            formData.append('minHeight', document.getElementById('minHeight').value);
            formData.append('overlayText', document.getElementById('overlayText').value);

            // Send AJAX request
            fetch('process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Process Images';

                if (data.success) {
                    status.className = 'success';
                    status.textContent = data.message;

                    // Remove "no images" message if present
                    const noImages = imageGrid.querySelector('.no-images');
                    if (noImages) {
                        noImages.remove();
                    }

                    // Add new images to the grid (prepend to show newest first)
                    if (data.images && data.images.length > 0) {
                        data.images.forEach(function(imagePath) {
                            const div = document.createElement('div');
                            div.className = 'image-item';

                            const img = document.createElement('img');
                            img.src = imagePath + '?t=' + Date.now(); // Cache bust
                            img.alt = 'Processed image';

                            // Extract filename from path
                            const filename = imagePath.split('/').pop();

                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'delete-btn';
                            deleteBtn.innerHTML = '&times;';
                            deleteBtn.title = 'Delete image';
                            deleteBtn.onclick = function() { deleteImage(this, filename); };

                            div.appendChild(img);
                            div.appendChild(deleteBtn);
                            imageGrid.insertBefore(div, imageGrid.firstChild);
                        });
                    }
                } else {
                    status.className = 'error';
                    status.textContent = data.message || 'An error occurred';
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Process Images';
                status.className = 'error';
                status.textContent = 'Request failed: ' + error.message;
            });
        });
    </script>
</body>
</html>
