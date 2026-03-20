<?php

/**
 * User: Claim Item Form
 * Allows users to submit a claim for a found item with verification details.
 * Accessed via: claim-item.php?id=<item_id>
 */
$pageTitle = 'Claim Item';
$activePage = 'claim-item';
include __DIR__ . '/includes/header.php';

$itemId = (int) ($_GET['id'] ?? 0);
if ($itemId <= 0) {
    echo '<div class="alert alert-danger">Invalid item ID.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch the item (only found items with open/approved status can be claimed)
$stmt = $conn->prepare("SELECT * FROM items WHERE id = ? AND type = 'found' AND status = 'open' AND verification_status = 'approved'");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$item) {
    echo '<div class="alert alert-warning">This item is not available for claiming. It may have already been claimed or is not verified yet.</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Check if user already has a pending claim for this item
$userId = (int) $_SESSION['id'];
$stmt = $conn->prepare("SELECT id, status FROM claims WHERE item_id = ? AND user_id = ? AND status IN ('pending', 'under_review')");
$stmt->bind_param("ii", $itemId, $userId);
$stmt->execute();
$existingClaim = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get user info for pre-filling
$stmt = $conn->prepare("SELECT fullname, username FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<h3 class="mb-1">Claim Found Item</h3>
<p class="text-muted mb-4">Submit a claim to prove this item belongs to you</p>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Claim submitted successfully!</strong> Your claim will be reviewed by an administrator. You can track it in <a href="my-claims.php">My Claims</a>.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?php
        $errors = [
            '1' => 'Please fill in all required fields.',
            '2' => 'Invalid file type. Allowed: JPG, PNG, PDF.',
            '3' => 'File too large. Maximum size is 5MB.',
            '4' => 'Failed to upload file. Please try again.',
            '5' => 'You already have a pending claim for this item.',
        ];
        echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred.');
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($existingClaim): ?>
    <div class="alert alert-info">
        You already have a <strong><?php echo htmlspecialchars($existingClaim['status']); ?></strong> claim for this item.
        <a href="my-claims.php" class="alert-link">View your claims</a>.
    </div>
<?php else: ?>

    <!-- Item Preview -->
    <div class="card mb-4">
        <div class="card-header bg-success bg-opacity-10">
            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Item Details (Found Item #<?php echo $itemId; ?>)</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <th width="150">Item Name:</th>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                        </tr>
                        <tr>
                            <th>Category:</th>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                        </tr>
                        <tr>
                            <th>Color:</th>
                            <td><?php echo htmlspecialchars($item['color']); ?></td>
                        </tr>
                        <tr>
                            <th>Location Found:</th>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                        </tr>
                        <tr>
                            <th>Date Reported:</th>
                            <td><?php echo date('M d, Y', strtotime($item['date_created'])); ?></td>
                        </tr>
                    </table>
                </div>
                <?php if (!empty($item['image']) && file_exists(__DIR__ . '/../uploads/' . $item['image'])): ?>
                    <div class="col-md-4 text-center">
                        <img src="../uploads/<?php echo htmlspecialchars($item['image']); ?>" class="img-fluid rounded" style="max-height:180px;" alt="Item photo">
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Claim Form -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-shield-check me-2"></i>Claim Verification Form</h5>
            <small class="text-muted">Provide as much detail as possible to verify ownership. The more accurate your description, the higher your verification score.</small>
        </div>
        <div class="card-body">
            <form action="../backend/submit_claim.php" method="POST" enctype="multipart/form-data" id="claimForm">
                <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">

                <div class="row g-3">
                    <!-- Claimant Info -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="claimant_name"
                            value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="claimant_email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone Number</label>
                        <input type="text" class="form-control" name="claimant_phone" placeholder="Optional">
                    </div>

                    <div class="col-12">
                        <hr>
                    </div>

                    <!-- Item Description (for matching) -->
                    <div class="col-12">
                        <label class="form-label">Describe the Item in Detail <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="item_description" rows="4" required
                            placeholder="Describe the item in as much detail as you remember: name, brand, model, color, size, contents, any markings or labels..."></textarea>
                        <small class="text-muted">This will be compared against the stored item data to calculate a match score.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Unique Identifiers <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="unique_identifiers" rows="3" required
                            placeholder="Describe unique features only the owner would know: scratches, stickers, dents, specific contents, serial numbers, custom markings, etc."></textarea>
                        <small class="text-muted">The more specific you are, the higher your verification score.</small>
                    </div>

                    <div class="col-12">
                        <hr>
                    </div>

                    <!-- Document Uploads -->
                    <div class="col-12">
                        <h6><i class="bi bi-file-earmark-arrow-up me-1"></i> Document Verification</h6>
                        <p class="text-muted small">Upload supporting documents to strengthen your claim. Accepted: JPG, PNG, PDF (max 5MB each).</p>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Proof Photo (of item)</label>
                        <input type="file" class="form-control" name="proof_image" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Photo of the item you're claiming</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Valid ID</label>
                        <input type="file" class="form-control" name="id_document" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Government ID, student ID, etc.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Proof of Ownership</label>
                        <input type="file" class="form-control" name="proof_document" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Receipt, screenshot, warranty card, etc.</small>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-send me-1"></i> Submit Claim
                    </button>
                    <a href="track-status.php" class="btn btn-outline-secondary btn-lg ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>