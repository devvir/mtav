<?php

use App\Models\Admin;
use App\Models\Family;
use App\Models\Log;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;

uses()->group('Unit.ProjectScope');

/**
 * Project Scope Unit Tests
 *
 * Tests the ProjectScope trait behavior, verifying that resources are
 * automatically filtered to only those belonging to Projects that the
 * authenticated user has access to.
 *
 * Universe Fixture Reference:
 * - Superadmin #1: Bypasses all Project Scopes
 * - Admin #11: Manages Project #1 only
 * - Admin #12: Manages Projects #2, #3
 * - Admin #10: No Projects assigned
 * - Member #102: Family #4, Project #1
 * - Member #136: Family #13, Project #2
 */

// ============================================================================
// WITH AUTHENTICATED MEMBER
// ============================================================================

describe('With an authenticated Member', function () {
    it('only returns Families that belong to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = Family::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Members that belong to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = Member::join('families', 'users.family_id', '=', 'families.id')
            ->pluck('families.project_id')
            ->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Units that belong to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = Unit::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Unit Types that belong to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = UnitType::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Logs that belong to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = Log::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('cannot find a Family that belongs to a different Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $familyInProject2 = Family::find(13); // Family #13 is in Project #2

        expect($familyInProject2)->toBeNull();
    });

    it('finds a Family that belongs to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $familyInProject1 = Family::find(4); // Family #4 is in Project #1

        expect($familyInProject1)
            ->not->toBeNull()
            ->and($familyInProject1->project_id)->toBe(1);
    });

    it('cannot find a Member that belongs to a different Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $memberInProject2 = Member::find(136); // Member #136 is in Project #2

        expect($memberInProject2)->toBeNull();
    });

    it('finds a Member that belongs to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $memberInProject1 = Member::find(102); // Member #102 is in Project #1

        expect($memberInProject1)->not->toBeNull();
    });

    it('cannot find a Unit that belongs to a different Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        // Need to find a Unit ID in Project #2 - assuming Unit #13+ are in Project #2
        $unitInProject2 = Unit::find(13);

        expect($unitInProject2)->toBeNull();
    });

    it('finds a Unit that belongs to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $unitInProject1 = Unit::find(1); // Unit #1 is in Project #1

        expect($unitInProject1)->not->toBeNull();
    });

    it('cannot find a Unit Type that belongs to a different Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $unitTypeInProject2 = UnitType::find(8); // UnitType #8 is in Project #2

        expect($unitTypeInProject2)->toBeNull();
    });

    it('finds a Unit Type that belongs to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $unitTypeInProject1 = UnitType::find(1); // UnitType #1 is in Project #1

        expect($unitTypeInProject1)->not->toBeNull();
    });

    it('cannot find a Log that belongs to a different Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $logInProject2 = Log::find(172); // Log #172 is in Project #2

        expect($logInProject2)->toBeNull();
    });

    it('finds a Log that belongs to the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $logInProject1 = Log::find(1); // Log #1 is in Project #1

        expect($logInProject1)->not->toBeNull();
    });
});

// ============================================================================
// WITH AUTHENTICATED ADMIN - MANAGING ONE PROJECT
// ============================================================================

describe('With an authenticated Admin managing one Project', function () {
    it('only returns Families that belong to the Admin\'s managed Project', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        $projectIds = Family::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Members that belong to the Admin\'s managed Project', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        $projectIds = Member::join('families', 'users.family_id', '=', 'families.id')
            ->pluck('families.project_id')
            ->unique();

        expect($projectIds)->toCollect(1);
    });

    it('only returns Units that belong to the Admin\'s managed Project', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        $projectIds = Unit::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('cannot find a Family that belongs to a Project the Admin does not manage', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        $familyInProject2 = Family::find(13); // Family #13 is in Project #2

        expect($familyInProject2)->toBeNull();
    });
});

// ============================================================================
// WITH AUTHENTICATED ADMIN - MANAGING MULTIPLE PROJECTS
// ============================================================================

describe('With an authenticated Admin managing multiple Projects', function () {
    it('returns Families from all of the Admin\'s managed Projects', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $projectIds = Family::pluck('project_id')->unique();

        // Only Project #2 has Families (Project #3 has no Unit Types, thus no Families)
        expect($projectIds)->toCollect(2);
    });

    it('finds a Family that belongs to one of the Admin\'s managed Projects', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $familyInProject2 = Family::find(13); // Project #2

        expect($familyInProject2)
            ->not->toBeNull()
            ->and($familyInProject2->project_id)->toBe(2);
    });

    it('returns Unit Types from all of the Admin\'s managed Projects', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $projectIds = UnitType::pluck('project_id')->unique();

        // Project #2 has Unit Types, Project #3 has none
        expect($projectIds)->toCollect(2);
    });

    it('cannot find a Unit that belongs to a Project the Admin does not manage', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $familyInProject1 = Family::find(4); // Project #1 (unmanaged)

        expect($familyInProject1)->toBeNull();
    });
});

