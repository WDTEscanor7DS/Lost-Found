<?php

/**
 * Generate PDF Claim Confirmation for Match Flow.
 * Uses DomPDF to create a professional, printable document.
 * Secured via claim_token — no direct access without valid token.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// ── Validate token ─────────────────────────────────────────────────────
$token = trim($_GET['token'] ?? '');
if (empty($token) || strlen($token) > 128) {
    http_response_code(403);
    die('Access denied. Invalid or missing claim token.');
}

// ── Look up match by token ─────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT m.*, m.lost_item_id, m.found_item_id, m.match_score, m.status, m.claim_token
    FROM matches m
    WHERE m.claim_token = ?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$match) {
    http_response_code(404);
    die('Claim not found. This link may have expired or been processed.');
}

// Only allow PDF for confirmed matches
if (!in_array($match['status'], ['user_confirmed', 'confirmed'])) {
    http_response_code(403);
    die('PDF is only available for confirmed matches.');
}

$lost_id  = (int) $match['lost_item_id'];
$found_id = (int) $match['found_item_id'];

// ── Fetch lost item + owner details ────────────────────────────────────
$stmt = $conn->prepare("
    SELECT i.*, u.fullname
    FROM items i
    LEFT JOIN users u ON i.user_id = u.id
    WHERE i.id = ?
");
$stmt->bind_param("i", $lost_id);
$stmt->execute();
$lostItem = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ── Fetch found item details ───────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $found_id);
$stmt->execute();
$foundItem = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$lostItem || !$foundItem) {
    die('Item data not found.');
}

// ── Build data for PDF ─────────────────────────────────────────────────
$claimantName  = htmlspecialchars($lostItem['fullname'] ?? $lostItem['name'] ?? 'N/A');
$claimantEmail = htmlspecialchars($lostItem['email'] ?? 'N/A');
$matchId       = (int) $match['id'];
$matchScore    = (int) $match['match_score'];
$matchStatus   = $match['status'] === 'confirmed' ? 'Admin Approved' : 'User Confirmed (Pending Admin)';
$currentDate   = date('F j, Y');
$currentTime   = date('g:i A');
$claimRef      = 'MCH-' . str_pad($matchId, 6, '0', STR_PAD_LEFT);

// ── PDF HTML ───────────────────────────────────────────────────────────
$html = '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: "Helvetica", "Arial", sans-serif;
        font-size: 12px;
        color: #333;
        margin: 0;
        padding: 25px;
        line-height: 1.5;
    }
    .header {
        text-align: center;
        border-bottom: 3px solid #2c3e50;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    .header h1 {
        margin: 0;
        font-size: 22px;
        color: #2c3e50;
        letter-spacing: 1px;
    }
    .header h2 {
        margin: 5px 0 0;
        font-size: 15px;
        color: #555;
        font-weight: normal;
    }
    .header p {
        margin: 8px 0 0;
        color: #888;
        font-size: 10px;
    }
    .claim-ref-box {
        background: #f0f4f8;
        border: 2px solid #2c3e50;
        border-radius: 8px;
        padding: 12px;
        text-align: center;
        margin: 15px 0 25px;
    }
    .claim-ref-box .label {
        font-size: 10px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .claim-ref-box .value {
        font-size: 20px;
        font-weight: bold;
        color: #2c3e50;
        margin-top: 4px;
    }
    .claim-ref-box .status {
        margin-top: 6px;
        font-size: 11px;
    }
    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 4px;
        font-weight: bold;
        font-size: 10px;
        text-transform: uppercase;
        background: #d4edda;
        color: #155724;
    }
    .section { margin: 20px 0; }
    .section-title {
        background: #2c3e50;
        color: white;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: bold;
        margin-bottom: 0;
    }
    .section-body {
        border: 1px solid #ddd;
        border-top: none;
        padding: 12px 15px;
    }
    table { width: 100%; border-collapse: collapse; }
    table td {
        padding: 5px 8px;
        vertical-align: top;
        font-size: 11px;
    }
    table td.lbl {
        font-weight: bold;
        width: 35%;
        color: #555;
    }
    .instructions-section {
        margin: 25px 0;
        border: 1px solid #ddd;
        border-radius: 6px;
        overflow: hidden;
    }
    .instructions-section .inst-title {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 10px 15px;
        font-weight: bold;
        font-size: 13px;
        border-bottom: 1px solid #c8e6c9;
    }
    .instructions-section .inst-body {
        padding: 15px;
    }
    .step {
        margin-bottom: 10px;
        padding-left: 10px;
    }
    .step-num {
        display: inline-block;
        width: 22px;
        height: 22px;
        background: #2c3e50;
        color: white;
        border-radius: 50%;
        text-align: center;
        line-height: 22px;
        font-size: 10px;
        font-weight: bold;
        margin-right: 8px;
    }
    .step-text {
        font-size: 11px;
        color: #444;
    }
    .signature-section {
        margin-top: 40px;
        page-break-inside: avoid;
    }
    .sig-row {
        overflow: hidden;
        margin-bottom: 15px;
    }
    .sig-col {
        float: left;
        width: 48%;
    }
    .sig-col:last-child { float: right; }
    .sig-line {
        border-top: 1px solid #333;
        width: 100%;
        margin-top: 45px;
        padding-top: 5px;
        font-size: 10px;
        color: #666;
    }
    .footer {
        margin-top: 30px;
        padding-top: 12px;
        border-top: 2px solid #2c3e50;
        text-align: center;
        font-size: 9px;
        color: #888;
    }
    .footer p { margin: 2px 0; }
    .watermark {
        position: fixed;
        top: 45%;
        left: 15%;
        font-size: 60px;
        color: rgba(0,0,0,0.04);
        transform: rotate(-30deg);
        font-weight: bold;
        letter-spacing: 5px;
        z-index: 0;
    }
</style>
</head>
<body>

<div class="watermark">LOST &amp; FOUND</div>

<div class="header">
    <h1>LOST &amp; FOUND SYSTEM</h1>
    <h2>Claim Confirmation Document</h2>
    <p>Official Document &mdash; Generated on ' . $currentDate . ' at ' . $currentTime . '</p>
</div>

<div class="claim-ref-box">
    <div class="label">Claim Reference Number</div>
    <div class="value">' . $claimRef . '</div>
    <div class="status"><span class="status-badge">' . htmlspecialchars($matchStatus) . '</span></div>
</div>

<div class="section">
    <div class="section-title">CLAIMANT INFORMATION</div>
    <div class="section-body">
        <table>
            <tr><td class="lbl">Full Name:</td><td>' . $claimantName . '</td></tr>
            <tr><td class="lbl">Email Address:</td><td>' . $claimantEmail . '</td></tr>
            <tr><td class="lbl">Date of Claim:</td><td>' . $currentDate . '</td></tr>
            <tr><td class="lbl">Time of Claim:</td><td>' . $currentTime . '</td></tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">ITEM INFORMATION</div>
    <div class="section-body">
        <table>
            <tr><td class="lbl">Item Name:</td><td>' . htmlspecialchars($foundItem['name']) . '</td></tr>
            <tr><td class="lbl">Category:</td><td>' . htmlspecialchars($foundItem['category']) . '</td></tr>
            <tr><td class="lbl">Color:</td><td>' . htmlspecialchars($foundItem['color']) . '</td></tr>
            <tr><td class="lbl">Location Found:</td><td>' . htmlspecialchars($foundItem['location']) . '</td></tr>
            <tr><td class="lbl">Description:</td><td>' . htmlspecialchars($foundItem['description']) . '</td></tr>
        </table>
    </div>
</div>

<div class="section">
    <div class="section-title">LOST ITEM REPORTED</div>
    <div class="section-body">
        <table>
            <tr><td class="lbl">Reported Item:</td><td>' . htmlspecialchars($lostItem['name']) . '</td></tr>
            <tr><td class="lbl">Description:</td><td>' . htmlspecialchars($lostItem['description']) . '</td></tr>
            <tr><td class="lbl">Location Lost:</td><td>' . htmlspecialchars($lostItem['location']) . '</td></tr>
            <tr><td class="lbl">Match Score:</td><td>' . $matchScore . '%</td></tr>
            <tr><td class="lbl">Approval Status:</td><td>' . htmlspecialchars($matchStatus) . '</td></tr>
        </table>
    </div>
</div>

<div class="instructions-section">
    <div class="inst-title">CLAIM PROCESS INSTRUCTIONS</div>
    <div class="inst-body">
        <div class="step">
            <span class="step-num">1</span>
            <span class="step-text">Go to the <strong>Lost &amp; Found Office</strong> during office hours</span>
        </div>
        <div class="step">
            <span class="step-num">2</span>
            <span class="step-text">Present a <strong>valid government-issued ID</strong> (school ID, driver&#039;s license, passport, etc.)</span>
        </div>
        <div class="step">
            <span class="step-num">3</span>
            <span class="step-text">Show this <strong>Claim Confirmation Document</strong> (printed or digital copy)</span>
        </div>
        <div class="step">
            <span class="step-num">4</span>
            <span class="step-text"><strong>Verify the item details</strong> with the office staff to confirm ownership</span>
        </div>
        <div class="step">
            <span class="step-num">5</span>
            <span class="step-text"><strong>Sign the claim log</strong> and collect your item</span>
        </div>
    </div>
</div>

<div class="signature-section">
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line">Claimant Signature</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Authorized Staff Signature</div>
        </div>
    </div>
    <div class="sig-row">
        <div class="sig-col">
            <div class="sig-line">Date</div>
        </div>
        <div class="sig-col">
            <div class="sig-line">Date</div>
        </div>
    </div>
</div>

<div class="footer">
    <p><strong>Lost &amp; Found Management System</strong></p>
    <p>Claim Reference: ' . $claimRef . ' &bull; Generated: ' . $currentDate . ' ' . $currentTime . '</p>
    <p><em>This document must be presented when collecting claimed items. Valid for 3 days from admin approval.</em></p>
    <p>This is a system-generated document and does not require a stamp to be valid.</p>
</div>

</body>
</html>';

// ── Generate PDF with DomPDF ───────────────────────────────────────────
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
$filename = 'Claim_Confirmation_' . $claimRef . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
