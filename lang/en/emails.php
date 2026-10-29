<?php

return [
    'greeting'      => 'Hello!',
    'greeting_name' => 'Hello :name!',

    'invitation_subject'      => 'You are invited to :project',
    'invitation_family'       => ':inviter invites you to join the :family family in :project.',
    'invitation_admin'        => ':inviter invites you to help manage :project.',
    'invitation_admin_family' => ':inviter invites you to help manage :project, as a member of the :family family.',
    'invitation_action'       => 'Accept the invitation',
    'invitation_expiry'       => 'The invitation is valid until :date.',
    'invitation_ignore'       => 'If you were not expecting it, you can ignore this email.',

    'reset_password_subject' => 'Choose a new password',
    'reset_password_intro'   => 'Someone asked to reset the password of your account.',
    'reset_password_action'  => 'Choose a new password',
    'reset_password_expiry'  => 'The link is valid for :minutes minutes.',
    'reset_password_ignore'  => 'If it was not you, your password stays as it is: ignore this email.',

    'verify_email_subject'   => 'Confirm your email address',
    'verify_email_intro'     => 'Please confirm this is your email address.',
    'verify_new_email_intro' => 'Please confirm this is your new email address. Your account keeps using the old one until you do.',
    'verify_email_action'    => 'Confirm my email',
    'verify_email_ignore'    => 'If you did not create an account, ignore this email.',

    'email_change_subject' => 'Your account\'s email is changing',
    'email_change_intro'   => 'Someone asked to change your account\'s email address to :email. It changes once that address confirms it.',
    'email_change_ignore'  => 'If it was not you, change your password now: your account keeps this address until then.',
];
