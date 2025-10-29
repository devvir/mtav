-- ============================================================================
-- MTAV Test Universe Fixture
-- ============================================================================
-- A comprehensive, self-documenting test dataset that provides:
-- - Predictable IDs for easy assertions
-- - Self-descriptive names indicating state/relationships
-- - Coverage of common scenarios (active, inactive, deleted, empty, full)
-- - Minimal setup needed in most tests
--
-- Usage:
-- - Reset before each test via database transactions (automatic rollback)
-- - For tests that can't rollback: manually reset using loadUniverse()
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PROJECTS (5 total)
-- ============================================================================
-- Distribution:
-- - #1, #2: Normal active projects with full data
-- - #3: Active but has NO unit types (edge case)
-- - #4: Inactive project (active=false)
-- - #5: Soft-deleted project
-- ============================================================================

TRUNCATE TABLE projects;

INSERT INTO projects (id, name, description, organization, active, created_at, updated_at, deleted_at) VALUES
(1, 'Project 1',                    'Description 1',                    'Organization 1',                    TRUE,  NOW(), NOW(), NULL),
(2, 'Project 2',                    'Description 2',                    'Organization 2',                    TRUE,  NOW(), NOW(), NULL),
(3, 'Project 3 (no unit types)',    'Description 3 (no unit types)',    'Organization 3 (no unit types)',    TRUE,  NOW(), NOW(), NULL),
(4, 'Project 4 (inactive)',         'Description 4 (inactive)',         'Organization 4 (inactive)',         FALSE, NOW(), NOW(), NULL),
(5, 'Project 5 (deleted)',          'Description 5 (deleted)',          'Organization 5 (deleted)',          TRUE,  NOW(), NOW(), NOW());

-- ============================================================================
-- USERS - ADMINS (4 total)
-- ============================================================================
-- Distribution:
-- - #1: Admin with NO projects assigned
-- - #2: Admin managing 1 project (#1)
-- - #3: Admin managing 2 projects (#2, #3)
-- - #4: Admin managing 3 projects (#2, #3, #4) - inactive in #2
--
-- Password: 'password' (hashed with bcrypt)
-- ============================================================================

TRUNCATE TABLE users;

