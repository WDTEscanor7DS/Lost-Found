<?php

/**
 * Matching Algorithm Engine
 * 
 * Reusable matching module — can be included by any page:
 *   require "../backend/matcher.php";
 *
 * Exposes:
 *   - Scoring functions: scoreName(), scoreDescription(), scoreKeywords(), etc.
 *   - calculateFinalScore(array $lost, array $found, int $sqlScore): int
 *   - getCandidateQuery(): string  — returns the SQL for candidate generation
 *   - findMatches(mysqli $conn, int $minScore = 60, int $limit = 100): array
 *   - claimMatch(mysqli $conn, int $lost_id, int $found_id, int $score): array
 *   - counterMatch(mysqli $conn, int $lost_id, int $found_id): bool
 */

/* =========================================================================
 *  SCORING MODULE
 * ========================================================================= */

function normalize(string $text): string
{
    return strtolower(trim($text));
}

/** Score name similarity using similar_text (max 30 pts). */
function scoreName(string $n1, string $n2): float
{
    $percent = 0;
    similar_text(normalize($n1), normalize($n2), $percent);
    return $percent * 0.30;
}

/** Score description similarity (max 15 pts). */
function scoreDescription(string $d1, string $d2): float
{
    $percent = 0;
    similar_text(normalize($d1), normalize($d2), $percent);
    return $percent * 0.15;
}

/** Score keyword overlap between combined name+description (max 15 pts). */
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

/** Score brand detection (max 5 pts). */
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

/** Score model number similarity (max 15 pts). */
function scoreModelNumbers(string $text1, string $text2): float
{
    preg_match_all('/[a-z]*[0-9]+[a-z0-9]*/i', normalize($text1), $m1);
    preg_match_all('/[a-z]*[0-9]+[a-z0-9]*/i', normalize($text2), $m2);

    $score = 0;

    if (count(array_intersect($m1[0], $m2[0])) > 0) {
        $score += 10;
    }

    foreach ($m1[0] as $a) {
        foreach ($m2[0] as $b) {
            if (is_numeric($a) && is_numeric($b) && abs($a - $b) <= 2) {
                return $score + 5;
            }
        }
    }

    return $score;
}

/** Score color overlap including description mentions (max 10 pts). */
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

/** Score ID-specific fields: issuer fuzzy match + multi-field bonus (max 20 pts). */
function scoreIdFields(array $item1, array $item2): float
{
    if (normalize($item1['category']) !== 'id' || normalize($item2['category']) !== 'id') {
        return 0;
    }

    $score = 0;
    $issuer1 = normalize($item1['id_issuer'] ?? '');
    $issuer2 = normalize($item2['id_issuer'] ?? '');

    if ($issuer1 !== '' && $issuer2 !== '') {
        $pct = 0;
        similar_text($issuer1, $issuer2, $pct);
        if ($pct > 80) $score += 10;
    }

    $matched = 0;
    if (normalize($item1['id_type'] ?? '') === normalize($item2['id_type'] ?? '') && ($item1['id_type'] ?? '') !== '') $matched++;
    if (normalize($item1['id_number'] ?? '') === normalize($item2['id_number'] ?? '') && ($item1['id_number'] ?? '') !== '') $matched++;
    if ($score > 0) $matched++;

    if ($matched >= 2) $score += 10;

    return $score;
}

/**
 * Final scoring: combines SQL preliminary score + PHP scoring components.
 * Returns 0-100 clamped score.
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
 *  CANDIDATE GENERATION & MATCHING
 * ========================================================================= */

/**
 * Returns the SQL query string for candidate match generation.
 */
