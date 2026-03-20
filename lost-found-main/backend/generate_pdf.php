<?php

/**
 * Backend: Generate PDF Claim Receipt
 * Uses DomPDF to create a printable claim receipt document.
 * Accessible by both admin and claimant (for approved claims).
 */
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$claimDbId = (int) ($_GET['claim_id'] ?? 0);
if ($claimDbId <= 0) {
    die('Invalid claim ID.');
}

// Fetch the claim with item data
$stmt = $conn->prepare("
    SELECT c.*, i.name as item_name, i.category as item_category, i.color as item_color,
           i.location as item_location, i.description as item_description,
           u.fullname as user_fullname
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON c.user_id = u.id
    WHERE c.id = ?
");
$stmt->bind_param("i", $claimDbId);
$stmt->execute();
$claim = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$claim) {
    die('Claim not found.');
}

// Security check: only the claimant or admin can download
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$isOwner = (int) $_SESSION['id'] === (int) $claim['user_id'];
if (!$isAdmin && !$isOwner) {
    die('Access denied.');
}

// Build the PDF HTML
$claimDate = date('F j, Y', strtotime($claim['date_claimed']));
$currentDate = date('F j, Y');
$statusLabel = ucfirst(str_replace('_', ' ', $claim['status']));

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header h2 {
            margin: 5px 0 0;
            font-size: 16px;
            color: #666;
            font-weight: normal;
        }
        .header p {
            margin: 5px 0 0;
            color: #888;
            font-size: 11px;
        }
        .claim-id-box {
            background: #f0f4f8;
            border: 2px solid #2c3e50;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
            margin: 15px 0;
        }
        .claim-id-box .label {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
        }
        .claim-id-box .value {
            font-size: 20px;
            font-weight: bold;
            color: #2c3e50;
        }
        .section {
            margin: 20px 0;
        }
        .section-title {
            background: #2c3e50;
            color: white;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 0;
        }
        .section-body {
            border: 1px solid #ddd;
            border-top: none;
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 6px 8px;
            vertical-align: top;
        }
        table td.label {
            font-weight: bold;
            width: 35%;
            color: #555;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        .status-approved { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-under_review { background: #d1ecf1; color: #0c5460; }
        .score-box {
            text-align: center;
            padding: 10px;
            border: 2px solid #28a745;
            border-radius: 8px;
            margin: 10px 0;
        }
        .score-box .score {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
        }
        .score-box .score-label {
            font-size: 11px;
            color: #666;
        }
        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        .sig-line {
            border-top: 1px solid #333;
            width: 250px;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 11px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #888;
        }
        .two-col { overflow: hidden; }
        .two-col .col { float: left; width: 48%; }
        .two-col .col:last-child { float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lost &amp; Found System</h1>
        <h2>Item Claim Receipt</h2>
        <p>Official Document &mdash; Generated on ' . htmlspecialchars($currentDate) . '</p>
    </div>

    <div class="claim-id-box">
        <div class="label">Claim Reference Number</div>
        <div class="value">' . htmlspecialchars($claim['claim_id']) . '</div>
        <div class="label" style="margin-top:5px;">Status: <span class="status-badge status-' . htmlspecialchars($claim['status']) . '">' . htmlspecialchars($statusLabel) . '</span></div>
    </div>

    <div class="section">
        <div class="section-title">Claimant Information</div>
        <div class="section-body">
            <table>
                <tr><td class="label">Full Name:</td><td>' . htmlspecialchars($claim['claimant_name']) . '</td></tr>
                <tr><td class="label">Email:</td><td>' . htmlspecialchars($claim['claimant_email']) . '</td></tr>
                <tr><td class="label">Phone:</td><td>' . htmlspecialchars($claim['claimant_phone'] ?: 'N/A') . '</td></tr>
                <tr><td class="label">User Account:</td><td>' . htmlspecialchars($claim['user_fullname']) . '</td></tr>
                <tr><td class="label">Date of Claim:</td><td>' . htmlspecialchars($claimDate) . '</td></tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Item Details</div>
        <div class="section-body">
            <table>
                <tr><td class="label">Item Name:</td><td>' . htmlspecialchars($claim['item_name']) . '</td></tr>
                <tr><td class="label">Category:</td><td>' . htmlspecialchars($claim['item_category']) . '</td></tr>
                <tr><td class="label">Color:</td><td>' . htmlspecialchars($claim['item_color']) . '</td></tr>
                <tr><td class="label">Location Found:</td><td>' . htmlspecialchars($claim['item_location']) . '</td></tr>
                <tr><td class="label">Item Description:</td><td>' . htmlspecialchars($claim['item_description']) . '</td></tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Verification Details</div>
        <div class="section-body">
            <div class="two-col">
                <div class="col">
                    <div class="score-box">
                        <div class="score">' . (int)$claim['confidence_score'] . '%</div>
                        <div class="score-label">Confidence Score</div>
                    </div>
                </div>
                <div class="col">
                    <table>
                        <tr><td class="label">Documents:</td><td>' .
    (!empty($claim['proof_image']) ? 'Photo ' : '') .
    (!empty($claim['id_document']) ? 'ID ' : '') .
    (!empty($claim['proof_document']) ? 'Proof' : '') .
    (empty($claim['proof_image']) && empty($claim['id_document']) && empty($claim['proof_document']) ? 'None' : '') .
    '</td></tr>' .
    (!empty($claim['admin_notes']) ? '<tr><td class="label">Admin Notes:</td><td>' . htmlspecialchars($claim['admin_notes']) . '</td></tr>' : '') .
    '</table>
                </div>
            </div>
            <table style="margin-top:10px;">
                <tr><td class="label">Claimant Description:</td><td>' . htmlspecialchars($claim['item_description']) . '</td></tr>
                <tr><td class="label">Unique Identifiers:</td><td>' . htmlspecialchars($claim['unique_identifiers']) . '</td></tr>
            </table>
        </div>
    </div>

    <div class="signature-section">
        <div class="two-col">
            <div class="col">
                <div class="sig-line">Claimant Signature</div>
            </div>
            <div class="col">
                <div class="sig-line">Authorized Staff Signature</div>
            </div>
        </div>
        <br><br>
        <div class="two-col">
            <div class="col">
                <div class="sig-line">Date</div>
            </div>
            <div class="col">
                <div class="sig-line">Date</div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>This document was generated by the Lost &amp; Found Management System.</p>
        <p>Claim ID: ' . htmlspecialchars($claim['claim_id']) . ' &bull; Generated: ' . htmlspecialchars($currentDate) . '</p>
        <p><em>This receipt must be presented when collecting claimed items.</em></p>
    </div>
</body>
</html>';

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', false);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output as download
$filename = 'Claim_Receipt_' . $claim['claim_id'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