INSERT INTO users (id, family_id, email, firstname, lastname, phone, avatar, legal_id, email_verified_at, password, is_admin, darkmode, remember_token, created_at, updated_at, deleted_at) VALUES
-- Admins (is_admin=true)
(1, NULL, 'admin1@example.com', 'admin', '1 (no projects)',       NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(2, NULL, 'admin2@example.com', 'admin', '2 (manages 1)',         NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(3, NULL, 'admin3@example.com', 'admin', '3 (manages 2,3)',       NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(4, NULL, 'admin4@example.com', 'admin', '4 (manages 2-,3+,4+)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(50, NULL, 'admin50@example.com', 'admin', '50 (manages deleted 5)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(53, NULL, 'admin53@example.com', 'admin', '53 (manages 2, deleted 5)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(54, NULL, 'admin54@example.com', 'admin', '54 (manages 2,3,4, deleted 5)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL);

-- ============================================================================
-- PROJECT_USER (Admin-Project assignments)
-- ============================================================================
-- Admin #1: No assignments (remains unassigned)
-- Admin #2: Manages Project #1 (active)
-- Admin #3: Manages Projects #2, #3 (both active)
-- Admin #4: Manages Projects #2 (inactive), #3, #4 (both active)
-- ============================================================================

TRUNCATE TABLE project_user;

INSERT INTO project_user (id, user_id, project_id, active, created_at, updated_at) VALUES
-- Admin #2 manages 1 project
(1, 2, 1, TRUE,  NOW(), NOW()),

-- Admin #3 manages 2 projects
(2, 3, 2, TRUE,  NOW(), NOW()),
(3, 3, 3, TRUE,  NOW(), NOW()),

-- Admin #4 manages 3 projects (inactive in #2)
(4, 4, 2, FALSE, NOW(), NOW()),
(5, 4, 3, TRUE,  NOW(), NOW()),
(6, 4, 4, TRUE,  NOW(), NOW()),

-- Admin #50 manages deleted Project #5
(7, 50, 5, TRUE, NOW(), NOW()),

-- Admin #53 manages 2 projects (one active, one deleted)
(8, 53, 2, TRUE, NOW(), NOW()),
(9, 53, 5, TRUE, NOW(), NOW()),

-- Admin #54 manages 4 projects (three active, one deleted)
(10, 54, 2, TRUE, NOW(), NOW()),
(11, 54, 3, TRUE, NOW(), NOW()),
(12, 54, 4, TRUE, NOW(), NOW()),
(13, 54, 5, TRUE, NOW(), NOW());

-- ============================================================================
-- UNIT TYPES (12 total)
-- ============================================================================
-- Distribution:
-- - Project #1: 3 unit types (#1, #2, #3)
-- - Project #2: 3 unit types (#4, #5, #6)
-- - Project #3: 0 unit types (NONE - edge case)
-- - Project #4: 3 unit types (#7, #8, #9)
-- - Project #5: 3 unit types (#10, #11, #12)
-- ============================================================================

TRUNCATE TABLE unit_types;

INSERT INTO unit_types (id, project_id, name, description, created_at, updated_at, deleted_at) VALUES
-- Project #1 types
(1,  1, 'Type 1',  'Description 1',  NOW(), NOW(), NULL),
(2,  1, 'Type 2',  'Description 2',  NOW(), NOW(), NULL),
(3,  1, 'Type 3',  'Description 3',  NOW(), NOW(), NULL),

-- Project #2 types
(4,  2, 'Type 4',  'Description 4',  NOW(), NOW(), NULL),
(5,  2, 'Type 5',  'Description 5',  NOW(), NOW(), NULL),
(6,  2, 'Type 6',  'Description 6',  NOW(), NOW(), NULL),

-- Project #3 has NO unit types (skipped)

-- Project #4 types
(7,  4, 'Type 7',  'Description 7',  NOW(), NOW(), NULL),
(8,  4, 'Type 8',  'Description 8',  NOW(), NOW(), NULL),
(9,  4, 'Type 9',  'Description 9',  NOW(), NOW(), NULL),

-- Project #5 types
(10, 5, 'Type 10', 'Description 10', NOW(), NOW(), NULL),
(11, 5, 'Type 11', 'Description 11', NOW(), NOW(), NULL),
(12, 5, 'Type 12', 'Description 12', NOW(), NOW(), NULL);

-- ============================================================================
-- FAMILIES (15 total)
-- ============================================================================
-- Distribution by Project:
-- - Project #1: 12 families (#1-#12) - 4 per unit type
-- - Project #2: 3 families (#13-#15) - distributed across types
-- - Project #3: 0 families (no unit types = no families)
-- - Project #4: 3 families (#16-#18) - 1 per type
-- - Project #5: 3 families (#19-#21) - 1 per type
--
-- Special states:
-- - Family #1: Has NO members (edge case)
-- - Family #2: Has only 1 member (inactive)
-- - Family #3: Has only 1 member (soft-deleted)
-- - Family #4: Has 3 members (1 active, 1 inactive, 1 deleted)
-- - Family #5: Has 10 members (for pagination tests)
-- - Families #6-#15: Have 3 members each
-- ============================================================================

TRUNCATE TABLE families;

INSERT INTO families (id, project_id, unit_type_id, name, avatar, created_at, updated_at, deleted_at) VALUES
-- Project #1 families (4 per type)
(1,  1, 1, 'Family 1 (no members)',      NULL, NOW(), NOW(), NULL),
(2,  1, 1, 'Family 2 (inactive member)', NULL, NOW(), NOW(), NULL),
(3,  1, 1, 'Family 3 (deleted member)',  NULL, NOW(), NOW(), NULL),
(4,  1, 1, 'Family 4',                   NULL, NOW(), NOW(), NULL),

(5,  1, 2, 'Family 5',                   NULL, NOW(), NOW(), NULL),
(6,  1, 2, 'Family 6',                   NULL, NOW(), NOW(), NULL),
(7,  1, 2, 'Family 7',                   NULL, NOW(), NOW(), NULL),
(8,  1, 2, 'Family 8',                   NULL, NOW(), NOW(), NULL),

(9,  1, 3, 'Family 9',                   NULL, NOW(), NOW(), NULL),
(10, 1, 3, 'Family 10',                  NULL, NOW(), NOW(), NULL),
(11, 1, 3, 'Family 11',                  NULL, NOW(), NOW(), NULL),
(12, 1, 3, 'Family 12',                  NULL, NOW(), NOW(), NULL),

-- Project #2 families (2 for type #4, 1 for type #5, 0 for type #6)
(13, 2, 4, 'Family 13',                  NULL, NOW(), NOW(), NULL),
(14, 2, 4, 'Family 14',                  NULL, NOW(), NOW(), NULL),
(15, 2, 5, 'Family 15',                  NULL, NOW(), NOW(), NULL),

-- Project #3 has NO families (no unit types)

-- Project #4 families (1 per type)
(16, 4, 7, 'Family 16',                  NULL, NOW(), NOW(), NULL),
(17, 4, 8, 'Family 17',                  NULL, NOW(), NOW(), NULL),
(18, 4, 9, 'Family 18',                  NULL, NOW(), NOW(), NULL),

-- Project #5 families (1 per type)
(19, 5, 10, 'Family 19',                 NULL, NOW(), NOW(), NULL),
(20, 5, 11, 'Family 20',                 NULL, NOW(), NOW(), NULL),
(21, 5, 12, 'Family 21',                 NULL, NOW(), NOW(), NULL),

-- Project #4 test family (for inactive project test)
(22, 4, 7, 'Family 22 (inactive project)', NULL, NOW(), NOW(), NULL),

-- Project #5 test family (for deleted project test)
(23, 5, 10, 'Family 23 (deleted project)', NULL, NOW(), NOW(), NULL);

-- ============================================================================
-- UNITS (22 total)
-- ============================================================================
-- Distribution by Unit Type:
-- - Type #1: 0 units (edge case - type with no units)
-- - Type #2: 1 unit (#1)
-- - Type #3: 2 units (#2 active, #3 deleted) - one unit per state
-- - Types #4-#12: 2 units each (#4-#22)
--
-- Units are NOT assigned to families in this fixture (family_id = NULL)
-- Tests can assign units as needed for their specific scenarios
-- ============================================================================

TRUNCATE TABLE units;

INSERT INTO units (id, project_id, unit_type_id, family_id, number, created_at, updated_at, deleted_at) VALUES
-- Type #1: NO units

-- Type #2: 1 unit
(1, 1, 2, NULL, 'Unit 1, Type 2',            NOW(), NOW(), NULL),

-- Type #3: 2 units (1 active, 1 deleted)
(2, 1, 3, NULL, 'Unit 2, Type 3',            NOW(), NOW(), NULL),
(3, 1, 3, NULL, 'Unit 3, Type 3 (deleted)',  NOW(), NOW(), NOW()),

-- Type #4: 2 units
(4, 2, 4, NULL, 'Unit 4, Type 4',            NOW(), NOW(), NULL),
(5, 2, 4, NULL, 'Unit 5, Type 4',            NOW(), NOW(), NULL),

-- Type #5: 2 units
(6, 2, 5, NULL, 'Unit 6, Type 5',            NOW(), NOW(), NULL),
(7, 2, 5, NULL, 'Unit 7, Type 5',            NOW(), NOW(), NULL),

-- Type #6: 2 units
(8, 2, 6, NULL, 'Unit 8, Type 6',            NOW(), NOW(), NULL),
(9, 2, 6, NULL, 'Unit 9, Type 6',            NOW(), NOW(), NULL),

-- Type #7: 2 units
(10, 4, 7, NULL, 'Unit 10, Type 7',          NOW(), NOW(), NULL),
(11, 4, 7, NULL, 'Unit 11, Type 7',          NOW(), NOW(), NULL),

-- Type #8: 2 units
(12, 4, 8, NULL, 'Unit 12, Type 8',          NOW(), NOW(), NULL),
(13, 4, 8, NULL, 'Unit 13, Type 8',          NOW(), NOW(), NULL),

-- Type #9: 2 units
(14, 4, 9, NULL, 'Unit 14, Type 9',          NOW(), NOW(), NULL),
(15, 4, 9, NULL, 'Unit 15, Type 9',          NOW(), NOW(), NULL),

-- Type #10: 2 units
(16, 5, 10, NULL, 'Unit 16, Type 10',        NOW(), NOW(), NULL),
(17, 5, 10, NULL, 'Unit 17, Type 10',        NOW(), NOW(), NULL),

-- Type #11: 2 units
(18, 5, 11, NULL, 'Unit 18, Type 11',        NOW(), NOW(), NULL),
(19, 5, 11, NULL, 'Unit 19, Type 11',        NOW(), NOW(), NULL),

-- Type #12: 2 units
(20, 5, 12, NULL, 'Unit 20, Type 12',        NOW(), NOW(), NULL),
(21, 5, 12, NULL, 'Unit 21, Type 12',        NOW(), NOW(), NULL);

-- ============================================================================
-- USERS - MEMBERS (30 total, IDs 5-34)
-- ============================================================================
-- Distribution by Family:
-- - Family #1: 0 members (edge case)
-- - Family #2: 1 member (#5, inactive via project_user)
-- - Family #3: 1 member (#6, soft-deleted)
-- - Family #4: 3 members (#7 active, #8 inactive, #9 deleted)
-- - Family #5: 10 members (#10-#19) for pagination tests
-- - Families #6-#15: 3 members each (#20-#49)
--
-- Note: Members are inserted with sequential IDs starting at 5
-- Password: 'password' (hashed with bcrypt)
-- ============================================================================

INSERT INTO users (id, family_id, email, firstname, lastname, phone, avatar, legal_id, email_verified_at, password, is_admin, darkmode, remember_token, created_at, updated_at, deleted_at) VALUES
-- Family #2: 1 member (inactive)
(5, 2, 'member5@example.com', 'member (inactive)', '5', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #3: 1 member (deleted)
(6, 3, 'member6@example.com', 'member (deleted)', '6', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NOW()),

-- Family #4: 3 members (1 active, 1 inactive, 1 deleted)
(7, 4, 'member7@example.com',  'member',            '7', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(8, 4, 'member8@example.com',  'member (inactive)', '8', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(9, 4, 'member9@example.com',  'member (deleted)',  '9', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NOW()),

-- Family #5: 10 members (pagination tests)
(10, 5, 'member10@example.com', 'member', '10', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(11, 5, 'member11@example.com', 'member', '11', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(12, 5, 'member12@example.com', 'member', '12', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(13, 5, 'member13@example.com', 'member', '13', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(14, 5, 'member14@example.com', 'member', '14', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(15, 5, 'member15@example.com', 'member', '15', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(16, 5, 'member16@example.com', 'member', '16', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(17, 5, 'member17@example.com', 'member', '17', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(18, 5, 'member18@example.com', 'member', '18', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(19, 5, 'member19@example.com', 'member', '19', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #6: 3 members
(20, 6, 'member20@example.com', 'member', '20', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(21, 6, 'member21@example.com', 'member', '21', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(22, 6, 'member22@example.com', 'member', '22', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #7: 3 members
(23, 7, 'member23@example.com', 'member', '23', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(24, 7, 'member24@example.com', 'member', '24', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(25, 7, 'member25@example.com', 'member', '25', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #8: 3 members
(26, 8, 'member26@example.com', 'member', '26', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(27, 8, 'member27@example.com', 'member', '27', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(28, 8, 'member28@example.com', 'member', '28', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #9: 3 members
(29, 9, 'member29@example.com', 'member', '29', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(30, 9, 'member30@example.com', 'member', '30', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(31, 9, 'member31@example.com', 'member', '31', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #10: 3 members
(32, 10, 'member32@example.com', 'member', '32', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(33, 10, 'member33@example.com', 'member', '33', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(34, 10, 'member34@example.com', 'member', '34', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #11: 3 members
(35, 11, 'member35@example.com', 'member', '35', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(36, 11, 'member36@example.com', 'member', '36', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(37, 11, 'member37@example.com', 'member', '37', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #12: 3 members
(38, 12, 'member38@example.com', 'member', '38', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(39, 12, 'member39@example.com', 'member', '39', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(40, 12, 'member40@example.com', 'member', '40', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #13: 3 members
(41, 13, 'member41@example.com', 'member', '41', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(42, 13, 'member42@example.com', 'member', '42', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(43, 13, 'member43@example.com', 'member', '43', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #14: 3 members
(44, 14, 'member44@example.com', 'member', '44', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(45, 14, 'member45@example.com', 'member', '45', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(46, 14, 'member46@example.com', 'member', '46', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #15: 3 members
(47, 15, 'member47@example.com', 'member', '47', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(48, 15, 'member48@example.com', 'member', '48', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(49, 15, 'member49@example.com', 'member', '49', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #22: 1 member in inactive Project #4
(51, 22, 'member51@example.com', 'member', '51 (inactive project)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #23: 1 member in deleted Project #5
(52, 23, 'member52@example.com', 'member', '52 (deleted project)', NULL, NULL, NULL, NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL);

-- ============================================================================
-- PROJECT_USER (Member-Project assignments via family_id)
-- ============================================================================
-- Members are automatically associated with projects through their family
-- Additional project_user entries needed for:
-- - Member #5: inactive in project #1 (via family #2)
-- - Member #8: inactive in project #1 (via family #4)
-- ============================================================================

-- Family #2's member (#5) is inactive
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(5, 1, FALSE, NOW(), NOW());

-- Family #4's members (#7, #8, #9) - #8 is inactive
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(7, 1, TRUE,  NOW(), NOW()),
(8, 1, FALSE, NOW(), NOW());
-- Member #9 is soft-deleted so no project_user entry needed (will be inactive)

-- Family #5's members (#10-#19) - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(10, 1, TRUE, NOW(), NOW()),
(11, 1, TRUE, NOW(), NOW()),
(12, 1, TRUE, NOW(), NOW()),
(13, 1, TRUE, NOW(), NOW()),
(14, 1, TRUE, NOW(), NOW()),
(15, 1, TRUE, NOW(), NOW()),
(16, 1, TRUE, NOW(), NOW()),
(17, 1, TRUE, NOW(), NOW()),
(18, 1, TRUE, NOW(), NOW()),
(19, 1, TRUE, NOW(), NOW());

-- Families #6-#12 members (#20-#40) in Project #1 - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(20, 1, TRUE, NOW(), NOW()), (21, 1, TRUE, NOW(), NOW()), (22, 1, TRUE, NOW(), NOW()),
(23, 1, TRUE, NOW(), NOW()), (24, 1, TRUE, NOW(), NOW()), (25, 1, TRUE, NOW(), NOW()),
(26, 1, TRUE, NOW(), NOW()), (27, 1, TRUE, NOW(), NOW()), (28, 1, TRUE, NOW(), NOW()),
(29, 1, TRUE, NOW(), NOW()), (30, 1, TRUE, NOW(), NOW()), (31, 1, TRUE, NOW(), NOW()),
(32, 1, TRUE, NOW(), NOW()), (33, 1, TRUE, NOW(), NOW()), (34, 1, TRUE, NOW(), NOW()),
(35, 1, TRUE, NOW(), NOW()), (36, 1, TRUE, NOW(), NOW()), (37, 1, TRUE, NOW(), NOW()),
(38, 1, TRUE, NOW(), NOW()), (39, 1, TRUE, NOW(), NOW()), (40, 1, TRUE, NOW(), NOW());

-- Families #13-#15 members (#41-#49) in Project #2 - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(41, 2, TRUE, NOW(), NOW()), (42, 2, TRUE, NOW(), NOW()), (43, 2, TRUE, NOW(), NOW()),
(44, 2, TRUE, NOW(), NOW()), (45, 2, TRUE, NOW(), NOW()), (46, 2, TRUE, NOW(), NOW()),
(47, 2, TRUE, NOW(), NOW()), (48, 2, TRUE, NOW(), NOW()), (49, 2, TRUE, NOW(), NOW());

-- Member #51 in inactive Project #4
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(51, 4, TRUE, NOW(), NOW());

-- Member #52 in deleted Project #5
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(52, 5, TRUE, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF UNIVERSE FIXTURE
-- ============================================================================
