<?php

require_once 'CSVProcessor.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => false, 'message' => 'Method not allowed']);
    exit();
}

if (!isset($_FILES['csvFile'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => false, 'message' => 'No file selected']);
    exit();
}

$fileError = $_FILES['csvFile']['error'];
if ($fileError !== UPLOAD_ERR_OK) {
    http_response_code(400); // Bad Request
    $errorMessage = 'Error while uploading the file.';
    switch ($fileError) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $errorMessage = 'File size exceeds the maximum allowed size.';
            break;
        case UPLOAD_ERR_PARTIAL:
            $errorMessage = 'File upload was only partially completed.';
            break;
        case UPLOAD_ERR_NO_FILE:
            $errorMessage = 'No file was uploaded.';
            break;
    }
    echo json_encode(['status' => false, 'message' => $errorMessage]);
    exit();
}

$allowedFileTypes = ['text/csv', 'application/vnd.ms-excel'];
if (!in_array($_FILES['csvFile']['type'], $allowedFileTypes)) {
    http_response_code(415); // Unsupported Media Type
    echo json_encode(['status' => false, 'message' => 'Only CSV files are allowed']);
    exit();
}

$database = new Database(__DIR__ . '/.env'); // Pass the correct .env file path
$dbConnection = $database->connect();

$processor = new CSVProcessor($dbConnection);
$result = $processor->process($_FILES['csvFile']['tmp_name']);

// Check if there was an error during processing
if (!$result['status']) {
    http_response_code(500); // Internal Server Error
}

header('Content-Type: application/json');
echo json_encode($result);
