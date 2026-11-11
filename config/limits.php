<?php

/**
 * Limits for projects a superadmin has not verified yet (see Project::limit()).
 *
 * Anyone can sign up and create a project; these bounds keep what is open to easy abuse
 * in check (room for most cooperatives, never a reason to ask), until a superadmin trusts
 * the project's managers. Verified projects have no limits. null disables a limit.
 */
return [
    'unapproved' => [
        /** Media */
        'media_storage_mb' => (int) env('LIMIT_MEDIA_STORAGE_MB', 500),
        'media_file_mb'    => (int) env('LIMIT_MEDIA_FILE_MB', 25),

        /** People */
        'members'             => (int) env('LIMIT_MEMBERS', 200),
        'invitations_per_day' => (int) env('LIMIT_INVITATIONS_PER_DAY', 150),

        /** Structure */
        'units'    => (int) env('LIMIT_UNITS', 50),
        'families' => (int) env('LIMIT_FAMILIES', 50),
    ],

    /**
     * Applies to every project: how many projects one person may create without a
     * superadmin's approval of any of them.
     */
    'unapproved_projects_per_user' => (int) env('LIMIT_UNAPPROVED_PROJECTS_PER_USER', 2),

    /** Days an invitation link stays valid */
    'invitation_days' => (int) env('INVITATION_DAYS', 14),

    /** Invitations one project may email one address an hour (sent again or not), every project alike */
    'invitations_per_address_per_hour' => (int) env('LIMIT_INVITATIONS_PER_ADDRESS_PER_HOUR', 3),

    /**
     * Hours in which a project its creator deleted still counts towards the projects they may
     * start unapproved: starting and deleting over and over tells the platform's team each time
     */
    'removed_projects_count_hours' => (int) env('REMOVED_PROJECTS_COUNT_HOURS', 24),

    /** How many groups of a project's chat one person may be in (Community aside) */
    'chat_groups_per_person' => (int) env('LIMIT_CHAT_GROUPS_PER_PERSON', 50),

    /** Minutes after sending in which a chat message can still be edited or deleted by whoever wrote it */
    'chat_changes_minutes' => (int) env('CHAT_CHANGES_MINUTES', 60),

    /** Days before someone declined may ask to join the same project again */
    'join_request_retry_days' => (int) env('JOIN_REQUEST_RETRY_DAYS', 30),

    /** Hours after someone asks to join in which asking the same again tells nobody again */
    'asked_again_quiet_hours' => (int) env('ASKED_AGAIN_QUIET_HOURS', 24),

    /** Open lotteries: anyone may write in them, so each stays small and none stays for ever */
    'open_lottery' => [
        'participants' => (int) env('OPEN_LOTTERY_PARTICIPANTS', 50),
        'items'        => (int) env('OPEN_LOTTERY_ITEMS', 100),
        'kept_days'    => (int) env('OPEN_LOTTERY_KEPT_DAYS', 90),
    ],

    /** Days what only piles up is kept (PruneLogs); the rest of the activity log stays, its IPs erased */
    'logs' => [
        'ip_days'              => (int) env('LOG_IP_DAYS', 90),
        'failed_sign_ins_days' => (int) env('LOG_FAILED_SIGN_INS_DAYS', 90),
        'errors_days'          => (int) env('LOG_ERRORS_DAYS', 180),
        'failed_jobs_days'     => (int) env('LOG_FAILED_JOBS_DAYS', 30),
    ],

    /** Absolute ceiling for any uploaded file, approved projects included */
    'media_file_max_mb' => (int) env('MEDIA_FILE_MAX_MB', 200),
];
