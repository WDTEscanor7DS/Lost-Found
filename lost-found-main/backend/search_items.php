<?php

/**
 * Backend: AJAX Search API for All Items
 * Returns JSON results for dynamic search/filter in admin/all-items.php
 */
session_start();
require_once __DIR__ . '/../auth/guard_admin_api.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$where = ["verification_status = 'approved'"];
$params = [];
$types = '';

// Filter: type
if (!empty($_GET['type']) && in_array($_GET['type'], ['lost', 'found'], true)) {
    $where[] = "type = ?";
    $params[] = $_GET['type'];
    $types .= 's';
}

// Filter: category
if (!empty($_GET['category'])) {
    $where[] = "category = ?";
    $params[] = $_GET['category'];
    $types .= 's';
}

// Filter: status
if (!empty($_GET['status']) && in_array($_GET['status'], ['open', 'matched', 'claimed'], true)) {
    $where[] = "status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
}

// Search: case-insensitive across name, description, location, category, color
if (!empty($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    $where[] = "(name LIKE ? OR description LIKE ? OR location LIKE ? OR category LIKE ? OR color LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $types .= 'sssss';
}

$sql = "SELECT id, type, name, category, color, location, status, description, date_created FROM items WHERE " . implode(' AND ', $where) . " ORDER BY date_created DESC LIMIT 200";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $row['description'] = mb_strimwidth($row['description'] ?? '', 0, 50, '...');
    $row['date_formatted'] = date('M d, Y', strtotime($row['date_created']));
    $items[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'items' => $items, 'count' => count($items)]);
