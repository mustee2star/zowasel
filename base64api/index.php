<?php

// Allow CORS from all domains
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Get the raw POST data
$input = file_get_contents("php://input");

// Decode the JSON input
$data = json_decode($input, true);

// Determine which type of file is provided and set the appropriate variables
$fileType = '';
$fileData = '';
$targetDir = '';

if (isset($data['imagefile'])) {
    $fileType = 'image';
    $fileData = $data['imagefile'];
    $targetDir = 'images/';
} elseif (isset($data['audiofile'])) {
    $fileType = 'audio';
    $fileData = $data['audiofile'];
    $targetDir = 'audio/';
} elseif (isset($data['videofile'])) {
    $fileType = 'video';
    $fileData = $data['videofile'];
    $targetDir = 'videos/';
} else {
    // Return an error response if no valid file field is found
    echo json_encode([
        'error' => true,
        'message' => 'No valid file provided (imagefile, audiofile, or videofile).',
        'data' => ''
    ]);
    exit;
}

// Ensure the target directory exists
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

// Function to handle file upload and return the response
function uploadFile($fileData, $targetDir) {
    // Remove data:mimetype;base64, header if present
    if (preg_match('/^data:(\w+\/[\w\+\-\.]+);base64,/', $fileData, $type)) {
        $fileData = substr($fileData, strpos($fileData, ',') + 1);
        $extension = strtolower(explode('/', $type[1])[1]); // Get the file extension
    } else {
        return json_encode([
            'error' => true,
            'message' => 'Invalid file data.',
            'data' => ''
        ]);
    }

    // Decode the base64 string
    $decodedFile = base64_decode($fileData);

    if ($decodedFile === false) {
        return json_encode([
            'error' => true,
            'message' => 'Base64 decode failed.',
            'data' => ''
        ]);
    }

    // Generate a unique filename for the file
    $fileName = uniqid('file_') . '.' . $extension;
    $filePath = $targetDir . $fileName;

    // Save the file
    if (file_put_contents($filePath, $decodedFile) === false) {
        return json_encode([
            'error' => true,
            'message' => 'Failed to save the file.',
            'data' => ''
        ]);
    }

    // Construct the file URL
    $fileURL = "https://base64api.zowasel.com/" . $filePath;

    // Return a success response with the file URL
    return json_encode([
        'error' => false,
        'message' => 'File uploaded successfully.',
        'data' => $fileURL
    ]);
}

// Call the uploadFile function and output the response
echo uploadFile($fileData, $targetDir);

?>
