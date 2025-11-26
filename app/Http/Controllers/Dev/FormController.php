<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Event;
use App\Models\Family;
use App\Models\Member;
use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitType;
use App\Services\Form\FormService;
use App\Services\Form\FormType;
use Inertia\Response;

class FormController extends Controller
{
    /**
     * Display form component previews and testing interface.
     */
    public function __invoke(): Response
    {
        // Fetch sample entities for UPDATE forms
        $sampleProject = Project::first();
        $sampleAdmin = Admin::first();
        $sampleMember = Member::first();
        $sampleFamily = Family::first();
        $sampleUnit = Unit::first();
        $sampleUnitType = UnitType::first();
        $sampleEvent = Event::first();

        // Generate FormService JSON for each entity
        $formSpecs = [
            'project' => [
                'create' => FormService::make(Project::class, FormType::CREATE),
                'update' => $sampleProject ? FormService::make($sampleProject, FormType::UPDATE) : null,
            ],
            'admin' => [
                'create' => FormService::make(Admin::class, FormType::CREATE),
                'update' => $sampleAdmin ? FormService::make($sampleAdmin, FormType::UPDATE) : null,
            ],
            'member' => [
                'create' => FormService::make(Member::class, FormType::CREATE),
                'update' => $sampleMember ? FormService::make($sampleMember, FormType::UPDATE) : null,
            ],
            'family' => [
                'create' => FormService::make(Family::class, FormType::CREATE),
                'update' => $sampleFamily ? FormService::make($sampleFamily, FormType::UPDATE) : null,
            ],
            'unit' => [
                'create' => FormService::make(Unit::class, FormType::CREATE),
                'update' => $sampleUnit ? FormService::make($sampleUnit, FormType::UPDATE) : null,
            ],
            'unitType' => [
                'create' => FormService::make(UnitType::class, FormType::CREATE),
                'update' => $sampleUnitType ? FormService::make($sampleUnitType, FormType::UPDATE) : null,
            ],
            'event' => [
                'create' => FormService::make(Event::class, FormType::CREATE),
                'update' => $sampleEvent ? FormService::make($sampleEvent, FormType::UPDATE) : null,
            ],
        ];

        return inertia('Dev/Forms', [
            'formSpecs' => $formSpecs,
        ]);
    }
}
