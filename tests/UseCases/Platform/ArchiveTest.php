<?php

use App\Enums\NotificationTarget;
use App\Enums\NotificationType;
use App\Models\Media;
use App\Models\Notification;
use App\Models\Project;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Deleting a project: its admins delete it either way; one with a history is archived
 * (kept for audits, gone for everyone else), a fresh one is deleted for good. Superadmins
 * see the archived ones, restore them, or delete them for good once they type the name.
 */

beforeEach(function () {
    Storage::fake(Media::DISK);
});

function deleteAsAdmin($test, $admin, Project $project)
{
    return $test->as($admin, $project)->delete(route('manage.settings.destroy'), ['confirmation' => $project->name]);
}

it('deletes for good a project its creator only set up', function () {
    $creator = aUser();
    $project = anUnapprovedProject($creator);
    aUnitType($project);

    deleteAsAdmin($this, $creator, $project)->assertSessionHas('success', __('flash.project_deleted'));

    expect(Project::withTrashed()->find($project->id))->toBeNull();
});

it('deletes a project whose name has a comma or quotes in it', function () {
    $creator = aUser();
    $project = anUnapprovedProject($creator);
    $project->update(['name' => 'Cooperativa "Mesa 1", Montevideo']);

    deleteAsAdmin($this, $creator, $project->fresh())->assertSessionHasNoErrors();

    expect(Project::withTrashed()->find($project->id))->toBeNull();
});

it('archives a project others took part in, which then is gone for them', function () {
    $project = aProject();
    $admin = anAdminOf($project);
    $member = aMemberOf(aFamily($project));

    // To its admins it is deleted, as any other
    deleteAsAdmin($this, $admin, $project)->assertSessionHas('success', __('flash.project_deleted'));

    expect(Project::find($project->id))->toBeNull();
    expect(Project::withTrashed()->find($project->id))
        ->deleted_at->not->toBeNull()
        ->archived_by->toBe($admin->id);

    $this->as($member)->get(route('projects.show', $project))->assertNotFound();
});

it('archives a project that shared files, keeping them', function () {
    $creator = aUser();
    $project = anUnapprovedProject($creator);
    $this->as($creator, $project)->post(route('media.store'), [
        'description' => 'Building site',
        'files'       => [UploadedFile::fake()->image('site.jpg')],
    ])->assertSessionHasNoErrors();

    deleteAsAdmin($this, $creator, $project);

    expect(Project::withTrashed()->find($project->id)->deleted_at)->not->toBeNull();
    expect(Storage::disk(Media::DISK)->allFiles("projects/{$project->id}"))->not->toBeEmpty();
});

it('no longer tells people about an archived project', function () {
    $project = aProject();
    $member = aMemberOf(aFamily($project));
    app(NotificationService::class)->send(NotificationTarget::PROJECT, NotificationType::RESOURCE_CREATED, 'Assembly', 'On Friday', targetId: $project->id);

    expect(Notification::for($member)->count())->toBe(1);

    $project->delete();

    expect(Notification::for($member->fresh())->count())->toBe(0);
});

it('lands its people on their projects page, not on the archived one', function () {
    $project = aProject();
    $member = aMemberOf(aFamily($project));
    $project->delete();

    $this->as($member)->get(route('home'))->assertRedirect(route('projects.index'));
});

it('turns its pending invitations invalid', function () {
    $project = aProject();
    $admin = anAdminOf($project);
    $this->as($admin, $project)->post(route('manage.invitations.store'), [
        'email' => 'ana@udelar.edu.uy', 'firstname' => 'Ana', 'is_admin' => true,
    ])->assertSessionHasNoErrors();
    $project->delete();

    $invited = aUser(['email' => 'ana@udelar.edu.uy']);
    $this->as($invited)->get(route('projects.index'))->assertOk();
    expect($this->as($invited)->get(route('projects.index'))->inertiaProps('invitations'))->toBeEmpty();
});

describe('A superadmin', function () {
    beforeEach(function () {
        $this->project = aProject(['name' => 'Cooperativa Vieja']);
        $this->admin = anAdminOf($this->project);
        deleteAsAdmin($this, $this->admin, $this->project);
    });

    it('sees the archived projects apart, with what deleting them takes away', function () {
        $page = $this->as(aSuperadmin())->get(route('admin.projects.index', ['show' => 'archived']));

        expect($page->inertiaProps('projects.data.0'))
            ->name->toBe('Cooperativa Vieja')
            ->people_count->toBe(1)
            ->archived_by->toBe($this->admin->name);
        expect($page->inertiaProps('counts.archived'))->toBe(1);
        expect($this->as(aSuperadmin())->get(route('admin.projects.index'))->inertiaProps('projects.data'))->toBeEmpty();
    });

    it('restores one, as it was', function () {
        $this->as(aSuperadmin())->put(route('admin.projects.restore', $this->project->id))->assertSessionHasNoErrors();

        expect(Project::find($this->project->id))->deleted_at->toBeNull()->archived_by->toBeNull();
        $this->as($this->admin)->get(route('projects.show', $this->project))->assertOk();
    });

    it('deletes one for good only by typing its name, files and notifications too', function () {
        Storage::disk(Media::DISK)->put("projects/{$this->project->id}/photo.jpg", 'jpeg');
        app(NotificationService::class)->send(NotificationTarget::PROJECT, NotificationType::RESOURCE_CREATED, 'Assembly', 'On Friday', targetId: $this->project->id);

        $this->as(aSuperadmin())->delete(route('admin.projects.destroy', $this->project->id), ['confirmation' => 'Cooperativa'])
            ->assertSessionHasErrors('confirmation');
        expect(Project::withTrashed()->find($this->project->id))->not->toBeNull();

        $this->as(aSuperadmin())->delete(route('admin.projects.destroy', $this->project->id), ['confirmation' => 'Cooperativa Vieja'])
            ->assertSessionHasNoErrors();

        expect(Project::withTrashed()->find($this->project->id))->toBeNull();
        expect(Storage::disk(Media::DISK)->allFiles("projects/{$this->project->id}"))->toBeEmpty();
        expect(Notification::where('target', NotificationTarget::PROJECT)->where('target_id', $this->project->id)->exists())->toBeFalse();
    });
});

it('lets no one else restore or delete for good', function () {
    $project = aProject();
    $admin = anAdminOf($project);
    $project->delete();

    expect($this->as($admin)->put(route('admin.projects.restore', $project->id)))->toBeDenied();
    expect($this->as($admin)->delete(route('admin.projects.destroy', $project->id), ['confirmation' => $project->name]))->toBeDenied();
    expect(Project::withTrashed()->find($project->id))->not->toBeNull();
});
