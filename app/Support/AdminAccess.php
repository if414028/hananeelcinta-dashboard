<?php

declare(strict_types=1);

namespace App\Support;

final class AdminAccess
{
    /** @var list<string> */
    public const ADMIN_PERMISSIONS = [
        'congregations.view', 'congregations.create', 'congregations.update', 'congregations.delete', 'congregations.export',
        'prayer_requests.view', 'prayer_requests.view_confidential', 'prayer_requests.update', 'prayer_requests.delete', 'prayer_requests.export',
        'family_altars.view', 'family_altars.create', 'family_altars.update', 'family_altars.delete',
        'events.view', 'events.create', 'events.update', 'events.delete', 'events.registrations', 'events.check_in',
        'announcements.view', 'announcements.create', 'announcements.update', 'announcements.delete', 'announcements.publish',
        'pastor_messages.view', 'pastor_messages.create', 'pastor_messages.update', 'pastor_messages.delete', 'pastor_messages.publish',
    ];

    public static function isSuperAdminPermission(string $permission): bool
    {
        return in_array(explode('.', $permission)[0], ['dashboard', 'admins', 'settings', 'audit_logs'], true);
    }
}
