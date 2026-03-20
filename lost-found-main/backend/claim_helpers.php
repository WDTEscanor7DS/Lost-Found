<?php

/**
 * Claim Verification Helpers
 *
 * Provides:
 *   - generateClaimId()       — Unique claim ID generator
 *   - calculateClaimScore()   — Compare claimant input vs stored item data
 *   - addNotification()       — Insert a notification record
 *   - getScoreBreakdown()     — Detailed scoring breakdown for admin
 */

/**
 * Generate a unique claim ID like CLM-20260320-0001
 */
function generateClaimId(mysqli $conn): string
{
    $datePart = date('Ymd');
    $prefix = "CLM-{$datePart}-";

    $stmt = $conn->prepare("SELECT claim_id FROM claims WHERE claim_id LIKE ? ORDER BY id DESC LIMIT 1");
    $pattern = $prefix . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($result) {
        $lastNum = (int) substr($result['claim_id'], -4);
        $nextNum = $lastNum + 1;
    } else {
        $nextNum = 1;
    }

    return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
}

/**
 * Calculate how well a claim description matches the stored item.
 * Returns an integer score 0-100.
 *
 * Scoring weights:
 *   - Name similarity:        max 20 pts
 *   - Color match:            max 15 pts
 *   - Description similarity: max 25 pts
 *   - Location match:         max 10 pts
 *   - Unique identifiers:     max 15 pts
 *   - Proof uploaded:         max 15 pts
 */
function calculateClaimScore(array $item, array $claimData): int
{
    $score = 0;
    $itemName = strtolower(trim($item['name'] ?? ''));
    $itemColor = strtolower(trim($item['color'] ?? ''));
    $itemDesc = strtolower(trim($item['description'] ?? ''));
    $itemLocation = strtolower(trim($item['location'] ?? ''));

    $claimDesc = strtolower(trim($claimData['item_description'] ?? ''));
    $claimIdentifiers = strtolower(trim($claimData['unique_identifiers'] ?? ''));

    // 1. Name similarity (max 20 pts)
    // Check if claimant description mentions the item name
    $pct = 0;
    similar_text($itemName, extractItemNameFromDesc($claimDesc, $itemName), $pct);
    $score += min(20, (int) round($pct * 0.20));

    // Also check direct mentions of the item name in description
    if ($itemName !== '' && str_contains($claimDesc, $itemName)) {
        $score += 5;
        $score = min($score, 20); // cap at 20
    }

    // 2. Color match (max 15 pts)
    if ($itemColor !== '') {
        if (str_contains($claimDesc, $itemColor) || str_contains($claimIdentifiers, $itemColor)) {
            $score += 15;
        } else {
            // Check for partial color matches in description
            $colors = [
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
                'gold',
                'beige',
                'navy'
            ];
            foreach ($colors as $c) {
                if (str_contains($itemColor, $c) && (str_contains($claimDesc, $c) || str_contains($claimIdentifiers, $c))) {
                    $score += 10;
                    break;
                }
            }
        }
    }

    // 3. Description similarity (max 25 pts)
    if ($itemDesc !== '' && $claimDesc !== '') {
        $descPct = 0;
        similar_text($itemDesc, $claimDesc, $descPct);
        $score += min(25, (int) round($descPct * 0.25));

        // Keyword overlap bonus
        $itemWords = extractKeywords($itemDesc);
        $claimWords = extractKeywords($claimDesc . ' ' . $claimIdentifiers);
        if (!empty($itemWords) && !empty($claimWords)) {
            $overlap = count(array_intersect($itemWords, $claimWords));
            $total = max(count($itemWords), 1);
            $keywordBonus = min(10, (int) round(($overlap / $total) * 10));
            $score += $keywordBonus;
            $score = min($score, 60); // ensure we don't exceed running total cap
        }
    }

    // 4. Location match (max 10 pts)
    if ($itemLocation !== '' && $claimDesc !== '') {
        if (str_contains($claimDesc, $itemLocation) || str_contains($claimIdentifiers, $itemLocation)) {
            $score += 10;
        } else {
            $locPct = 0;
            similar_text($itemLocation, $claimDesc, $locPct);
            if ($locPct > 50) {
                $score += 5;
            }
        }
    }

    // 5. Unique identifiers provided (max 15 pts)
    if ($claimIdentifiers !== '') {
        $identifierLength = strlen($claimIdentifiers);
        if ($identifierLength > 100) {
            $score += 15; // Very detailed
        } elseif ($identifierLength > 50) {
            $score += 10;
        } elseif ($identifierLength > 20) {
            $score += 7;
        } else {
            $score += 3;
        }
    }

    // 6. Proof uploaded (max 15 pts)
    $proofScore = 0;
    if (!empty($claimData['proof_image'])) $proofScore += 5;
    if (!empty($claimData['id_document'])) $proofScore += 5;
    if (!empty($claimData['proof_document'])) $proofScore += 5;
    $score += $proofScore;

    return max(0, min(100, $score));
}