function getCandidateQuery(): string
{
    return "
    SELECT
        li.id   AS lost_id,   li.name AS lost_name,   li.category AS lost_category,
        li.color AS lost_color, li.location AS lost_location, li.description AS lost_description,
        li.id_type AS lost_id_type, li.id_number AS lost_id_number, li.id_issuer AS lost_id_issuer,

        fi.id   AS found_id,  fi.name AS found_name,  fi.category AS found_category,
        fi.color AS found_color, fi.location AS found_location, fi.description AS found_description,
        fi.id_type AS found_id_type, fi.id_number AS found_id_number, fi.id_issuer AS found_id_issuer,

        (
            20
            + (CASE WHEN li.location = fi.location THEN 10 ELSE 0 END)
            + (CASE WHEN li.color = fi.color AND li.color != '' THEN 10 ELSE 0 END)
            + (CASE WHEN LOWER(li.category) = 'id'
                    AND LOWER(li.id_type) = LOWER(fi.id_type)
                    AND li.id_type IS NOT NULL AND li.id_type != ''
                    THEN 15 ELSE 0 END)
            + (CASE WHEN LOWER(li.category) = 'id'
                    AND li.id_number = fi.id_number
                    AND li.id_number IS NOT NULL AND li.id_number != ''
                    THEN 25 ELSE 0 END)
        ) AS preliminary_score

    FROM
        (SELECT id, name, category, color, location, description,
                id_type, id_number, id_issuer
         FROM items
         WHERE type = 'lost'
           AND status = 'open'
           AND verification_status = 'approved'
        ) AS li

    INNER JOIN
        (SELECT id, name, category, color, location, description,
                id_type, id_number, id_issuer
         FROM items
         WHERE type = 'found'
           AND status = 'open'
           AND verification_status = 'approved'
        ) AS fi
        ON li.category = fi.category

    LEFT JOIN matches m
        ON li.id = m.lost_item_id AND fi.id = m.found_item_id

    LEFT JOIN rejected_matches rm
        ON li.id = rm.lost_item_id AND fi.id = rm.found_item_id

    WHERE m.id IS NULL
      AND rm.id IS NULL
      AND (
          li.location = fi.location
          OR (li.color = fi.color AND li.color != '')
          OR LOWER(li.name) = LOWER(fi.name)
      )

    HAVING preliminary_score >= 15
    ORDER BY preliminary_score DESC
    LIMIT 100
    ";
}

/**
 * Find all matches above the minimum score threshold.
 * Returns array of ['lost' => [...], 'found' => [...], 'score' => int]
 */
function findMatches(mysqli $conn, int $minScore = 60, int $limit = 100): array
{
    $result = $conn->query(getCandidateQuery());

    if (!$result) {
        return [];
    }

    $matches = [];
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

        if ($finalScore >= $minScore) {
            $matches[] = [
                'lost'  => $lost,
                'found' => $found,
                'score' => $finalScore,
            ];
        }

        if (count($matches) >= $limit) break;
    }

    return $matches;
}

/**
 * Claim a match: insert into matches table, update item statuses, send notification email.
 * Returns ['success' => bool, 'message' => string]
 */
function claimMatch(mysqli $conn, int $lost_id, int $found_id, int $score): array
{
    // Get lost item info
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

    if (!$lostItem || !$foundItem) {
        return ['success' => false, 'message' => 'Items not found.'];
    }

    // Generate a unique secure claim token
    $claimToken = bin2hex(random_bytes(32));

    // Insert into matches with token
    $stmt = $conn->prepare("INSERT INTO matches (lost_item_id, found_item_id, match_score, status, claim_token) VALUES (?, ?, ?, 'pending', ?)");
    $stmt->bind_param("iiis", $lost_id, $found_id, $score, $claimToken);
    $stmt->execute();
    $stmt->close();

    // Update item statuses
    $stmt = $conn->prepare("UPDATE items SET status='matched' WHERE id IN (?, ?)");
    $stmt->bind_param("ii", $lost_id, $found_id);
    $stmt->execute();
    $stmt->close();

    // Send email notification with secure token
    $emailResult = sendMatchNotification($conn, $lost_id, $found_id, $lostItem, $foundItem, $claimToken);

    if ($emailResult === true) {
        return ['success' => true, 'message' => 'Item matched! The user will be notified for confirmation.'];
    } else {
        return ['success' => true, 'message' => 'Match claimed, but email could not be sent. Error: ' . $emailResult];
    }
}

