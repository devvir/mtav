<?php

/**
 * Confirmation messages shown after an action.
 */
return [
    'not_allowed' => 'You are not allowed to do that.',

    /** Accounts */
    'verification_sent'       => 'We sent you a new confirmation link.',
    'email_verified'          => 'Your email address is confirmed. Welcome!',
    'email_verification_sent' => 'We sent a confirmation link to :email. Your address changes once you confirm it.',
    'email_changed'           => 'Your email address was changed.',
    'profile_updated'         => 'Your profile was saved.',
    'password_updated'        => 'Your password was changed.',
    'avatar_updated'          => 'Your picture was changed.',

    /** Projects */
    'project_created'    => 'Your project is ready. Start by adding its unit types.',
    'project_updated'    => 'The project was saved.',
    'project_deleted'    => 'The project was deleted.',
    'project_left'       => 'You left :project.',
    'approval_requested' => 'We asked the platform team to approve your project. You will hear from us soon.',
    'project_approved'   => ':project is approved: its limits are lifted.',
    'project_restored'   => ':project is back, as it was.',
    'project_purged'     => ':project was deleted for good.',

    /** People */
    'invitation_sent'     => 'Invitation sent to :email.',
    'invitation_revoked'  => 'The invitation was withdrawn.',
    'invitation_accepted' => 'Welcome to :project!',
    'invitation_declined' => 'You declined the invitation.',
    'invitation_sign_in'  => 'This invitation is for :email. Sign in with that account to accept it.',
    'member_updated'      => 'Saved.',
    'member_removed'      => 'The person was removed from the project.',
    'superadmin_granted'  => ':name is now a superadmin.',
    'superadmin_revoked'  => ':name is no longer a superadmin.',

    /** Structure */
    'family_created'    => 'The family was added.',
    'family_updated'    => 'The family was saved.',
    'family_deleted'    => 'The family was removed.',
    'unit_type_created' => 'The unit type was added.',
    'unit_type_updated' => 'The unit type was saved.',
    'unit_type_deleted' => 'The unit type was removed.',
    'unit_created'      => 'The unit was added.',
    'unit_updated'      => 'The unit was saved.',
    'unit_deleted'      => 'The unit was removed.',
    'plan_updated'      => 'The plan was saved.',

    /** Community */
    'event_created'      => 'The event was created.',
    'event_updated'      => 'The event was saved.',
    'event_deleted'      => 'The event was cancelled.',
    'rsvp_attending'     => 'Great, see you there!',
    'rsvp_not_attending' => 'Thanks for letting us know.',
    'media_uploaded'     => 'Your file is shared.|Your :count files are shared.',
    'media_updated'      => 'The description was saved.',
    'media_deleted'      => 'The file was removed.',

    /** Lottery */
    'lottery_scheduled'   => 'The lottery was scheduled.',
    'lottery_started'     => 'The lottery is running. Results appear here in a moment.',
    'lottery_invalidated' => 'The lottery was invalidated. Everyone in the project can read why.',

    'notifications_read' => 'All notifications are marked as read.',

    'deploy_dev'        => 'Dev is being deployed from :branch. Follow it below.',
    'deploy_production' => 'Production is being deployed: it waits for approval on GitHub.',
    'deploy_rollback'   => ':environment is going back to the release before.',
    'deploy_refused'    => 'GitHub did not start it (:status). Check the deploy token.',
];
