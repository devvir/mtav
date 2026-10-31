<?php

namespace App\Services\Assistant;

use App\Enums\ProjectFeature;
use App\Models\Project;
use App\Models\User;

/**
 * Where the Assistant may send someone: only what they could reach by themselves, each place
 * with what it holds. A link it writes names one of these; the address comes from here.
 */
class Places
{
    /**
     * @return array<string, array{about: string, route: string, params?: array<string, mixed>, tour?: string}>
     */
    public function for(User $user, Project $project): array
    {
        $manages = $user->manages($project);
        $family = (bool) $user->familyIn($project);

        return array_filter([
            'home'             => ['about' => 'the project at a glance', 'route' => 'projects.show', 'tour' => 'member.home'],
            'lottery'          => ['about' => 'when the lottery is, how it works, ranking units, results', 'route' => 'lottery.show', 'tour' => 'lottery'],
            'people'           => ['about' => 'families and people of the project', 'route' => 'families.index', 'tour' => 'member.families'],
            'units'            => ['about' => 'the units by type, and the plan of the site', 'route' => 'units.index'],
            'events'           => $project->hasFeature(ProjectFeature::EVENTS) ? ['about' => 'assemblies, work days, whether one attends', 'route' => 'events.index', 'tour' => 'member.events'] : null,
            'files'            => $project->hasFeature(ProjectFeature::GALLERY) || $project->hasFeature(ProjectFeature::FILES) ? ['about' => 'shared photos, videos, documents and recordings; sharing one', 'route' => 'media.index', 'tour' => 'member.files'] : null,
            'forum'            => $project->hasFeature(ProjectFeature::FORUM) ? ['about' => 'conversations among everyone in the project', 'route' => 'forum.index', 'tour' => 'member.forum'] : null,
            'invite_family'    => $family ? ['about' => 'inviting someone into their own family', 'route' => 'invitations.index', 'tour' => 'member.invitations'] : null,
            'setup'            => $manages ? ['about' => 'admins: setting the project up, step by step', 'route' => 'manage.overview', 'tour' => 'manage.setup'] : null,
            'invitations'      => $manages ? ['about' => 'admins: inviting people, those asking to join', 'route' => 'manage.invitations.index', 'tour' => 'manage.invitations'] : null,
            'project_settings' => $manages ? ['about' => 'admins: name, directory, guests, features on or off', 'route' => 'manage.settings.edit', 'tour' => 'manage.settings'] : null,
            'edit_plan'        => $manages ? ['about' => 'admins: drawing the plan of the site', 'route' => 'units.edit', 'tour' => 'manage.plan'] : null,
            'profile'          => ['about' => 'their own details, picture, password', 'route' => 'profile.edit', 'tour' => 'account.profile'],
            'preferences'      => ['about' => 'text size, language, light or dark, guided tours', 'route' => 'profile.edit', 'params' => ['section' => 'preferences']],
            'find_project'     => ['about' => 'the directory of projects to join', 'route' => 'directory.index', 'tour' => 'account.directory'],
            'your_projects'    => ['about' => 'every project they take part in', 'route' => 'projects.index', 'tour' => 'account.projects'],
            'guide'            => ['about' => 'the illustrated guide', 'route' => 'documentation.guide'],
        ]);
    }
}