/**
 * Mark a pair as "not a match" so it won't appear again.
 */
function counterMatch(mysqli $conn, int $lost_id, int $found_id): bool
{
    if ($lost_id <= 0 || $found_id <= 0) return false;

    $stmt = $conn->prepare("INSERT INTO rejected_matches (lost_item_id, found_item_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $lost_id, $found_id);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
}

/**
 * Send match notification email to the lost item owner.
 * Returns true on success, or error message string on failure.
 */
function sendMatchNotification(mysqli $conn, int $lost_id, int $found_id, array $lostItem, array $foundItem, string $claimToken = '')
{
    // PHPMailer must be in backend/PHPMailer/
    $mailerPath = __DIR__ . '/PHPMailer/';
    if (!file_exists($mailerPath . 'PHPMailer.php')) {
        return 'PHPMailer not found.';
    }

    require_once $mailerPath . 'PHPMailer.php';
    require_once $mailerPath . 'SMTP.php';
    require_once $mailerPath . 'Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $lostEmail = $lostItem['email'];
        $lostName = $lostItem['name'];
        $foundName = $foundItem['name'];
        $foundImage = $foundItem['image'];

        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = 'tls';
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_USERNAME, MAIL_FROM_NAME);
        $mail->addAddress($lostEmail);

        $mail->isHTML(true);
        $mail->Subject = 'Your Lost Item May Have Been Found!';

        // Base URL for confirmation links (uses secure token)
        $baseUrl = SITE_URL . '/backend';
        $confirmUrl = $baseUrl . '/user_confirm_match.php?token=' . urlencode($claimToken);
        $rejectUrl  = $baseUrl . '/user_reject_match.php?token=' . urlencode($claimToken);

        $imagePath = __DIR__ . "/../uploads/" . $foundImage;
        if (!empty($foundImage) && file_exists($imagePath)) {
            $mail->addEmbeddedImage($imagePath, 'foundimg');
            $imageHTML = "<img src='cid:foundimg' width='300'>";
        } else {
            $imageHTML = "<p>No image available.</p>";
        }

        $mail->Body = "
        <h2>Potential Match Found!</h2>
        <p>We believe we may have found your lost item <strong>\"" . htmlspecialchars($lostName) . "\"</strong>!</p>
        <p><strong>Found Item Details:</strong></p>
        <ul>
            <li><strong>Name:</strong> " . htmlspecialchars($foundName) . "</li>
            <li><strong>Category:</strong> " . htmlspecialchars($foundItem['category']) . "</li>
            <li><strong>Color:</strong> " . htmlspecialchars($foundItem['color']) . "</li>
            <li><strong>Location:</strong> " . htmlspecialchars($foundItem['location']) . "</li>
            <li><strong>Description:</strong> " . nl2br(htmlspecialchars($foundItem['description'])) . "</li>";

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
                <a href='{$confirmUrl}' style='background:#4CAF50;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;margin-right:10px;display:inline-block;'>YES - This is my item</a>
                <a href='{$rejectUrl}' style='background:#f44336;color:white;padding:12px 24px;text-decoration:none;border-radius:6px;font-weight:bold;display:inline-block;'>NO - Not my item</a>
            </p>
            <p><strong>If you confirm this is your item:</strong> Our administration team will review your confirmation and contact you with pickup instructions for the claim booth.</p>
            <p><strong>If this is not your item:</strong> We'll continue searching for your actual lost item.</p>
        </div>
        ";

        $mail->send();
        return true;
    } catch (\Exception $e) {
        return $mail->ErrorInfo;
    }
}
