<?php
require(__DIR__ . '/./vendor/autoload.php');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

try {
    // Load environment variables
    $dotenv = Dotenv::createImmutable(__DIR__ . '/.');
    $dotenv->load();

    // Validate required environment variables
    $requiredEnvVars = ['AWS_REGION', 'AWS_ACCESS_KEY_ID', 'AWS_SECRET_ACCESS_KEY', 'AWS_BUCKET_NAME'];
    foreach ($requiredEnvVars as $envVar) {
        if (empty($_ENV[$envVar])) {
            throw new Exception("Missing required environment variable: $envVar");
        }
    }

    // Initialize S3 client
    $s3 = new S3Client([
        'region'      => $_ENV['AWS_REGION'],
        'version'     => 'latest',
        'credentials' => [
            'key'    => $_ENV['AWS_ACCESS_KEY_ID'],
            'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
        ],
    ]);

    // Check if file upload exists and has no basic errors
    if (!isset($_FILES['file'])) {
        throw new Exception('No file provided in upload request', 400);
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds maximum upload size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];

        $errorCode = $_FILES['file']['error'];
        $message = $uploadErrors[$errorCode] ?? 'Unknown upload error';
        throw new Exception($message, 400);
    }

    $file = $_FILES['file'];
    $isDocument = isset($_POST['isDocument']) && $_POST['isDocument'] === 'true';

    // Validate file size (example: max 10MB)
    $maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds maximum limit of " . ($maxSize / 1024 / 1024) . "MB", 400);
    }

    // Log environment variables and file details
    error_log("AWS Region: " . $_ENV['AWS_REGION']);
    error_log("AWS Bucket: " . $_ENV['AWS_BUCKET_NAME']);
    error_log("File details: " . json_encode([
        'name' => $file['name'],
        'type' => $file['type'],
        'size' => $file['size']
    ]));

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    if (empty($extension) && $file['type']) {
        $mime_types = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
        $extension = $mime_types[$file['type']] ?? 'unknown';
    }

    if ($extension === 'unknown') {
        throw new Exception('Unable to determine file type', 400);
    }

    $uniqueName = uniqid() . '_' . time() . '.' . $extension;
    $key = $_ENV['AWS_S3_FOLDER'] . '/' . $uniqueName;

    // Verify file exists and is readable
    if (!is_readable($file['tmp_name'])) {
        throw new Exception('Uploaded file is not readable', 500);
    }

    // Perform the upload
    $uploadResponse = $s3->putObject([
        'Bucket'      => $_ENV['AWS_BUCKET_NAME'],
        'Key'         => $key,
        'SourceFile'  => $file['tmp_name'],
        'ContentType' => $isDocument ? $file['type'] : 'image/jpeg'
    ]);

    error_log("Upload successful. Object URL: " . $uploadResponse['ObjectURL']);
    echo json_encode([
        'success' => true,
        'url' => $uploadResponse['ObjectURL'],
        'filename' => $uniqueName,
        'originalname' => $file['name']
    ]);
} catch (AwsException $e) {
    // AWS-specific errors
    $errorDetails = [
        'message' => "AWS Upload Error: " . $e->getMessage(),
        'code' => $e->getAwsErrorCode(),
        'type' => $e->getAwsErrorType(),
        'requestId' => $e->getAwsRequestId()
    ];

    error_log(json_encode($errorDetails));
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Upload failed',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // General errors
    $statusCode = $e->getCode() ?: 500;
    error_log("Error: " . $e->getMessage() . " (Code: $statusCode)");
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

exit;