<?php

/**
 * Admin: Claim Requests
 * View all claims, see match scores, approve/reject.
 */
require_once __DIR__ . '/../auth/guard_admin.php';
require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/claim_helpers.php';

$pageTitle = 'Claim Requests';
$activePage = 'claim-requests';

// Fetch all claims with item and user data
$statusFilter = $_GET['status'] ?? '';
$where = "1=1";
$params = [];
$types = '';

if ($statusFilter !== '' && in_array($statusFilter, ['pending', 'under_review', 'approved', 'rejected'], true)) {
    $where .= " AND c.status = ?";
    $params[] = $statusFilter;
    $types .= 's';
}

$sql = "
    SELECT c.*, i.name as item_name, i.category as item_category, i.color as item_color,
           i.location as item_location, i.description as item_description, i.image as item_image,
           i.id_type as item_id_type, i.id_number as item_id_number,
           u.fullname as user_fullname, u.username as user_username
    FROM claims c
    JOIN items i ON c.item_id = i.id
    JOIN users u ON c.user_id = u.id
    WHERE {$where}
    ORDER BY
        CASE c.status
            WHEN 'pending' THEN 1
            WHEN 'under_review' THEN 2
            WHEN 'approved' THEN 3
            WHEN 'rejected' THEN 4
        END,
        c.date_claimed DESC
";
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$claims = $stmt->get_result();
$stmt->close();

// Count by status
$counts = [];
$countResult = $conn->query("SELECT status, COUNT(*) as c FROM claims GROUP BY status");
while ($r = $countResult->fetch_assoc()) {
    $counts[$r['status']] = (int) $r['c'];
}
$totalClaims = array_sum($counts);

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Claim Requests</h3>
<p class="text-muted mb-4">Review and manage item ownership claims</p>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-<?php echo match ($_GET['msg']) {
                                'approved' => 'success',
                                'rejected' => 'danger',
                                'reviewing' => 'warning',
                                default => 'info'
                            }; ?> alert-dismissible fade show">
        <?php echo match ($_GET['msg']) {
            'approved' => 'Claim has been approved. The claimant has been notified.',
            'rejected' => 'Claim has been rejected. The claimant has been notified.',
            'reviewing' => 'Claim marked as under review.',
            default => 'Action completed.'
        }; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Status Summary -->
<div class="row gy-3 mb-4">
    <div class="col-lg-3 col-sm-6">
        <a href="?status=pending" class="text-decoration-none">
            <div class="card h-100 <?php echo $statusFilter === 'pending' ? 'border-warning' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="avatar-initial bg-warning rounded shadow-xs"><i class="bi bi-clock"></i></div>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0">Pending</p>
                            <h5 class="mb-0"><?php echo $counts['pending'] ?? 0; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="?status=under_review" class="text-decoration-none">
            <div class="card h-100 <?php echo $statusFilter === 'under_review' ? 'border-info' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="avatar-initial bg-info rounded shadow-xs"><i class="bi bi-search"></i></div>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0">Under Review</p>
                            <h5 class="mb-0"><?php echo $counts['under_review'] ?? 0; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="?status=approved" class="text-decoration-none">
            <div class="card h-100 <?php echo $statusFilter === 'approved' ? 'border-success' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="avatar-initial bg-success rounded shadow-xs"><i class="bi bi-check-circle"></i></div>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0">Approved</p>
                            <h5 class="mb-0"><?php echo $counts['approved'] ?? 0; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-3 col-sm-6">
        <a href="?status=rejected" class="text-decoration-none">
            <div class="card h-100 <?php echo $statusFilter === 'rejected' ? 'border-danger' : ''; ?>">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar">
                            <div class="avatar-initial bg-danger rounded shadow-xs"><i class="bi bi-x-circle"></i></div>
                        </div>
                        <div class="ms-3">
                            <p class="mb-0">Rejected</p>
                            <h5 class="mb-0"><?php echo $counts['rejected'] ?? 0; ?></h5>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="mb-3">
    <a href="claim-requests.php" class="btn btn-sm <?php echo $statusFilter === '' ? 'btn-primary' : 'btn-outline-primary'; ?>">All (<?php echo $totalClaims; ?>)</a>
</div>

