<?php

// Copilot - pending review

namespace App\Http\Controllers\Dev;

use App\Mail\AdminInvitationMail;
use App\Mail\MemberInvitationMail;
use App\Models\Admin;
use App\Models\Member;
use App\Models\Project;
use App\Services\InvitationTokenService;
use Illuminate\Support\Facades\Mail;

class TestEmailController
{
    public function sendTestEmails()
    {
        // CHANGE THIS TO YOUR REAL EMAIL ADDRESS
        $testEmail = 'cuisdy@gmail.com'; // <-- PUT YOUR EMAIL HERE

        // Get first project for testing
        $project = Project::with('families')->first();

        if (!$project) {
            return response()->json(['error' => 'No projects found. Run php artisan db:seed first.'], 404);
        }

        $family = $project->families->first();

        if (!$family) {
            return response()->json(['error' => 'No families found. Run php artisan db:seed first.'], 404);
        }

        // Generate test token
        $tokenData = InvitationTokenService::generate();
        $token = $tokenData['token'];

        // Create test member with relationships
        $member = new Member([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => $testEmail,
        ]);
        $member->id = 999; // Fake ID for URL generation
        $member->setRelation('family', $family);

        // Create test admin with relationships
        $admin = new Admin([
            'firstname' => 'Jane',
            'lastname' => 'Smith',
            'email' => $testEmail,
        ]);
        $admin->id = 999; // Fake ID for URL generation

        // Get some projects for admin
        $projects = Project::take(3)->get();
        $admin->setRelation('projects', $projects);

        // Send Member Invitation
        Mail::to($testEmail)->send(new MemberInvitationMail($member, $token));

        // Send Admin Invitation
        Mail::to($testEmail)->send(new AdminInvitationMail($admin, $token));

        return response()->json([
            'success' => true,
            'message' => 'Test emails sent successfully!',
            'sent_to' => $testEmail,
            'emails' => [
                'member_invitation' => 'Sent with project: ' . $project->name . ', family: ' . $family->name,
                'admin_invitation' => 'Sent with ' . $projects->count() . ' project(s): ' . $projects->pluck('name')->join(', '),
            ],
        ]);
    }
}
