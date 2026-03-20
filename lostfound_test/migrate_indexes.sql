-- =============================================================
-- Migration: Add indexes for optimized matching pipeline
-- Run once: mysql -u root db < migrate_indexes.sql
-- =============================================================

-- Stage 1: Composite index for candidate filtering
-- Covers WHERE type/status/verification_status/category lookups
ALTER TABLE `items`
ADD INDEX `idx_match_candidates` (
    `type`,
    `status`,
    `verification_status`,
    `category`
);

-- Stage 2: FULLTEXT index for text similarity search
ALTER TABLE `items`
ADD FULLTEXT INDEX `ft_name_desc` (`name`, `description`);

-- Stage 3: Index on matches pair for duplicate check
ALTER TABLE `matches`
ADD UNIQUE INDEX `idx_match_pair` (
    `lost_item_id`,
    `found_item_id`
);

-- Stage 4: Index on rejected_matches pair (already has pair_unique, verify)
-- Already exists from schema: UNIQUE KEY `pair_unique` (`lost_item_id`, `found_item_id`)