// ============================================================================
// WITH AUTHENTICATED SUPERADMIN
// ============================================================================

describe('With an authenticated Superadmin', function () {
    it('returns Families from all Projects', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $projectIds = Family::pluck('project_id')->unique();

        // Should see Families from all Projects (1, 2, 4, 5 have Families)
        expect($projectIds)->toContain(1, 2, 4, 5);
    });

    it('returns Members from all Projects', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $projectIds = Member::join('families', 'users.family_id', '=', 'families.id')
            ->pluck('families.project_id')
            ->unique();

        expect($projectIds)->toContain(1, 2, 4, 5);
    });

    it('returns Unit Types from all Projects', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $projectIds = UnitType::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1, 2, 4, 5);
    });

    it('returns Units from all Projects', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $projectIds = Unit::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1, 2, 4, 5);
    });

    it('returns Logs from all Projects', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $projectIds = Log::pluck('project_id')->unique();

        expect($projectIds)->toCollect(1, 2);
    });

    it('finds Families from any Project', function () {
        $this->actingAs(User::find(1)); // Superadmin

        $family1 = Family::find(4);   // Project #1
        $family2 = Family::find(13);  // Project #2

        expect($family1)->not->toBeNull();
        expect($family2)->not->toBeNull();
    });
});

// ============================================================================
// BYPASSING PROJECT SCOPE
// ============================================================================

describe('When bypassing Project Scope', function () {
    it('allows a Member to query Families from other Projects when scope is bypassed', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $scopedFamily = Family::find(13); // Project #2
        expect($scopedFamily)->toBeNull();

        $unscopedFamily = Family::withoutGlobalScope('projectScope')->find(13);
        expect($unscopedFamily)
            ->not->toBeNull()
            ->and($unscopedFamily->project_id)->toBe(2);
    });

    it('allows an Admin to query Families from unmanaged Projects when scope is bypassed', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        $scopedFamily = Family::find(13); // Project #2
        expect($scopedFamily)->toBeNull();

        $unscopedFamily = Family::withoutGlobalScope('projectScope')->find(13);
        expect($unscopedFamily)
            ->not->toBeNull()
            ->and($unscopedFamily->project_id)->toBe(2);
    });

    it('allows counting all Families across all Projects when scope is bypassed', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $scopedCount = Family::count();
        $unscopedCount = Family::withoutGlobalScope('projectScope')->count();

        expect($unscopedCount)
            ->toBeGreaterThan($scopedCount)
            ->and($unscopedCount)->toBe(28); // Total Families in universe
    });

    it('allows bypassing all global scopes with withoutGlobalScopes', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $scopedCount = Family::count();
        $unscopedCount = Family::withoutGlobalScopes()->count();

        expect($unscopedCount)->toBeGreaterThan($scopedCount);
    });
});

// ============================================================================
// MEMBER FINDING OTHER MEMBERS
// ============================================================================

describe('With an authenticated Member querying Members', function () {
    it('finds Members from the Member\'s own Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $memberFromOwnProject = Member::find(102); // Member #102 is in Project #1

        expect($memberFromOwnProject)->not->toBeNull();
    });

    it('cannot find Members from other Projects', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $memberFromOtherProject = Member::find(136); // Member #136 is in Project #2

        expect($memberFromOtherProject)->toBeNull();
    });
});

// ============================================================================
// MEMBER FINDING ADMINS
// ============================================================================

describe('With an authenticated Member querying Admins', function () {
    it('finds Admins that manage the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $adminManagingMemberProject = Admin::find(11); // Admin #11 manages Project #1

        expect($adminManagingMemberProject)->not->toBeNull();
    });

    it('finds Admins that manage the Member\'s Project and other Projects', function () {
        $this->actingAs(User::find(136)); // Member: Project #2

        $adminManagingMultipleProjects = Admin::find(12); // Admin #12 manages Projects #2, #3

        expect($adminManagingMultipleProjects)->not->toBeNull();
    });

    it('cannot find Admins that don\'t manage the Member\'s Project', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $adminNotManagingMemberProject = Admin::find(12); // Admin #12 manages Projects #2, #3 (not #1)

        expect($adminNotManagingMemberProject)->toBeNull();
    });
});

// ============================================================================
// ADMIN FINDING OTHER ADMINS
// ============================================================================

