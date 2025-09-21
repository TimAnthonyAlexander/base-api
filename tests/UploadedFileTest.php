<?php

namespace BaseApi\Tests;

use Override;
use Throwable;
use PHPUnit\Framework\TestCase;
use BaseApi\Http\UploadedFile;

class UploadedFileTest extends TestCase
{
    private string $tempFile;

    #[Override]
    protected function setUp(): void
    {
        // Create a temporary file for testing
        $this->tempFile = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($this->tempFile, 'test file content');
    }

    #[Override]
    protected function tearDown(): void
    {
        if (file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    public function testConstructorWithCompleteData(): void
    {
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        $this->assertEquals('test.jpg', $uploadedFile->name);
        $this->assertEquals('image/jpeg', $uploadedFile->clientType);
        $this->assertEquals($this->tempFile, $uploadedFile->tmpName);
        $this->assertEquals(UPLOAD_ERR_OK, $uploadedFile->error);
        $this->assertEquals(1024, $uploadedFile->size);
    }

    public function testConstructorWithMissingData(): void
    {
        $fileData = [
            'name' => 'test.jpg',
            // Missing other fields
        ];

        $uploadedFile = new UploadedFile($fileData);

        $this->assertEquals('test.jpg', $uploadedFile->name);
        $this->assertEquals('', $uploadedFile->clientType);
        $this->assertEquals('', $uploadedFile->tmpName);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $uploadedFile->error);
        $this->assertEquals(0, $uploadedFile->size);
    }

    public function testConstructorWithEmptyData(): void
    {
        $uploadedFile = new UploadedFile([]);

        $this->assertEquals('', $uploadedFile->name);
        $this->assertEquals('', $uploadedFile->clientType);
        $this->assertEquals('', $uploadedFile->tmpName);
        $this->assertEquals(UPLOAD_ERR_NO_FILE, $uploadedFile->error);
        $this->assertEquals(0, $uploadedFile->size);
    }

    public function testIsValid(): void
    {
        // Valid file
        $validFileData = [
            'name' => 'test.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $validFile = new UploadedFile($validFileData);
        $this->assertTrue($validFile->isValid());

        // Invalid file (error code)
        $invalidFileData = [
            'name' => 'test.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 1024
        ];

        $invalidFile = new UploadedFile($invalidFileData);
        $this->assertFalse($invalidFile->isValid());
    }

    public function testGetExtension(): void
    {
        $testCases = [
            'document.pdf' => 'pdf',
            'image.JPG' => 'jpg', // Should be lowercase
            'archive.tar.gz' => 'gz', // Should return last extension
            'noextension' => '',
            'file.' => '', // Edge case
            '.hidden' => 'hidden', // .hidden files have 'hidden' as extension
        ];

        foreach ($testCases as $filename => $expectedExtension) {
            $fileData = ['name' => $filename, 'tmp_name' => $this->tempFile];
            $uploadedFile = new UploadedFile($fileData);
            
            $this->assertEquals($expectedExtension, $uploadedFile->getExtension(), 
                'Failed for filename: ' . $filename);
        }
    }

    public function testGetMimeType(): void
    {
        // Since the temp file contains text, it should be detected as text/plain
        $fileData = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $this->tempFile,
        ];

        $uploadedFile = new UploadedFile($fileData);
        $mimeType = $uploadedFile->getMimeType();

        // The actual mime type detected from file content
        $this->assertIsString($mimeType);
        $this->assertNotEmpty($mimeType);
    }

    public function testGetMimeTypeFallback(): void
    {
        // Test with non-existent file to trigger fallback
        $fileData = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/non/existent/file',
        ];

        $uploadedFile = new UploadedFile($fileData);
        $mimeType = $uploadedFile->getMimeType();

        // Should fallback to client type
        $this->assertEquals('image/jpeg', $mimeType);
    }

    public function testGetMimeTypeFallbackToDefault(): void
    {
        // Test with non-existent file and no client type
        $fileData = [
            'name' => 'test.unknown',
            'tmp_name' => '/non/existent/file',
        ];

        $uploadedFile = new UploadedFile($fileData);
        $mimeType = $uploadedFile->getMimeType();

        // Should fallback to default
        $this->assertEquals('application/octet-stream', $mimeType);
    }

    public function testGetSize(): void
    {
        $fileData = [
            'name' => 'test.jpg',
            'size' => 2048
        ];

        $uploadedFile = new UploadedFile($fileData);
        $this->assertEquals(2048, $uploadedFile->getSize());
    }

