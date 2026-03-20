<?php
require_once __DIR__ . '/../auth/guard_admin.php';

/**
 * Admin: Detected Matches page.
 * Uses the matching algorithm from backend/matcher.php to display potential matches.
 */
$pageTitle = 'Detected Matches';
$activePage = 'matches';

require_once __DIR__ . '/../backend/config.php';
require_once __DIR__ . '/../backend/matcher.php';

// Handle claim match
$notification = '';
$notificationType = '';
$just_claimed = false;
$claimed_lost_id = 0;
$claimed_found_id = 0;

if (isset($_POST['claim_match'])) {
    $lost_id = (int)$_POST['lost_id'];
    $found_id = (int)$_POST['found_id'];
    $score = (int)$_POST['score'];
    $just_claimed = true;
    $claimed_lost_id = $lost_id;
    $claimed_found_id = $found_id;

    $result = claimMatch($conn, $lost_id, $found_id, $score);
    $notification = $result['message'];
    $notificationType = $result['success'] ? 'success' : 'danger';
}

// Handle counter match
if (isset($_POST['counter_match'])) {
    $lost_id = (int)$_POST['lost_id'];
    $found_id = (int)$_POST['found_id'];

    if (counterMatch($conn, $lost_id, $found_id)) {
        $notification = 'Not a match. This pair will not be shown again.';
        $notificationType = 'danger';
    }
}

// Find all matches
$matches = findMatches($conn);

include __DIR__ . '/includes/header.php';
?>

<h3 class="mb-1">Detected Matches</h3>

<?php if (!empty($notification)): ?>
    <div class="alert alert-<?php echo $notificationType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($notification); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (empty($matches)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h5 class="text-muted">No potential matches found at this time.</h5>
            <p class="text-muted">Check back later as new items are submitted.</p>
        </div>
    </div>
<?php else: ?>

    <?php foreach ($matches as $m):
        $lost = $m['lost'];
        $found = $m['found'];
        $finalScore = $m['score'];
        $isClaimed = ($just_claimed && $lost['id'] == $claimed_lost_id && $found['id'] == $claimed_found_id);
    ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($lost['name']); ?> &#8596; <?php echo htmlspecialchars($found['name']); ?></h5>
                <span class="badge bg-<?php echo $finalScore >= 80 ? 'success' : ($finalScore >= 60 ? 'warning' : 'secondary'); ?> fs-6">
                    Score: <?php echo $finalScore; ?>%
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-danger">Lost Item</h6>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($lost['category']); ?></p>
                        <p><strong>Color:</strong> <?php echo htmlspecialchars($lost['color']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($lost['location']); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($lost['description'])); ?></p>
                        <?php if (strtolower($lost['category']) === 'id' && !empty($lost['id_type'])): ?>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($lost['id_type']);
                                                    if (!empty($lost['id_number'])) echo ' #' . htmlspecialchars($lost['id_number']);
                                                    if (!empty($lost['id_issuer'])) echo ' (' . htmlspecialchars($lost['id_issuer']) . ')';
                                                    ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Found Item</h6>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($found['category']); ?></p>
                        <p><strong>Color:</strong> <?php echo htmlspecialchars($found['color']); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($found['location']); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($found['description'])); ?></p>
                        <?php if (strtolower($found['category']) === 'id' && !empty($found['id_type'])): ?>
                            <p><strong>ID:</strong> <?php echo htmlspecialchars($found['id_type']);
                                                    if (!empty($found['id_number'])) echo ' #' . htmlspecialchars($found['id_number']);
                                                    if (!empty($found['id_issuer'])) echo ' (' . htmlspecialchars($found['id_issuer']) . ')';
                                                    ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mt-3">
                    <?php if ($isClaimed): ?>
                        <span class="btn btn-success disabled"><i class="ri ri-check-line"></i> Item Claimed Successfully</span>
                    <?php else: ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="lost_id" value="<?php echo (int)$lost['id']; ?>">
                            <input type="hidden" name="found_id" value="<?php echo (int)$found['id']; ?>">
                            <input type="hidden" name="score" value="<?php echo $finalScore; ?>">
                            <button type="submit" name="claim_match" class="btn btn-success me-2">
                                <i class="ri ri-check-line"></i> Item Matched
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="lost_id" value="<?php echo (int)$lost['id']; ?>">
                            <input type="hidden" name="found_id" value="<?php echo (int)$found['id']; ?>">
                            <button type="submit" name="counter_match" class="btn btn-danger">
                                <i class="ri ri-close-line"></i> Not a Match
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>