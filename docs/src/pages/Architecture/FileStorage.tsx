import { Box, Typography, Paper, Alert } from '@mui/material';
import CodeBlock from '../../components/CodeBlock';
import Admonition from '../../components/Admonition';

export default function FileStorage() {
    return (
        <Box>
            <Typography variant="h3" component="h1" gutterBottom>
                File Storage
            </Typography>

            <Typography variant="body1" paragraph>
                BaseAPI includes a simple yet powerful file storage system that abstracts local filesystem
                and cloud storage (S3, Google Cloud), providing a consistent API for file operations across
                different storage backends.
            </Typography>

            <Admonition type="tip" title="KISS Principle">
                The storage system follows BaseAPI's KISS principles - it covers 90% of use cases with a clean,
                simple interface while remaining extensible for advanced scenarios.
            </Admonition>

            {/* Basic Usage */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Basic File Upload
            </Typography>

            <Typography variant="body1" paragraph>
                Upload files using the enhanced <code>UploadedFile</code> class with built-in validation:
            </Typography>

            <CodeBlock
                language="php"
                title="FileUploadController.php"
                code={`<?php

namespace App\\Controllers;

use BaseApi\\Controllers\\Controller;
use BaseApi\\Http\\JsonResponse;
use BaseApi\\Http\\UploadedFile;
use BaseApi\\Http\\Validation\\Attributes\\*;
use BaseApi\\Storage\\Storage;

class FileUploadController extends Controller
{
    #[Required]
    #[File]
    #[Mimes(['jpg', 'jpeg', 'png', 'pdf'])]
    #[Size(5)] // 5MB max
    public UploadedFile $file;

    public function post(): JsonResponse
    {
        // Validate the uploaded file
        $this->validate($this);
        
        // Store file with auto-generated name
        $path = $this->file->store('uploads');
        
        // Get public URL
        $url = Storage::url($path);
        
        return JsonResponse::created([
            'path' => $path,
            'url' => $url,
            'size' => $this->file->getSize(),
            'type' => $this->file->getMimeType()
        ]);
    }
}`}
            />

            {/* Storage Operations */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Storage Operations
            </Typography>

            <Typography variant="body1" paragraph>
                The storage system provides multiple ways to store and manage files:
            </Typography>

            <CodeBlock
                language="php"
                title="Storage Methods"
                code={`// Upload methods on UploadedFile
$path = $file->store('uploads');                    // Auto-generated name
$path = $file->storeAs('uploads', 'custom.jpg');   // Custom name
$path = $file->storePublicly('public/uploads');    // Public storage
$path = $file->storePubliclyAs('public', 'file.pdf'); // Public + custom name

// Direct storage operations
Storage::put('file.txt', 'Hello World');           // Store content
Storage::putFile('docs', $uploadedFile);           // Store uploaded file
Storage::putFileAs('docs', $uploadedFile, 'document.pdf'); // With custom name

// Reading and checking files
$content = Storage::get('file.txt');               // Get content
$exists = Storage::exists('file.txt');             // Check existence
$size = Storage::size('file.txt');                 // Get file size
$url = Storage::url('file.txt');                   // Get public URL

// File management
$deleted = Storage::delete('file.txt');            // Delete file`}
            />

            {/* Configuration */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Configuration
            </Typography>

            <Typography variant="body1" paragraph>
                Storage disks are configured in your application's <code>config/app.php</code> following BaseAPI's unified configuration pattern.
                This keeps all application configuration in one place, making it easy to manage and understand.
            </Typography>

            <Alert severity="info" sx={{ mb: 2 }}>
                <strong>BaseAPI Configuration Pattern:</strong> Unlike other frameworks that use separate config files,
                BaseAPI uses a single <code>config/app.php</code> that extends framework defaults. This follows the KISS principle
                and keeps configuration simple and centralized.
            </Alert>

            <CodeBlock
                language="php"
                title="config/app.php"
                code={`<?php

return [
    // ... other application configuration ...

    'filesystems' => [
        'default' => $_ENV['FILESYSTEM_DISK'] ?? 'local',

        'disks' => [
            'local' => [
                'driver' => 'local',
                'root' => 'storage/app',
                'url' => ($_ENV['APP_URL'] ?? 'http://localhost:7879') . '/storage',
            ],

            'public' => [
                'driver' => 'local',
                'root' => 'storage/app/public',
                'url' => ($_ENV['APP_URL'] ?? 'http://localhost:7879') . '/storage',
                'visibility' => 'public',
            ],

            // Cloud storage examples (coming soon)
            /*
            's3' => [
                'driver' => 's3',
                'key' => $_ENV['AWS_ACCESS_KEY_ID'] ?? null,
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'] ?? null,
                'region' => $_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1',
                'bucket' => $_ENV['AWS_BUCKET'] ?? null,
            ],
            */
        ],
    ],

    // ... other configuration sections ...
];`}
            />

            {/* Multiple Disks */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Working with Multiple Disks
            </Typography>

            <Typography variant="body1" paragraph>
                You can define and use multiple storage disks for different purposes:
            </Typography>

            <CodeBlock
                language="php"
                title="Multiple Disk Usage"
                code={`// Use specific disks
Storage::disk('local')->put('private/document.pdf', $content);
Storage::disk('public')->put('images/photo.jpg', $imageData);

// Upload to different disks
$privatePath = $file->store('documents', 'local');
$publicPath = $file->storePublicly('uploads');

// Cross-disk operations
$content = Storage::disk('local')->get('document.pdf');
Storage::disk('public')->put('backup.pdf', $content);`}
            />

            {/* File Validation */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                File Validation
            </Typography>

            <Typography variant="body1" paragraph>
                BaseAPI's validation system integrates seamlessly with file uploads:
            </Typography>

            <CodeBlock
                language="php"
                title="File Validation Attributes"
                code={`use BaseApi\\Http\\Validation\\Attributes\\*;

class DocumentUploadController extends Controller
{
    #[Required]                              // File is required
    #[File]                                  // Must be a valid file upload
    #[Mimes(['pdf', 'doc', 'docx'])]        // Allowed MIME types
    #[Size(10)]                              // Max 10MB
    public UploadedFile $document;

    // For images with more specific validation
    #[Required]
    #[Image]                                 // Must be an image
    #[Mimes(['jpg', 'png', 'gif'])]         // Image formats
    #[Size(2)]                              // Max 2MB
    public UploadedFile $avatar;
}`}
            />

            {/* Security */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Security Features
            </Typography>

            <Alert severity="success" sx={{ mb: 2 }}>
                BaseAPI's file storage includes built-in security measures to protect your application.
            </Alert>

            <Box component="ul" sx={{ pl: 3 }}>
                <li><strong>Path Traversal Protection:</strong> Prevents directory traversal attacks</li>
                <li><strong>File Type Validation:</strong> MIME type checking and extension validation</li>
                <li><strong>Unique Filenames:</strong> Auto-generated names prevent conflicts and enumeration</li>
                <li><strong>Permission Management:</strong> Configurable file and directory permissions</li>
                <li><strong>Size Limits:</strong> Built-in file size validation</li>
            </Box>

            {/* Example API Endpoints */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Example API Endpoints
            </Typography>

            <Typography variant="body1" paragraph>
                The baseapi-template includes working file upload examples:
            </Typography>

            <Paper sx={{ p: 2, mb: 2 }}>
                <Typography variant="body2" component="div">
                    <strong>POST /files/upload</strong> - Upload with auto-generated filename<br />
                    <strong>POST /files/upload-public</strong> - Upload to public storage<br />
                    <strong>POST /files/upload-custom</strong> - Upload with custom filename<br />
                    <strong>GET /files/info?path=...</strong> - Get file information<br />
                    <strong>DELETE /files</strong> - Delete a stored file
                </Typography>
            </Paper>

            <CodeBlock
                language="bash"
                title="Example Usage"
                code={`# Upload a file
curl -X POST -F "file=@document.pdf" http://localhost:7879/files/upload

# Response
{
  "data": {
    "path": "uploads/document_20241218143022_a1b2c3d4.pdf",
    "url": "http://localhost:7879/storage/uploads/document_20241218143022_a1b2c3d4.pdf",
    "size": 245760,
    "type": "application/pdf",
    "original_name": "document.pdf"
  }
}`}
            />

            {/* Advanced Usage */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Advanced Usage
            </Typography>

            <Typography variant="body1" paragraph>
                For more complex scenarios, you can work directly with the storage system:
            </Typography>

            <CodeBlock
                language="php"
                title="Advanced File Operations"
                code={`use BaseApi\\Storage\\Storage;
use BaseApi\\Storage\\StorageManager;

class FileService
{
    public function processUpload(UploadedFile $file): array
    {
        // Validate file before processing
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Invalid file upload');
        }

        // Store original file
        $originalPath = $file->store('originals');
        
        // Create thumbnail if image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $thumbnailContent = $this->createThumbnail($file);
            $thumbnailPath = Storage::put(
                'thumbnails/' . pathinfo($originalPath, PATHINFO_FILENAME) . '_thumb.jpg',
                $thumbnailContent
            );
        }

        return [
            'original' => [
                'path' => $originalPath,
                'url' => Storage::url($originalPath),
                'size' => Storage::size($originalPath)
            ],
            'thumbnail' => isset($thumbnailPath) ? [
                'path' => $thumbnailPath,
                'url' => Storage::url($thumbnailPath),
                'size' => Storage::size($thumbnailPath)
            ] : null
        ];
    }

    private function createThumbnail(UploadedFile $file): string
    {
        // Image processing logic here
        // Return processed image content
    }
}`}
            />

            {/* What's Coming */}
            <Typography variant="h4" component="h2" gutterBottom sx={{ mt: 4 }}>
                Cloud Storage (Coming Soon)
            </Typography>

            <Alert severity="info" sx={{ mb: 2 }}>
                Cloud storage drivers for AWS S3 and Google Cloud Storage are planned for the next release.
            </Alert>

            <Typography variant="body1" paragraph>
                The storage system is architected to support cloud storage with the same simple API:
            </Typography>

            <CodeBlock
                language="php"
                title="Future Cloud Storage Usage"
                code={`// Same API, different storage backend
Storage::disk('s3')->put('documents/file.pdf', $content);
Storage::disk('gcs')->putFile('uploads', $file);

// Seamless switching between local and cloud
$disk = ($_ENV['APP_ENV'] ?? 'local') === 'production' ? 's3' : 'local';
$path = Storage::disk($disk)->putFile('uploads', $file);`}
            />

            <Admonition type="note" title="Implementation Status">
                The core file storage system with local filesystem support is complete and ready for production use.
                Cloud storage drivers will be added in a future release while maintaining the same simple API.
            </Admonition>
        </Box>
    );
}

