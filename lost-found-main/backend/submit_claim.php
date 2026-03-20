<?php

/**
 * Backend: Submit Claim
 * Handles POST from user/claim-item.php
 * Validates input, handles file uploads, calculates score, inserts claim.
 */
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/claim_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../user/index.php");
    exit;
}

$itemId = (int) ($_POST['item_id'] ?? 0);
$userId = (int) $_SESSION['id'];
$claimantName = trim($_POST['claimant_name'] ?? '');
$claimantEmail = trim($_POST['claimant_email'] ?? '');
$claimantPhone = trim($_POST['claimant_phone'] ?? '');
$itemDescription = trim($_POST['item_description'] ?? '');
$uniqueIdentifiers = trim($_POST['unique_identifiers'] ?? '');

// Validate required fields
if ($itemId <= 0 || $claimantName === '' || $claimantEmail === '' || $itemDescription === '' || $uniqueIdentifiers === '') {
    header("Location: ../user/claim-item.php?id={$itemId}&error=1");
    exit;
}

// Validate email format
if (!filter_var($claimantEmail, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../user/claim-item.php?id={$itemId}&error=1");
    exit;
}

// Check item exists and is claimable
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND type = 'found' AND status = 'open' AND verification_status = 'approved'");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    header("Location: ../user/claim-item.php?id={$itemId}&error=1");
    exit;
}

// Check for existing pending claim by this user
$stmt = $conn->prepare("SELECT id FROM claims WHERE item_id = ? AND user_id = ? AND status IN ('pending', 'under_review')");
$stmt->bind_param("ii", $itemId, $userId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    header("Location: ../user/claim-item.php?id={$itemId}&error=5");
    exit;
}
$stmt->close();

// File upload handling
$uploadDir = __DIR__ . '/../uploads/claims/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB

/**
 * Process a single file upload securely.
 * Returns the stored filename or empty string.
 */
function processUpload(string $fieldName, string $uploadDir, array $allowedTypes, int $maxSize, int $itemId): string
{
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }

    $file = $_FILES[$fieldName];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedTypes, true)) {
        return 'error_type';
    }
    if ($file['size'] > $maxSize) {
        return 'error_size';
    }

    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'pdf' => 'application/pdf'
    ];

    if (!isset($allowedMimes[$ext]) || $allowedMimes[$ext] !== $mimeType) {
        return 'error_type';
    }

    $filename = $fieldName . '_' . $itemId . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return $filename;
    }
    return 'error_upload';
}

$proofImage = processUpload('proof_image', $uploadDir, $allowedTypes, $maxSize, $itemId);
$idDocument = processUpload('id_document', $uploadDir, $allowedTypes, $maxSize, $itemId);
$proofDocument = processUpload('proof_document', $uploadDir, $allowedTypes, $maxSize, $itemId);

// Check for upload errors
foreach (['proof_image' => $proofImage, 'id_document' => $idDocument, 'proof_document' => $proofDocument] as $field => $result) {
    if ($result === 'error_type') {
        header("Location: ../user/claim-item.php?id={$itemId}&error=2");
        exit;
    }
    if ($result === 'error_size') {
        header("Location: ../user/claim-item.php?id={$itemId}&error=3");
        exit;
    }
    if ($result === 'error_upload') {
        header("Location: ../user/claim-item.php?id={$itemId}&error=4");
        exit;
    }
}

// Calculate confidence score
$claimData = [
    'item_description'   => $itemDescription,
    'unique_identifiers' => $uniqueIdentifiers,
    'proof_image'        => $proofImage,
    'id_document'        => $idDocument,
    'proof_document'     => $proofDocument,
];
$confidenceScore = calculateClaimScore($item, $claimData);

// Generate unique claim ID
$claimId = generateClaimId($conn);

// Insert claim
$stmt = $conn->prepare("
    INSERT INTO claims
    (claim_id, item_id, user_id, claimant_name, claimant_email, claimant_phone,
     item_description, unique_identifiers, proof_image, id_document, proof_document, confidence_score)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    "siissssssssi",
    $claimId,
    $itemId,
    $userId,
    $claimantName,
    $claimantEmail,
    $claimantPhone,
    $itemDescription,
    $uniqueIdentifiers,
    $proofImage,
    $idDocument,
    $proofDocument,
    $confidenceScore
);
$stmt->execute();
$stmt->close();

// Notify all admin users
$admins = $conn->query("SELECT id FROM users WHERE role = 'admin'");
while ($admin = $admins->fetch_assoc()) {
    addNotification(
        $conn,
        (int) $admin['id'],
        'New Claim Submitted',
        "A new claim ({$claimId}) has been submitted for item \"" . htmlspecialchars($item['name']) . "\" with a confidence score of {$confidenceScore}%.",
        'info',
        'claim-requests.php'
    );
}

// Notify the claimant
addNotification(
    $conn,
    $userId,
    'Claim Submitted',
    "Your claim ({$claimId}) for \"" . htmlspecialchars($item['name']) . "\" has been submitted and is pending review.",
    'info',
    'my-claims.php'
);

header("Location: ../user/claim-item.php?id={$itemId}&success=1");
exit;
