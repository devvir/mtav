-- ============================================================================
-- MTAV Test Universe Fixture
-- ============================================================================
-- A comprehensive, self-documenting test dataset that provides:
-- - Predictable IDs for easy assertions
-- - Self-descriptive names indicating state/relationships
-- - Coverage of common scenarios (active, inactive, deleted, empty, verified)
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
-- USERS - ADMINS (8 total)
-- ============================================================================
-- Distribution:
-- - IDs 1-9: Reserved for superadmins
--   - #1: Superadmin with NO projects assigned
-- - IDs 10-99: Regular admins
--   - #10: Admin with NO projects assigned
--   - #11: Admin managing 1 project (#1)
--   - #12: Admin managing 2 projects (#2, #3)
--   - #13: Admin managing 3 projects (#2, #3, #4) - inactive in #2
--   - #14: Admin managing deleted project #5 only
--   - #15: Admin managing project #2 and deleted project #5
--   - #16: Admin managing projects #2, #3, #4, and deleted #5
--   - #17: Unverified admin managing 1 project (#1) - for verification testing
--
-- Password: 'password' (hashed with bcrypt)
-- ============================================================================

TRUNCATE TABLE users;

INSERT INTO users (id, family_id, email, firstname, lastname, phone, avatar, legal_id, invitation_accepted_at, email_verified_at, password, is_admin, darkmode, remember_token, created_at, updated_at, deleted_at) VALUES
-- Superadmins (IDs 1-9)
(1, NULL, 'superadmin1@example.com', 'superadmin', '1 (no projects)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),

-- Admins (IDs 10-99, is_admin=true)
(10, NULL, 'admin10@example.com', 'admin', '10 (no projects)',       NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(11, NULL, 'admin11@example.com', 'admin', '11 (manages 1)',         NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(12, NULL, 'admin12@example.com', 'admin', '12 (manages 2,3)',       NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(13, NULL, 'admin13@example.com', 'admin', '13 (manages 2-,3+,4+)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(14, NULL, 'admin14@example.com', 'admin', '14 (manages deleted 5)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(15, NULL, 'admin15@example.com', 'admin', '15 (manages 2, deleted 5)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(16, NULL, 'admin16@example.com', 'admin', '16 (manages 2,3,4, deleted 5)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(17, NULL, 'admin17@example.com', 'admin (unverified)', '17 (no projects)', NULL, NULL, NULL, NULL, NULL, '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(18, NULL, 'invited18@example.com', 'admin (invited)', '18 (manages 1)', NULL, NULL, NULL, NULL, NULL, '$2y$12$SBnC3gnDbcL4ThpWSK30suml0mDnf38KGl5PRftC071JvsjXhVboS', TRUE, NULL, NULL, NOW(), NOW(), NULL),
(19, NULL, 'invited19@example.com', 'admin (invited)', '19 (manages 1,2)', NULL, NULL, NULL, NULL, NULL, '$2y$12$SBnC3gnDbcL4ThpWSK30suml0mDnf38KGl5PRftC071JvsjXhVboS', TRUE, NULL, NULL, NOW(), NOW(), NULL);

-- ============================================================================
-- PROJECT_USER (Admin-Project assignments)
-- ============================================================================
-- Superadmin #1: No assignments (remains unassigned)
-- Admin #10: No assignments (remains unassigned)
-- Admin #11: Manages Project #1 (active)
-- Admin #12: Manages Projects #2, #3 (both active)
-- Admin #13: Manages Projects #2 (inactive), #3, #4 (both active)
-- ============================================================================

TRUNCATE TABLE project_user;

INSERT INTO project_user (id, user_id, project_id, active, created_at, updated_at) VALUES
-- Admin #11 manages 1 project
(1, 11, 1, TRUE,  NOW(), NOW()),

-- Admin #12 manages 2 projects
(2, 12, 2, TRUE,  NOW(), NOW()),
(3, 12, 3, TRUE,  NOW(), NOW()),

-- Admin #13 manages 3 projects (inactive in #2)
(4, 13, 2, FALSE, NOW(), NOW()),
(5, 13, 3, TRUE,  NOW(), NOW()),
(6, 13, 4, TRUE,  NOW(), NOW()),

-- Admin #14 manages deleted Project #5
(7, 14, 5, TRUE, NOW(), NOW()),

-- Admin #15 manages 2 projects (one active, one deleted)
(8, 15, 2, TRUE, NOW(), NOW()),
(9, 15, 5, TRUE, NOW(), NOW()),

-- Admin #16 manages 4 projects (three active, one deleted)
(10, 16, 2, TRUE, NOW(), NOW()),
(11, 16, 3, TRUE, NOW(), NOW()),
(12, 16, 4, TRUE, NOW(), NOW()),
(13, 16, 5, TRUE, NOW(), NOW());

-- Admin #17 manages 1 project (unverified)
INSERT INTO project_user (id, user_id, project_id, active, created_at, updated_at) VALUES
(14, 17, 1, TRUE, NOW(), NOW()),

-- Admin #18 manages 1 project (invited)
(15, 18, 1, TRUE, NOW(), NOW()),

-- Admin #19 manages 2 projects (invited)
(16, 19, 1, TRUE, NOW(), NOW()),
(17, 19, 2, TRUE, NOW(), NOW());

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
(23, 5, 10, 'Family 23 (deleted project)', NULL, NOW(), NOW(), NULL),

-- Project #1 test family (for unverified member test)
(24, 1, 1, 'Family 24 (unverified member)', NULL, NOW(), NOW(), NULL),

-- Project #1 test family (for invited member test)
(25, 1, 1, 'Family 25 (invited member)', NULL, NOW(), NOW(), NULL),

-- Project #2 test family (for invited member test)
(26, 2, 4, 'Family 26 (invited member)', NULL, NOW(), NOW(), NULL);

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
-- USERS - MEMBERS (48 total, IDs 100-149)
-- ============================================================================
-- Distribution by Family:
-- - Family #1: 0 members (edge case)
-- - Family #2: 1 member (#100, inactive via project_user)
-- - Family #3: 1 member (#101, soft-deleted)
-- - Family #4: 3 members (#102 active, #103 inactive, #104 deleted)
-- - Family #5: 10 members (#105-#114) for pagination tests
-- - Families #6-#15: 3 members each (#115-#144)
-- - Family #22: 1 member (#145) in inactive Project #4
-- - Family #23: 1 member (#146) in deleted Project #5
-- - Unassigned: 1 unverified member (#200) for testing verification
--
-- Note: Members are inserted with sequential IDs starting at 100
-- Password: 'password' (hashed with bcrypt)
-- ============================================================================

INSERT INTO users (id, family_id, email, firstname, lastname, phone, avatar, legal_id, invitation_accepted_at, email_verified_at, password, is_admin, darkmode, remember_token, created_at, updated_at, deleted_at) VALUES
-- Family #2: 1 member (inactive)
(100, 2, 'member100@example.com', 'member (inactive)', '100', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #3: 1 member (deleted)
(101, 3, 'member101@example.com', 'member (deleted)', '101', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NOW()),

-- Family #4: 3 members (1 active, 1 inactive, 1 deleted)
(102, 4, 'member102@example.com', 'member',            '102', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(103, 4, 'member103@example.com', 'member (inactive)', '103', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(104, 4, 'member104@example.com', 'member (deleted)',  '104', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NOW()),

-- Family #5: 10 members (pagination tests)
(105, 5, 'member105@example.com', 'member', '105', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(106, 5, 'member106@example.com', 'member', '106', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(107, 5, 'member107@example.com', 'member', '107', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(108, 5, 'member108@example.com', 'member', '108', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(109, 5, 'member109@example.com', 'member', '109', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(110, 5, 'member110@example.com', 'member', '110', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(111, 5, 'member111@example.com', 'member', '111', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(112, 5, 'member112@example.com', 'member', '112', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(113, 5, 'member113@example.com', 'member', '113', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(114, 5, 'member114@example.com', 'member', '114', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #6: 3 members
(115, 6, 'member115@example.com', 'member', '115', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(116, 6, 'member116@example.com', 'member', '116', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(117, 6, 'member117@example.com', 'member', '117', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #7: 3 members
(118, 7, 'member118@example.com', 'member', '118', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(119, 7, 'member119@example.com', 'member', '119', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(120, 7, 'member120@example.com', 'member', '120', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #8: 3 members
(121, 8, 'member121@example.com', 'member', '121', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(122, 8, 'member122@example.com', 'member', '122', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(123, 8, 'member123@example.com', 'member', '123', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #9: 3 members
(124, 9, 'member124@example.com', 'member', '124', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(125, 9, 'member125@example.com', 'member', '125', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(126, 9, 'member126@example.com', 'member', '126', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #10: 3 members
(127, 10, 'member127@example.com', 'member', '127', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(128, 10, 'member128@example.com', 'member', '128', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(129, 10, 'member129@example.com', 'member', '129', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #11: 3 members
(130, 11, 'member130@example.com', 'member', '130', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(131, 11, 'member131@example.com', 'member', '131', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(132, 11, 'member132@example.com', 'member', '132', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #12: 3 members
(133, 12, 'member133@example.com', 'member', '133', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(134, 12, 'member134@example.com', 'member', '134', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(135, 12, 'member135@example.com', 'member', '135', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #13: 3 members
(136, 13, 'member136@example.com', 'member', '136', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(137, 13, 'member137@example.com', 'member', '137', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(138, 13, 'member138@example.com', 'member', '138', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #14: 3 members
(139, 14, 'member139@example.com', 'member', '139', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(140, 14, 'member140@example.com', 'member', '140', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(141, 14, 'member141@example.com', 'member', '141', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #15: 3 members
(142, 15, 'member142@example.com', 'member', '142', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(143, 15, 'member143@example.com', 'member', '143', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),
(144, 15, 'member144@example.com', 'member', '144', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #22: 1 member in inactive Project #4
(145, 22, 'member145@example.com', 'member', '145 (inactive project)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #23: 1 member in deleted Project #5
(146, 23, 'member146@example.com', 'member', '146 (deleted project)', NULL, NULL, NULL, NOW(), NOW(), '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #24: 1 unverified member in Project #1 (for verification testing)
(147, 24, 'unverified@example.com', 'unverified', 'member', NULL, NULL, NULL, NULL, NULL, '$2y$12$32i/Xcc5RXjhGsrKerd/6e/kBdwZ8LfPpSJXKOBbpTc.z2IKgU/5e', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #25: 1 invited member in Project #1
(148, 25, 'invited148@example.com', 'member (invited)', '148 (in project 1)', NULL, NULL, NULL, NULL, NULL, '$2y$12$SBnC3gnDbcL4ThpWSK30suml0mDnf38KGl5PRftC071JvsjXhVboS', FALSE, NULL, NULL, NOW(), NOW(), NULL),

-- Family #26: 1 invited member in Project #2
(149, 26, 'invited149@example.com', 'member (invited)', '149 (in project 2)', NULL, NULL, NULL, NULL, NULL, '$2y$12$SBnC3gnDbcL4ThpWSK30suml0mDnf38KGl5PRftC071JvsjXhVboS', FALSE, NULL, NULL, NOW(), NOW(), NULL);

-- ============================================================================
-- PROJECT_USER (Member-Project assignments via family_id)
-- ============================================================================
-- Members are automatically associated with projects through their family
-- Additional project_user entries needed for:
-- - Member #100: inactive in project #1 (via family #2)
-- - Member #103: inactive in project #1 (via family #4)
-- ============================================================================

-- Family #2's member (#100) is inactive
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(100, 1, FALSE, NOW(), NOW());

-- Family #4's members (#102, #103, #104) - #103 is inactive
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(102, 1, TRUE,  NOW(), NOW()),
(103, 1, FALSE, NOW(), NOW());
-- Member #104 is soft-deleted so no project_user entry needed (will be inactive)

-- Family #5's members (#105-#114) - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(105, 1, TRUE, NOW(), NOW()),
(106, 1, TRUE, NOW(), NOW()),
(107, 1, TRUE, NOW(), NOW()),
(108, 1, TRUE, NOW(), NOW()),
(109, 1, TRUE, NOW(), NOW()),
(110, 1, TRUE, NOW(), NOW()),
(111, 1, TRUE, NOW(), NOW()),
(112, 1, TRUE, NOW(), NOW()),
(113, 1, TRUE, NOW(), NOW()),
(114, 1, TRUE, NOW(), NOW());

-- Families #6-#12 members (#115-#135) in Project #1 - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(115, 1, TRUE, NOW(), NOW()), (116, 1, TRUE, NOW(), NOW()), (117, 1, TRUE, NOW(), NOW()),
(118, 1, TRUE, NOW(), NOW()), (119, 1, TRUE, NOW(), NOW()), (120, 1, TRUE, NOW(), NOW()),
(121, 1, TRUE, NOW(), NOW()), (122, 1, TRUE, NOW(), NOW()), (123, 1, TRUE, NOW(), NOW()),
(124, 1, TRUE, NOW(), NOW()), (125, 1, TRUE, NOW(), NOW()), (126, 1, TRUE, NOW(), NOW()),
(127, 1, TRUE, NOW(), NOW()), (128, 1, TRUE, NOW(), NOW()), (129, 1, TRUE, NOW(), NOW()),
(130, 1, TRUE, NOW(), NOW()), (131, 1, TRUE, NOW(), NOW()), (132, 1, TRUE, NOW(), NOW()),
(133, 1, TRUE, NOW(), NOW()), (134, 1, TRUE, NOW(), NOW()), (135, 1, TRUE, NOW(), NOW());

-- Families #13-#15 members (#136-#144) in Project #2 - all active
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(136, 2, TRUE, NOW(), NOW()), (137, 2, TRUE, NOW(), NOW()), (138, 2, TRUE, NOW(), NOW()),
(139, 2, TRUE, NOW(), NOW()), (140, 2, TRUE, NOW(), NOW()), (141, 2, TRUE, NOW(), NOW()),
(142, 2, TRUE, NOW(), NOW()), (143, 2, TRUE, NOW(), NOW()), (144, 2, TRUE, NOW(), NOW());

-- Member #145 in inactive Project #4
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(145, 4, TRUE, NOW(), NOW());

-- Member #146 in deleted Project #5
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(146, 5, TRUE, NOW(), NOW());

-- Member #147 (unverified) in Project #1
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(147, 1, TRUE, NOW(), NOW());

-- Member #148 (invited) in Project #1
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(148, 1, TRUE, NOW(), NOW());

-- Member #149 (invited) in Project #2
INSERT INTO project_user (user_id, project_id, active, created_at, updated_at) VALUES
(149, 2, TRUE, NOW(), NOW());

-- ============================================================================
-- LOGS (4 total)
-- ============================================================================
-- Distribution by Project:
-- - Project #1: 2 logs (#1 by Member #102, #2 by Admin #11)
-- - Project #2: 2 logs (#3 by Member #136, #4 by Admin #12)
-- ============================================================================

TRUNCATE TABLE logs;

INSERT INTO logs (id, event, user_id, project_id, created_at, updated_at) VALUES
-- Project #1 logs
(1, 'Member logged in', 102, 1, NOW(), NOW()),
(2, 'Admin updated project settings', 11, 1, NOW(), NOW()),

-- Project #2 logs
(3, 'Member viewed dashboard', 136, 2, NOW(), NOW()),
(4, 'Admin created new family', 12, 2, NOW(), NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF UNIVERSE FIXTURE
-- ============================================================================