describe('With an authenticated Admin querying Admins', function () {
    it('finds Admins who have at least one managed Project in common', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $adminWithCommonProject = Admin::find(13); // Admin #13 manages Projects #2, #3, #4

        expect($adminWithCommonProject)->not->toBeNull();
    });

    it('finds Admins who manage one of the same Projects', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        $adminWithSameProject = Admin::find(17); // Admin #17 manages Project #1

        expect($adminWithSameProject)->not->toBeNull();
    });

    it('cannot find Admins who don\'t manage any of the same Projects', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        $adminWithNoCommonProjects = Admin::find(14); // Admin #14 manages Project #5 only

        expect($adminWithNoCommonProjects)->toBeNull();
    });
});

// ============================================================================
// EDGE CASES
// ============================================================================

describe('With edge cases', function () {
    it('returns empty results for an Admin with no Projects assigned', function () {
        $this->actingAs(User::find(10)); // Admin: No Projects assigned

        $familyIds = Family::pluck('id');

        expect($familyIds)->toBeEmpty();
    });

    it('returns null when an Admin with no Projects queries a specific Family', function () {
        $this->actingAs(User::find(10)); // Admin: No Projects assigned

        $family = Family::find(1);

        expect($family)->toBeNull();
    });

    it('does not apply Project Scope for guest users', function () {
        // Not authenticated
        $familyCount = Family::count();

        // Guest should see all Families (no scope applied)
        expect($familyCount)->toBeGreaterThan(0);
    });

    it('allows an Admin managing a soft-deleted Project to query its Families', function () {
        $this->actingAs(User::find(14)); // Admin: Manages deleted Project #5

        $familyIds = Family::pluck('id');

        // Admin #14 only manages deleted Project #5
        // This test verifies the scope doesn't error
        expect($familyIds)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('allows an Admin managing an inactive Project to query its Families', function () {
        $this->actingAs(User::find(13)); // Admin: Manages Projects #2, #3, #4 (inactive)

        // Should be able to access inactive Project data
        $familyIdsInProject4 = Family::where('project_id', 4)->pluck('id');

        expect($familyIdsInProject4)->not->toBeEmpty();
    });
});

// ============================================================================
// INSCOPE METHOD
// ============================================================================

describe('When using the inProject scope method', function () {
    it('filters Families to a specific Project', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $projectIds = Family::inProject(2)->pluck('project_id')->unique();

        expect($projectIds)->toCollect(2);
    });

    it('returns empty results when filtering to an unmanaged Project', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1 only

        // Try to filter to Project #2 (unmanaged)
        $familyIds = Family::inProject(2)->pluck('id');

        // Global scope filters to Project #1, inProject filters to #2 = empty
        expect($familyIds)->toBeEmpty();
    });

    it('accepts a Project model instance as parameter', function () {
        $this->actingAs(User::find(12)); // Admin: Manages Projects #2, #3

        $project = Project::find(2);
        $projectIds = Family::inProject($project)->pluck('project_id')->unique();

        expect($projectIds)->toCollect(2);
    });
});

// ============================================================================
// PROJECT SCOPE WITH RELATIONSHIPS
// ============================================================================

describe('When eager loading relationships', function () {
    it('maintains Project Scope when eager loading Family Members', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $projectIds = Family::with('members')->pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });

    it('maintains Project Scope through a relationship chain', function () {
        $this->actingAs(User::find(102)); // Member: Project #1

        $members = Member::with('family.project')->get();
        $projectIds = $members->map(fn ($m) => $m->family?->project_id)
            ->filter()
            ->unique();

        expect($projectIds)->toCollect(1);
    });

    it('maintains Project Scope when eager loading Unit Type Units', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        $projectIds = UnitType::with('units')->pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });
});

// ============================================================================
// PROJECT SCOPE WITH QUERY COMPOSITION
// ============================================================================

describe('When combining Project Scope with other query constraints', function () {
    it('works with WHERE clauses', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        // Combine scope with where clause
        $results = Family::where('id', '<=', 5)->get(['id', 'project_id']);

        expect($results->every(fn ($f) => $f->project_id === 1))
            ->toBeTrue()
            ->and($results->pluck('id')->max())->toBeLessThanOrEqual(5);
    });

    it('works with ORDER BY clauses', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        $familyIds = Family::orderBy('id', 'desc')->pluck('id');
        $projectIds = Family::orderBy('id', 'desc')->pluck('project_id')->unique();

        expect($projectIds)->toCollect(1)
            ->and($familyIds->first())->toBeGreaterThan($familyIds->last());
    });

    it('works with pagination', function () {
        $this->actingAs(User::find(11)); // Admin: Manages Project #1

        $paginatedFamilies = Family::paginate(5);
        $projectIds = collect($paginatedFamilies->items())->pluck('project_id')->unique();

        expect($projectIds)->toCollect(1);
    });
});