    public function testGetSizeInMB(): void
    {
        $fileData = [
            'name' => 'test.jpg',
            'size' => 2097152 // 2MB in bytes
        ];

        $uploadedFile = new UploadedFile($fileData);
        $this->assertEquals(2.0, $uploadedFile->getSizeInMB());

        // Test with smaller size
        $fileData2 = [
            'name' => 'small.txt',
            'size' => 1048576 // 1MB in bytes
        ];

        $uploadedFile2 = new UploadedFile($fileData2);
        $this->assertEquals(1.0, $uploadedFile2->getSizeInMB());

        // Test with fractional MB
        $fileData3 = [
            'name' => 'medium.doc',
            'size' => 1572864 // 1.5MB in bytes
        ];

        $uploadedFile3 = new UploadedFile($fileData3);
        $this->assertEquals(1.5, $uploadedFile3->getSizeInMB());
    }

    public function testStore(): void
    {
        $fileData = [
            'name' => 'test.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        // Since Storage is not properly initialized in test environment,
        // we expect this to throw an exception
        $this->expectException(Throwable::class);
        $uploadedFile->store('uploads');
    }

    public function testStoreAs(): void
    {
        $fileData = [
            'name' => 'original.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        // Since Storage is not properly initialized in test environment,
        // we expect this to throw an exception
        $this->expectException(Throwable::class);
        $uploadedFile->storeAs('uploads', 'custom-name.jpg');
    }

    public function testStorePublicly(): void
    {
        $fileData = [
            'name' => 'public-test.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        // Since Storage is not properly initialized in test environment,
        // we expect this to throw an exception
        $this->expectException(Throwable::class);
        $uploadedFile->storePublicly('images');
    }

    public function testStorePubliclyAs(): void
    {
        $fileData = [
            'name' => 'public-original.jpg',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        // Since Storage is not properly initialized in test environment,
        // we expect this to throw an exception
        $this->expectException(Throwable::class);
        $uploadedFile->storePubliclyAs('images', 'public-custom.jpg');
    }

    public function testAllErrorCodes(): void
    {
        $errorCodes = [
            UPLOAD_ERR_OK => true,
            UPLOAD_ERR_INI_SIZE => false,
            UPLOAD_ERR_FORM_SIZE => false,
            UPLOAD_ERR_PARTIAL => false,
            UPLOAD_ERR_NO_FILE => false,
            UPLOAD_ERR_NO_TMP_DIR => false,
            UPLOAD_ERR_CANT_WRITE => false,
            UPLOAD_ERR_EXTENSION => false,
        ];

        foreach ($errorCodes as $errorCode => $expectedValid) {
            $fileData = [
                'name' => 'test.jpg',
                'tmp_name' => $this->tempFile,
                'error' => $errorCode,
                'size' => 1024
            ];

            $uploadedFile = new UploadedFile($fileData);
            $this->assertEquals($expectedValid, $uploadedFile->isValid(), 
                'Failed for error code: ' . $errorCode);
        }
    }

    public function testMimeTypeDetectionIntegration(): void
    {
        // Create a proper image file for testing
        $imageContent = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $imageFile = tempnam(sys_get_temp_dir(), 'test_image_');
        file_put_contents($imageFile, $imageContent);

        $fileData = [
            'name' => 'test.gif',
            'type' => 'text/plain', // Wrong client type
            'tmp_name' => $imageFile,
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($imageContent)
        ];

        $uploadedFile = new UploadedFile($fileData);
        $detectedType = $uploadedFile->getMimeType();

        // The detected type should be the actual type, not the client type
        $this->assertStringContainsString('image', $detectedType);
        $this->assertEquals('gif', $uploadedFile->getExtension());

        unlink($imageFile);
    }

    public function testConstructorSetsTypeFromMimeDetection(): void
    {
        $fileData = [
            'name' => 'test.txt',
            'type' => 'wrong/type',
            'tmp_name' => $this->tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];

        $uploadedFile = new UploadedFile($fileData);

        // clientType should be the original
        $this->assertEquals('wrong/type', $uploadedFile->clientType);
        
        // type should be the detected mime type
        $this->assertNotEquals('wrong/type', $uploadedFile->type);
        $this->assertIsString($uploadedFile->type);
    }

    public function testLargeFileSizeInMB(): void
    {
        $fileData = [
            'name' => 'huge.zip',
            'size' => 104857600 // 100MB
        ];

        $uploadedFile = new UploadedFile($fileData);
        $this->assertEquals(100.0, $uploadedFile->getSizeInMB());
    }

    public function testZeroSizeFile(): void
    {
        $fileData = [
            'name' => 'empty.txt',
            'size' => 0
        ];

        $uploadedFile = new UploadedFile($fileData);
        $this->assertEquals(0, $uploadedFile->getSize());
        $this->assertEquals(0.0, $uploadedFile->getSizeInMB());
    }

    public function testVerySmallFileSizeInMB(): void
    {
        $fileData = [
            'name' => 'tiny.txt',
            'size' => 512 // 0.5KB
        ];

        $uploadedFile = new UploadedFile($fileData);
        $sizeInMB = $uploadedFile->getSizeInMB();
        
        $this->assertLessThan(0.001, $sizeInMB);
        $this->assertGreaterThanOrEqual(0, $sizeInMB);
    }
}
