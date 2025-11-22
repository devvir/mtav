<?php

namespace Database\Factories;

use App\Models\Family;
use App\Models\Member;
use App\Models\Project;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<Member>
 */
class MemberFactory extends UserFactory
{
    protected $model = Member::class;

    public function configure(): static
    {
        $this->state(fn (array $attributes) => [
            'family_id' => Family::factory()->inProject($attributes['project_id']),
        ]);

        return $this->member()->afterCreating(
            fn (Member $member) => $member->family?->project->addMember($member)
        );
    }

    public function inProject(Project|int $projectOrId): static
    {
        $project = model($projectOrId, Project::class);

        return parent::inProject($project)->recycle($project->families);
    }

    public function inFamily(Family|int $family): static
    {
        return $this->state([
            'family_id' => $family->id ?? $family,
        ]);
    }
}
