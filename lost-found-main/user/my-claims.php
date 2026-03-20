<?php

/**
 * User: My Claims
 * View submitted claims and their status, download PDF for approved claims.
 */
$pageTitle = 'My Claims';
$activePage = 'my-claims';
include __DIR__ . '/includes/header.php';

$userId = (int) ($_SESSION['id'] ?? 0);

// Fetch user's claims
$stmt = $conn->prepare("
    SELECT c.*, i.name as item_name, i.category as item_category, i.color as item_color,
           i.location as item_location, i.image as item_image
    FROM claims c
    JOIN items i ON c.item_id = i.id
    WHERE c.user_id = ?
    ORDER BY c.date_claimed DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$claims = $stmt->get_result();
$stmt->close();
?>

<h3 class="mb-4">My Claims</h3>

<?php if ($claims->num_rows === 0): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-x fs-1 text-muted mb-3"></i>
            <h5 class="text-muted">No claims submitted yet</h5>
            <p class="text-muted">When you submit a claim for an item, it will appear here.</p>
        </div>
    </div>
<?php else: ?>
    <?php while ($claim = $claims->fetch_assoc()):
        $statusBadges = [
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'under_review' => '<span class="badge bg-info">Under Review</span>',
            'approved' => '<span class="badge bg-success">Approved</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>',
        ];
        $statusBadge = $statusBadges[$claim['status']] ?? '<span class="badge bg-secondary">Unknown</span>';
        $borderColors = [
            'approved' => 'success',
            'rejected' => 'danger',
            'under_review' => 'info',
        ];
        $borderColor = $borderColors[$claim['status']] ?? 'warning';
    ?>
        <div class="card mb-3 border-<?php echo $borderColor; ?>">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($claim['claim_id']); ?></h6>
                    <small class="text-muted">Submitted <?php echo date('M d, Y g:i A', strtotime($claim['date_claimed'])); ?></small>
                </div>
                <div>
                    <span class="badge bg-secondary me-1">Score: <?php echo (int)$claim['confidence_score']; ?>%</span>
                    <?php echo $statusBadge; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <th width="140">Item:</th>
                                <td><?php echo htmlspecialchars($claim['item_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Category:</th>
                                <td><?php echo htmlspecialchars($claim['item_category']); ?></td>
                            </tr>
                            <tr>
                                <th>Color:</th>
                                <td><?php echo htmlspecialchars($claim['item_color']); ?></td>
                            </tr>
                            <tr>
                                <th>Location:</th>
                                <td><?php echo htmlspecialchars($claim['item_location']); ?></td>
                            </tr>
                        </table>

                        <!-- Status tracker -->
                        <div class="mt-3">
                            <div class="d-flex align-items-center">
                                <?php
                                $steps = ['pending' => 'Submitted', 'under_review' => 'Under Review', 'approved' => 'Approved'];
                                $currentStep = $claim['status'];
                                $reached = true;
                                foreach ($steps as $key => $label):
                                    $isActive = ($key === $currentStep);
                                    $isDone = $reached && !$isActive && $currentStep !== 'rejected';
                                    if ($isActive) $reached = false;
                                ?>
                                    <div class="text-center flex-fill">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center
                                            <?php echo $isDone ? 'bg-success' : ($isActive ? 'bg-primary' : 'bg-light'); ?>"
                                            style="width:32px;height:32px;">
                                            <?php if ($isDone): ?>
                                                <i class="bi bi-check text-white"></i>
                                            <?php elseif ($currentStep === 'rejected' && $key === 'approved'): ?>
                                                <i class="bi bi-x text-danger"></i>
                                            <?php else: ?>
                                                <i class="bi bi-circle<?php echo $isActive ? '-fill text-white' : ' text-muted'; ?>" style="font-size:10px;"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="small <?php echo $isActive ? 'fw-bold' : 'text-muted'; ?>"><?php echo $label; ?></div>
                                    </div>
                                    <?php if ($key !== 'approved'): ?>
                                        <div class="flex-fill" style="height:2px;background:<?php echo $isDone ? '#198754' : '#dee2e6'; ?>;margin-top:-18px;"></div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if ($claim['status'] === 'rejected' && !empty($claim['admin_notes'])): ?>
                            <div class="alert alert-danger mt-3 mb-0">
                                <strong>Rejection reason:</strong> <?php echo htmlspecialchars($claim['admin_notes']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-center">
                        <?php if (!empty($claim['item_image']) && file_exists(__DIR__ . '/../uploads/' . $claim['item_image'])): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($claim['item_image']); ?>" class="img-fluid rounded" style="max-height:120px;" alt="Item">
                        <?php endif; ?>

                        <?php if ($claim['status'] === 'approved'): ?>
                            <div class="mt-3">
                                <a href="../backend/generate_pdf.php?claim_id=<?php echo (int)$claim['id']; ?>" class="btn btn-primary" target="_blank">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Download Receipt
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>