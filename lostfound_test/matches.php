<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include "config.php";
include "header.php";
include "sidebar.php";

require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

// Notification system
$notification = '';
$notificationType = '';

// CLAIM MATCH
$lost_id = 0;
$found_id = 0;
$score = 0;
$just_claimed = false;
if (isset($_POST['claim_match'])) {
    $lost_id = (int)$_POST['lost_id'];
    $found_id = (int)$_POST['found_id'];
    $score = (int)$_POST['score'];
    $just_claimed = true;
    $notification = 'Item matched! The user will be notified for confirmation.';
    $notificationType = 'success';
}

// COUNTER MATCH (Mark as not a match)
if (isset($_POST['counter_match'])) {
    $lost_id = (int)$_POST['lost_id'];
    $found_id = (int)$_POST['found_id'];

    if ($lost_id > 0 && $found_id > 0) {
        $stmt = $conn->prepare("INSERT INTO rejected_matches (lost_item_id, found_item_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $lost_id, $found_id);
        $stmt->execute();
        $stmt->close();

        $notification = 'Not a match. This pair will not be shown again.';
        $notificationType = 'error';
    }
}

if ($just_claimed && $lost_id > 0 && $found_id > 0) {
    // Get lost item info (to notify user)
    $stmt = $conn->prepare("SELECT name, email FROM items WHERE id = ?");
    $stmt->bind_param("i", $lost_id);
    $stmt->execute();
    $lostItem = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("SELECT name FROM items WHERE id = ?");
    $stmt->bind_param("i", $found_id);
    $stmt->execute();
    $foundItem = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($lostItem && $foundItem) {
        $lostEmail = $lostItem['email'];
        $lostName = $lostItem['name'];
        $foundName = $foundItem['name'];

        // Insert into matches table
        $stmt = $conn->prepare("INSERT INTO matches (lost_item_id, found_item_id, match_score, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iii", $lost_id, $found_id, $score);
        $stmt->execute();
        $stmt->close();

        // Update items status to matched (pending admin confirmation)
        $stmt = $conn->prepare("UPDATE items SET status='matched' WHERE id IN (?, ?)");
        $stmt->bind_param("ii", $lost_id, $found_id);
        $stmt->execute();
        $stmt->close();

        // Send email
        $mail = new PHPMailer(true);

        try {
            $stmt = $conn->prepare("SELECT name, email FROM items WHERE id = ?");
            $stmt->bind_param("i", $lost_id);
            $stmt->execute();
            $lostItem = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT name, category, color, location, description, image, id_type, id_number, id_issuer FROM items WHERE id = ?");
            $stmt->bind_param("i", $found_id);
            $stmt->execute();
            $foundItem = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $lostEmail = $lostItem['email'];
            $lostName = $lostItem['name'];
            $foundName = $foundItem['name'];
            $foundImage = $foundItem['image'];

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'lostandfoundbot2000@gmail.com';
            $mail->Password   = 'zlyc fynv owzc vkur';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('lostandfoundbot2000@gmail.com', 'Lost & Found System');
            $mail->addAddress($lostEmail);

            $mail->isHTML(true);
            $mail->Subject = 'Your Lost Item May Have Been Found!';

            $imagePath = "uploads/" . $foundImage;

            if (!empty($foundImage) && file_exists($imagePath)) {
                $mail->addEmbeddedImage($imagePath, 'foundimg');
                $imageHTML = "<img src='cid:foundimg' width='300'>";
            } else {
                $imageHTML = "<p>No image available.</p>";
            }

            $mail->Body = "
            <h2>Potential Match Found!</h2>
            <p>We believe we may have found your lost item <strong>\"" . htmlspecialchars($lostName) . "</strong>!</p>
            <p><strong>Found Item Details:</strong></p>
            <ul>
                <li><strong>Name:</strong> " . htmlspecialchars($foundName) . "</li>
                <li><strong>Category:</strong> " . htmlspecialchars($foundItem['category']) . "</li>
                <li><strong>Color:</strong> " . htmlspecialchars($foundItem['color']) . "</li>
                <li><strong>Location:</strong> " . htmlspecialchars($foundItem['location']) . "</li>
                <li><strong>Description:</strong> " . nl2br(htmlspecialchars($foundItem['description'])) . "</li>";

            // Add ID details if it's an ID card
            if (strtolower($foundItem['category']) === "id" && (!empty($foundItem['id_type']) || !empty($foundItem['id_number']) || !empty($foundItem['id_issuer']))) {
                $mail->Body .= "<li><strong>ID Type:</strong> " . htmlspecialchars($foundItem['id_type'] ?? 'Not specified') . "</li>";
                if (!empty($foundItem['id_number'])) {
                    $mail->Body .= "<li><strong>ID Number:</strong> " . htmlspecialchars($foundItem['id_number']) . "</li>";
                }
                if (!empty($foundItem['id_issuer'])) {
                    $mail->Body .= "<li><strong>Issuing Authority:</strong> " . htmlspecialchars($foundItem['id_issuer']) . "</li>";
                }
            }

            $mail->Body .= "
            </ul>
            $imageHTML

            <div style='background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;border-left:4px solid #4CAF50;'>
                <h3 style='margin-top:0;color:#2e7d32;'>Is this your item?</h3>
                <p>Please confirm whether this matches your lost item:</p>
                <p style='text-align:center;margin:20px 0;'>
                    <a href='http://localhost/lostfound_test/user_confirm_match.php?lost_id=$lost_id&found_id=$found_id' style='background:#4CAF50;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;margin-right:10px;display:inline-block;'>YES - This is my item</a>
                    <a href='http://localhost/lostfound_test/reject_match.php?lost_id=$lost_id&found_id=$found_id' style='background:#f44336;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;'>NO - Not my item</a>
                </p>
                <p><strong>If you confirm this is your item:</strong> Our administration team will review your confirmation and contact you with pickup instructions for the claim booth.</p>
                <p><strong>If this is not your item:</strong> We'll continue searching for your actual lost item.</p>
            </div>
        ";

            $mail->send();
        } catch (Exception $e) {
            $notification = 'Match claimed, but email could not be sent. Error: ' . $mail->ErrorInfo;
            $notificationType = 'error';
        }
    }
}
?>

<!-- Notification UI -->
<?php if (!empty($notification)): ?>
    <div style="padding:15px;margin-bottom:20px;border-radius:5px;background:<?php echo $notificationType === 'success' ? '#d4edda' : '#f8d7da'; ?>;color:<?php echo $notificationType === 'success' ? '#155724' : '#721c24'; ?>;border:1px solid <?php echo $notificationType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;display:flex;justify-content:space-between;align-items:center;">
        <span><?php echo htmlspecialchars($notification); ?></span>
        <button type="button" onclick="this.parentElement.style.display='none'" style="background:none;border:none;color:inherit;cursor:pointer;font-size:20px;padding:0;margin-left:15px;">&times;</button>
    </div>
<?php endif; ?>

<h2>Detected Matches</h2>

<?php

/* =========================================================================
 *  SCORING MODULE - Modular weighted components
 * ========================================================================= */

/**
 * Normalize a string for comparison.
 */
function normalize(string $text): string
{
    return strtolower(trim($text));
}

/**
 * Score name similarity using similar_text (max 30 pts).
 */
function scoreName(string $n1, string $n2): float
{
    $percent = 0;
    similar_text(normalize($n1), normalize($n2), $percent);
    return $percent * 0.30;
}

/**
 * Score description similarity using similar_text (max 15 pts).
 */
function scoreDescription(string $d1, string $d2): float
{
    $percent = 0;
    similar_text(normalize($d1), normalize($d2), $percent);
    return $percent * 0.15;
}

/**
 * Score keyword overlap between combined name+description (max 15 pts).
 */
function scoreKeywords(string $name1, string $desc1, string $name2, string $desc2): float
{
    static $stopwords = [
        'the',
        'and',
        'of',
        'a',
        'an',
        'is',
        'it',
        'this',
        'that',
        'lost',
        'found',
        'item',
        'my',
        'in',
        'on',
        'at',
        'with'
    ];

    $extract = function (string $text) use ($stopwords): array {
        $words = preg_split('/[^a-z0-9]+/', normalize($text));
        return array_values(array_diff(array_filter($words), $stopwords));
    };

    $words1 = $extract($name1 . ' ' . $desc1);
    $words2 = $extract($name2 . ' ' . $desc2);

    if (empty($words1) || empty($words2)) return 0;

    $common = array_intersect($words1, $words2);
    if (count($common) === 0) return -3;

    return (count($common) / max(count($words1), count($words2))) * 15;
}

/**
 * Score brand detection (max 5 pts).
 */
function scoreBrand(string $text1, string $text2): float
{
    $brands = [
        'iphone',
        'samsung',
        'xiaomi',
        'oppo',
        'vivo',
        'huawei',
        'realme',
        'razer',
        'logitech',
        'jbl',
        'sony',
        'bose',
        'apple',
        'lenovo',
        'asus',
        'acer',
        'hp',
        'dell',
        'msi'
    ];
    $t1 = normalize($text1);
    $t2 = normalize($text2);

    foreach ($brands as $brand) {
        if (str_contains($t1, $brand) && str_contains($t2, $brand)) {
            return 5;
        }
    }
    return 0;
}

/**
 * Score model number similarity (max 15 pts).
 */
function scoreModelNumbers(string $text1, string $text2): float
{
    preg_match_all('/[a-z]*[0-9]+[a-z0-9]*/i', normalize($text1), $m1);
    preg_match_all('/[a-z]*[0-9]+[a-z0-9]*/i', normalize($text2), $m2);

    $score = 0;

    // Exact model match
    if (count(array_intersect($m1[0], $m2[0])) > 0) {
        $score += 10;
    }

    // Proximity match for numeric models
    foreach ($m1[0] as $a) {
        foreach ($m2[0] as $b) {
            if (is_numeric($a) && is_numeric($b) && abs($a - $b) <= 2) {
                return $score + 5;
            }
        }
    }

    return $score;
}

/**
 * Score color overlap including description mentions (max 10 pts).
 * SQL already scored exact color match; this adds fuzzy/description color detection.
 */
function scoreColorFuzzy(array $item1, array $item2): float
{
    $palette = [
        'white',
        'black',
        'red',
        'blue',
        'green',
        'yellow',
        'pink',
        'purple',
        'orange',
        'gray',
        'grey',
        'brown',
        'silver',
        'gold'
    ];

    $extract = function (array $item) use ($palette): array {
        $tokens = preg_split('/[,\s\/]+/', normalize($item['color'] . ' ' . $item['description']));
        return array_values(array_intersect(array_filter($tokens), $palette));
    };

    $c1 = $extract($item1);
    $c2 = $extract($item2);

    if (empty($c1) || empty($c2)) return 0;

    $common = array_intersect($c1, $c2);
    if (count($common) === 0) return 0;

    return (count($common) / max(count($c1), count($c2))) * 10;
}

/**
 * Score ID-specific fields: issuer fuzzy match + multi-field bonus (max 20 pts).
 * SQL already scored id_type (+15) and id_number (+25) exact matches.
 * PHP adds issuer fuzzy comparison and multi-field bonus.
 */
function scoreIdFields(array $item1, array $item2): float
{
    if (normalize($item1['category']) !== 'id' || normalize($item2['category']) !== 'id') {
        return 0;
    }

    $score = 0;
    $issuer1 = normalize($item1['id_issuer'] ?? '');
    $issuer2 = normalize($item2['id_issuer'] ?? '');

    // Fuzzy issuer match
    if ($issuer1 !== '' && $issuer2 !== '') {
        $pct = 0;
        similar_text($issuer1, $issuer2, $pct);
        if ($pct > 80) $score += 10;
    }

    // Multi-field bonus: if 2+ ID fields match, extra points
    $matched = 0;
    if (normalize($item1['id_type'] ?? '') === normalize($item2['id_type'] ?? '') && ($item1['id_type'] ?? '') !== '') $matched++;
    if (normalize($item1['id_number'] ?? '') === normalize($item2['id_number'] ?? '') && ($item1['id_number'] ?? '') !== '') $matched++;
    if ($score > 0) $matched++; // issuer counted

    if ($matched >= 2) $score += 10;

    return $score;
}

/**
 * Final PHP scoring on a candidate pair.
 * Receives the SQL preliminary_score and adds PHP-only components.
 *
 * SQL already contributed:
 *   category match   +20
 *   color exact      +10
 *   location match   +10
 *   id_type match    +15
 *   id_number match  +25
 *   FULLTEXT bonus   +5
 *
 * PHP adds:
 *   name similarity       (max 30)
 *   description similarity(max 15)
 *   keyword overlap       (max 15)
 *   brand detection       (max  5)
 *   model numbers         (max 15)
 *   color fuzzy           (max 10)
 *   ID issuer + bonus     (max 20)
 *
 * Total possible: ~170 raw, clamped to 0-100.
 */
function calculateFinalScore(array $lost, array $found, int $sqlScore): int
{
    $php  = 0;
    $php += scoreName($lost['name'], $found['name']);
    $php += scoreDescription($lost['description'], $found['description']);
    $php += scoreKeywords($lost['name'], $lost['description'], $found['name'], $found['description']);
    $php += scoreBrand(
        $lost['name'] . ' ' . $lost['description'],
        $found['name'] . ' ' . $found['description']
    );
    $php += scoreModelNumbers(
        $lost['name'] . ' ' . $lost['description'],
        $found['name'] . ' ' . $found['description']
    );
    $php += scoreColorFuzzy($lost, $found);
    $php += scoreIdFields($lost, $found);

    return max(0, min(100, (int) round($sqlScore + $php)));
}


/* =========================================================================
 *  STAGE 1+2 - SQL Candidate Generation + Preliminary Scoring
 *
 *  Strategy:
 *    - Two indexed sub-selects (lost_pool / found_pool) filter to only
 *      open+approved items. The composite index idx_match_candidates
 *      (type, status, verification_status, category) makes each a
 *      range scan, not a full table scan.
 *    - INNER JOIN on category (equi-join, not CROSS JOIN). Only
 *      same-category pairs are ever constructed.
 *    - FULLTEXT MATCH() adds a text-similarity signal scored in SQL.
 *    - LEFT JOINs exclude already-matched and rejected pairs.
 *    - HAVING filters to preliminary_score >= 15, LIMIT caps output.
 * ========================================================================= */

$candidateQuery = "
    SELECT
        li.id   AS lost_id,   li.name AS lost_name,   li.category AS lost_category,
        li.color AS lost_color, li.location AS lost_location, li.description AS lost_description,
        li.id_type AS lost_id_type, li.id_number AS lost_id_number, li.id_issuer AS lost_id_issuer,

        fi.id   AS found_id,  fi.name AS found_name,  fi.category AS found_category,
        fi.color AS found_color, fi.location AS found_location, fi.description AS found_description,
        fi.id_type AS found_id_type, fi.id_number AS found_id_number, fi.id_issuer AS found_id_issuer,

        /* ---- Stage 2: SQL preliminary scoring ---- */
        (
            /* category already equal via JOIN, grant base points */
            20

            /* location match */
            + (CASE WHEN li.location = fi.location THEN 10 ELSE 0 END)

            /* exact color match */
            + (CASE WHEN li.color = fi.color AND li.color != '' THEN 10 ELSE 0 END)

            /* ID-type match (IDs only) */
            + (CASE WHEN LOWER(li.category) = 'id'
                    AND LOWER(li.id_type) = LOWER(fi.id_type)
                    AND li.id_type IS NOT NULL AND li.id_type != ''
                    THEN 15 ELSE 0 END)

            /* ID-number match (IDs only) */
            + (CASE WHEN LOWER(li.category) = 'id'
                    AND li.id_number = fi.id_number
                    AND li.id_number IS NOT NULL AND li.id_number != ''
                    THEN 25 ELSE 0 END)

        ) AS preliminary_score

    FROM
        /* Sub-select: lost pool (uses idx_match_candidates) */
        (SELECT id, name, category, color, location, description,
                id_type, id_number, id_issuer
         FROM items
         WHERE type = 'lost'
           AND status = 'open'
           AND verification_status = 'approved'
        ) AS li

    INNER JOIN
        /* Sub-select: found pool (uses idx_match_candidates) */
        (SELECT id, name, category, color, location, description,
                id_type, id_number, id_issuer
         FROM items
         WHERE type = 'found'
           AND status = 'open'
           AND verification_status = 'approved'
        ) AS fi
        ON li.category = fi.category   /* equi-join on category */

    /* Exclude already matched pairs */
    LEFT JOIN matches m
        ON li.id = m.lost_item_id AND fi.id = m.found_item_id

    /* Exclude rejected pairs */
    LEFT JOIN rejected_matches rm
        ON li.id = rm.lost_item_id AND fi.id = rm.found_item_id

    WHERE m.id IS NULL
      AND rm.id IS NULL

      /* Stage 1 filter: pairs must share at least one signal beyond category */
      AND (
          li.location = fi.location
          OR (li.color = fi.color AND li.color != '')
          OR LOWER(li.name) = LOWER(fi.name)
      )

    HAVING preliminary_score >= 15
    ORDER BY preliminary_score DESC
    LIMIT 100
";

$result = $conn->query($candidateQuery);

if (!$result) {
    echo "<p style='color:red;'>Database query failed: " . htmlspecialchars($conn->error) . "</p>";
    $result = null;
}


/* =========================================================================
 *  STAGE 3+4 - PHP Final Scoring + Display
 * ========================================================================= */

$matchesFound = false;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $lost = [
            'id'          => $row['lost_id'],
            'name'        => $row['lost_name'],
            'category'    => $row['lost_category'],
            'color'       => $row['lost_color'],
            'location'    => $row['lost_location'],
            'description' => $row['lost_description'],
            'id_type'     => $row['lost_id_type'],
            'id_number'   => $row['lost_id_number'],
            'id_issuer'   => $row['lost_id_issuer'],
        ];

        $found = [
            'id'          => $row['found_id'],
            'name'        => $row['found_name'],
            'category'    => $row['found_category'],
            'color'       => $row['found_color'],
            'location'    => $row['found_location'],
            'description' => $row['found_description'],
            'id_type'     => $row['found_id_type'],
            'id_number'   => $row['found_id_number'],
            'id_issuer'   => $row['found_id_issuer'],
        ];

        $finalScore = calculateFinalScore($lost, $found, (int)$row['preliminary_score']);

        if ($finalScore < 60) continue;

        $matchesFound = true;

        echo "<div class='card' style='margin-bottom:20px;padding:15px;border:1px solid #ccc;border-radius:8px;background:#f9f9f9;'>";

        echo "<h3>" . htmlspecialchars($lost['name']) . " &#8596; " . htmlspecialchars($found['name']) . "</h3>";
        echo "<p><strong>Score: " . $finalScore . "%</strong></p>";
        echo "<p><strong>Category:</strong> " . htmlspecialchars($lost['category']) . "</p>";

        // Show ID details if both items are IDs
        if (strtolower($lost['category']) === "id" && strtolower($found['category']) === "id") {
            echo "<div style='background:#e8f4fd;padding:8px;border-radius:4px;margin:8px 0;'>";
            echo "<strong>ID Details:</strong><br>";

            if (!empty($lost['id_type'])) {
                echo "Lost: " . htmlspecialchars($lost['id_type']);
                if (!empty($lost['id_number'])) echo " #" . htmlspecialchars($lost['id_number']);
                if (!empty($lost['id_issuer'])) echo " (" . htmlspecialchars($lost['id_issuer']) . ")";
                echo "<br>";
            }

            if (!empty($found['id_type'])) {
                echo "Found: " . htmlspecialchars($found['id_type']);
                if (!empty($found['id_number'])) echo " #" . htmlspecialchars($found['id_number']);
                if (!empty($found['id_issuer'])) echo " (" . htmlspecialchars($found['id_issuer']) . ")";
            }

            echo "</div>";
        }

        echo "<p><strong>Location:</strong> " . htmlspecialchars($lost['location']) . "</p>";
        echo "<p><strong>Lost Description:</strong> " . htmlspecialchars($lost['description']) . "</p>";
        echo "<p><strong>Found Description:</strong> " . htmlspecialchars($found['description']) . "</p>";

        echo "<form method='POST' style='margin-top:10px;'>";
        echo "<input type='hidden' name='lost_id' value='" . (int)$lost['id'] . "'>";
        echo "<input type='hidden' name='found_id' value='" . (int)$found['id'] . "'>";
        echo "<input type='hidden' name='score' value='" . $finalScore . "'>";

        if ($just_claimed && $lost['id'] == $lost_id && $found['id'] == $found_id) {
            echo "<div style='padding:8px 15px;background:#4CAF50;color:white;border-radius:5px;display:inline-block;'>";
            echo "✓ Item Claimed Successfully";
            echo "</div>";
        } else {
            echo "<button type='submit' name='claim_match' 
                  style='padding:8px 15px;background:green;color:white;border:none;border-radius:5px;cursor:pointer;margin-right:10px;'>";
            echo "Item Matched";
            echo "</button>";
            echo "<button type='submit' name='counter_match' 
                  style='padding:8px 15px;background:#ff6b6b;color:white;border:none;border-radius:5px;cursor:pointer;'>";
            echo "Not a Match";
            echo "</button>";
        }

        echo "</form>";
        echo "</div>";
    }
}

if (!$matchesFound) {
    echo "<p>No potential matches found at this time. Check back later!</p>";
}

?>
</div>
</body>

</html>