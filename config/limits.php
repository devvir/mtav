<?php

/**
 * Limits for projects a superadmin has not approved yet (see Project::limit()).
 *
 * Anyone can sign up and create a project; these bounds keep what is open to easy
 * abuse in check until a superadmin trusts the project's managers. Approved
 * projects have no limits. null disables a limit.
 */
return [
    'unapproved' => [
        /** Media */
        'media_storage_mb' => (int) env('LIMIT_MEDIA_STORAGE_MB', 200),
        'media_file_mb'    => (int) env('LIMIT_MEDIA_FILE_MB', 10),

        /** People */
        'members'             => (int) env('LIMIT_MEMBERS', 50),
        'invitations_per_day' => (int) env('LIMIT_INVITATIONS_PER_DAY', 20),

        /** Structure */
        'units'    => (int) env('LIMIT_UNITS', 60),
        'families' => (int) env('LIMIT_FAMILIES', 60),
    ],

    /**
     * Applies to every project: how many projects one person may create without a
     * superadmin's approval of any of them.
     */
    'unapproved_projects_per_user' => (int) env('LIMIT_UNAPPROVED_PROJECTS_PER_USER', 2),

    /** Days an invitation link stays valid */
    'invitation_days' => (int) env('INVITATION_DAYS', 14),

    /** Absolute ceiling for any uploaded file, approved projects included */
    'media_file_max_mb' => (int) env('MEDIA_FILE_MAX_MB', 200),
];