<?php if ($claims->num_rows === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <p class="text-muted mb-0">No claim requests found.</p>
        </div>
    </div>
<?php else: ?>
    <?php while ($claim = $claims->fetch_assoc()):
        $item = [
            'name' => $claim['item_name'],
            'category' => $claim['item_category'],
            'color' => $claim['item_color'],
            'location' => $claim['item_location'],
            'description' => $claim['item_description'],
        ];
        $claimData = [
            'item_description' => $claim['item_description'],
            'unique_identifiers' => $claim['unique_identifiers'],
            'proof_image' => $claim['proof_image'],
            'id_document' => $claim['id_document'],
            'proof_document' => $claim['proof_document'],
        ];
        $breakdown = getScoreBreakdown($item, $claimData);
        $score = (int) $claim['confidence_score'];

        $scoreClass = $score >= 70 ? 'bg-success' : ($score >= 40 ? 'bg-warning text-dark' : 'bg-danger');
        $statusBadge = match ($claim['status']) {
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'under_review' => '<span class="badge bg-info">Under Review</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
            default => '<span class="badge bg-secondary">Unknown</span>',
        };
    ?>
        <div class="card mb-4 border-<?php echo match ($claim['status']) {
                                            'pending' => 'warning',
                                            'under_review' => 'info',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        }; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Claim <?php echo htmlspecialchars($claim['claim_id']); ?></h5>
                    <small class="text-muted">Submitted <?php echo date('M d, Y g:i A', strtotime($claim['date_claimed'])); ?></small>
                </div>
                <div>
                    <span class="badge <?php echo $scoreClass; ?> me-1">Score: <?php echo $score; ?>%</span>
                    <?php echo $statusBadge; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <!-- Claimant Info -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-primary border-3 h-100">
                            <h6 class="text-primary mb-3"><i class="bi bi-person me-1"></i> Claimant Information</h6>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($claim['claimant_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($claim['claimant_email']); ?></p>
                            <?php if (!empty($claim['claimant_phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($claim['claimant_phone']); ?></p>
                            <?php endif; ?>
                            <p><strong>User Account:</strong> <?php echo htmlspecialchars($claim['user_fullname'] . ' (@' . $claim['user_username'] . ')'); ?></p>
                            <hr>
                            <p><strong>Their Description:</strong></p>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($claim['item_description'])); ?></p>
                            <p><strong>Unique Identifiers:</strong></p>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($claim['unique_identifiers'])); ?></p>
                        </div>
                    </div>

                    <!-- Stored Item Info -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded border-start border-success border-3 h-100">
                            <h6 class="text-success mb-3"><i class="bi bi-box-seam me-1"></i> Stored Item Data</h6>
                            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($claim['item_name']); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($claim['item_category']); ?></p>
                            <p><strong>Color:</strong> <?php echo htmlspecialchars($claim['item_color']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($claim['item_location']); ?></p>
                            <p><strong>Description:</strong></p>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($claim['item_description'])); ?></p>
                            <?php if (!empty($claim['item_image']) && file_exists(__DIR__ . '/../uploads/' . $claim['item_image'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($claim['item_image']); ?>" class="img-fluid rounded" style="max-height:150px;" alt="Item">
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Score Breakdown -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded">
                            <h6 class="mb-3"><i class="bi bi-bar-chart me-1"></i> Confidence Score Breakdown</h6>
                            <div class="row g-2">
                                <?php foreach ($breakdown as $key => $b): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="d-flex justify-content-between mb-1">
                                            <small><?php echo htmlspecialchars($b['label']); ?></small>
                                            <small class="fw-bold"><?php echo $b['score']; ?>/<?php echo $b['max']; ?></small>
                                        </div>
                                        <div class="progress" style="height:8px;">
                                            <div class="progress-bar <?php echo $b['score'] >= $b['max'] * 0.7 ? 'bg-success' : ($b['score'] >= $b['max'] * 0.4 ? 'bg-warning' : 'bg-danger'); ?>"
                                                style="width:<?php echo ($b['max'] > 0 ? round($b['score'] / $b['max'] * 100) : 0); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Uploaded Documents -->
                    <?php if (!empty($claim['proof_image']) || !empty($claim['id_document']) || !empty($claim['proof_document'])): ?>
                        <div class="col-12">
                            <div class="p-3 bg-light rounded">
                                <h6 class="mb-3"><i class="bi bi-file-earmark me-1"></i> Uploaded Documents</h6>
                                <div class="row g-3">
                                    <?php
                                    $docs = [
                                        'proof_image' => ['label' => 'Proof Photo', 'icon' => 'bi-image'],
                                        'id_document' => ['label' => 'ID Document', 'icon' => 'bi-person-badge'],
                                        'proof_document' => ['label' => 'Proof of Ownership', 'icon' => 'bi-file-earmark-text'],
                                    ];
                                    foreach ($docs as $field => $info):
                                        if (empty($claim[$field])) continue;
                                        $filePath = '../uploads/claims/' . htmlspecialchars($claim[$field]);
                                        $ext = strtolower(pathinfo($claim[$field], PATHINFO_EXTENSION));
                                    ?>
                                        <div class="col-md-4">
                                            <div class="card">
                                                <div class="card-body text-center p-3">
                                                    <i class="bi <?php echo $info['icon']; ?> fs-3 mb-2"></i>
                                                    <p class="mb-2 fw-bold"><?php echo $info['label']; ?></p>
                                                    <?php if (in_array($ext, ['jpg', 'jpeg', 'png'])): ?>
                                                        <img src="<?php echo $filePath; ?>" class="img-fluid rounded mb-2" style="max-height:120px;" alt="<?php echo $info['label']; ?>">
                                                    <?php else: ?>
                                                        <p class="text-muted small">PDF Document</p>
                                                    <?php endif; ?>
                                                    <br>
                                                    <a href="<?php echo $filePath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Action Buttons -->
                <?php if ($claim['status'] === 'pending' || $claim['status'] === 'under_review'): ?>
                    <div class="mt-4 pt-3 border-top">
                        <div class="row align-items-end">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Admin Notes (optional)</label>
                                <textarea class="form-control" id="notes_<?php echo (int)$claim['id']; ?>" rows="2" placeholder="Add notes about this claim..."></textarea>
                            </div>
                            <div class="col-md-6 mb-3 text-end">
                                <?php if ($claim['status'] === 'pending'): ?>
                                    <form method="POST" action="../backend/process_claim.php" class="d-inline">
                                        <input type="hidden" name="claim_db_id" value="<?php echo (int)$claim['id']; ?>">
                                        <input type="hidden" name="action" value="under_review">
                                        <input type="hidden" name="admin_notes" value="">
                                        <button type="submit" class="btn btn-info me-1"
                                            onclick="this.form.admin_notes.value=document.getElementById('notes_<?php echo (int)$claim['id']; ?>').value">
                                            <i class="bi bi-search me-1"></i> Mark Under Review
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($score >= 40): ?>
                                    <form method="POST" action="../backend/process_claim.php" class="d-inline">
                                        <input type="hidden" name="claim_db_id" value="<?php echo (int)$claim['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="admin_notes" value="">
                                        <button type="submit" class="btn btn-success me-1"
                                            onclick="this.form.admin_notes.value=document.getElementById('notes_<?php echo (int)$claim['id']; ?>').value; return confirm('Approve this claim? The item will be marked as claimed.')">
                                            <i class="bi bi-check-circle me-1"></i> Approve
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-success me-1" disabled title="Score too low for approval (minimum 40%)">
                                        <i class="bi bi-check-circle me-1"></i> Approve (Score too low)
                                    </button>
                                <?php endif; ?>

                                <form method="POST" action="../backend/process_claim.php" class="d-inline">
                                    <input type="hidden" name="claim_db_id" value="<?php echo (int)$claim['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="admin_notes" value="">
                                    <button type="submit" class="btn btn-danger"
                                        onclick="this.form.admin_notes.value=document.getElementById('notes_<?php echo (int)$claim['id']; ?>').value; return confirm('Reject this claim?')">
                                        <i class="bi bi-x-circle me-1"></i> Reject
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php elseif (!empty($claim['admin_notes'])): ?>
                    <div class="mt-3 pt-3 border-top">
                        <p class="text-muted"><strong>Admin Notes:</strong> <?php echo htmlspecialchars($claim['admin_notes']); ?></p>
                    </div>
                <?php endif; ?>

                <!-- PDF Download for approved claims -->
                <?php if ($claim['status'] === 'approved'): ?>
                    <div class="mt-3 pt-3 border-top text-end">
                        <a href="../backend/generate_pdf.php?claim_id=<?php echo (int)$claim['id']; ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-file-earmark-pdf me-1"></i> Download Claim Receipt PDF
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>