/**
 * Get detailed score breakdown for admin view.
 */
function getScoreBreakdown(array $item, array $claimData): array
{
    $breakdown = [];
    $itemName = strtolower(trim($item['name'] ?? ''));
    $itemColor = strtolower(trim($item['color'] ?? ''));
    $itemDesc = strtolower(trim($item['description'] ?? ''));
    $itemLocation = strtolower(trim($item['location'] ?? ''));
    $claimDesc = strtolower(trim($claimData['item_description'] ?? ''));
    $claimIdentifiers = strtolower(trim($claimData['unique_identifiers'] ?? ''));

    // Name check
    $nameScore = 0;
    $pct = 0;
    similar_text($itemName, extractItemNameFromDesc($claimDesc, $itemName), $pct);
    $nameScore = min(20, (int) round($pct * 0.20));
    if ($itemName !== '' && str_contains($claimDesc, $itemName)) {
        $nameScore = min(20, $nameScore + 5);
    }
    $breakdown['name'] = ['score' => $nameScore, 'max' => 20, 'label' => 'Item Name Match'];

    // Color check
    $colorScore = 0;
    if ($itemColor !== '') {
        if (str_contains($claimDesc, $itemColor) || str_contains($claimIdentifiers, $itemColor)) {
            $colorScore = 15;
        } else {
            $colors = [
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
                'gold',
                'beige',
                'navy'
            ];
            foreach ($colors as $c) {
                if (str_contains($itemColor, $c) && (str_contains($claimDesc, $c) || str_contains($claimIdentifiers, $c))) {
                    $colorScore = 10;
                    break;
                }
            }
        }
    }
    $breakdown['color'] = ['score' => $colorScore, 'max' => 15, 'label' => 'Color Match'];

    // Description similarity
    $descScore = 0;
    if ($itemDesc !== '' && $claimDesc !== '') {
        $descPct = 0;
        similar_text($itemDesc, $claimDesc, $descPct);
        $descScore = min(25, (int) round($descPct * 0.25));
    }
    $breakdown['description'] = ['score' => $descScore, 'max' => 25, 'label' => 'Description Similarity'];

    // Location
    $locScore = 0;
    if ($itemLocation !== '' && $claimDesc !== '') {
        if (str_contains($claimDesc, $itemLocation) || str_contains($claimIdentifiers, $itemLocation)) {
            $locScore = 10;
        }
    }
    $breakdown['location'] = ['score' => $locScore, 'max' => 10, 'label' => 'Location Match'];

    // Unique identifiers
    $idScore = 0;
    if ($claimIdentifiers !== '') {
        $len = strlen($claimIdentifiers);
        if ($len > 100) $idScore = 15;
        elseif ($len > 50) $idScore = 10;
        elseif ($len > 20) $idScore = 7;
        else $idScore = 3;
    }
    $breakdown['identifiers'] = ['score' => $idScore, 'max' => 15, 'label' => 'Unique Identifiers'];

    // Proof docs
    $proofScore = 0;
    if (!empty($claimData['proof_image'])) $proofScore += 5;
    if (!empty($claimData['id_document'])) $proofScore += 5;
    if (!empty($claimData['proof_document'])) $proofScore += 5;
    $breakdown['proof'] = ['score' => $proofScore, 'max' => 15, 'label' => 'Proof Documents'];

    return $breakdown;
}

/**
 * Extract keywords from text, removing common stopwords.
 */
function extractKeywords(string $text): array
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
        'was',
        'were',
        'been',
        'my',
        'in',
        'on',
        'at',
        'with',
        'for',
        'to',
        'from',
        'by',
        'i',
        'have',
        'had',
        'has',
        'its',
        'lost',
        'found',
        'item',
        'very',
        'just',
        'about',
        'also',
        'there'
    ];
    $words = preg_split('/[^a-z0-9]+/', strtolower(trim($text)));
    return array_values(array_unique(array_diff(array_filter($words, fn($w) => strlen($w) > 2), $stopwords)));
}

/**
 * Try to extract the item name component from description text.
 */
function extractItemNameFromDesc(string $desc, string $itemName): string
{
    // If the description contains the item name, extract it
    if (str_contains($desc, $itemName)) {
        return $itemName;
    }
    // Otherwise return the first few significant words of the description
    $words = array_slice(explode(' ', $desc), 0, 5);
    return implode(' ', $words);
}

/**
 * Insert a notification record.
 */
function addNotification(mysqli $conn, int $userId, string $title, string $message, string $type = 'info', string $link = ''): bool
{
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $title, $message, $type, $link);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Get unread notification count for a user.
 */
function getUnreadNotificationCount(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) as c FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    $stmt->close();
    return (int) $count;
}
