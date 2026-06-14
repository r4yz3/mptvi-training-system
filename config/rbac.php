<?php

/*
|--------------------------------------------------------------------------
| MPTVI RBAC definition — single source of truth
|--------------------------------------------------------------------------
| Ported verbatim from the demo (live-backup-index.html: ROLES / PERMS / MODULES).
| - roles:        the 5 staff roles + display labels
| - capabilities: every permission cap used across the app
| - matrix:       role => [caps]   (admin implicitly gets all via Gate::before)
| - modules:      sidebar nav items + which roles may see/enter each
*/

return [

    'roles' => [
        'admin'       => 'Administrator',
        'manager'     => 'Secretary',
        'registrar'   => 'Registrar',
        'cashier'     => 'Cashier',
        'coordinator' => 'Training Coordinator',
    ],

    // All capability strings. admin holds '*' (handled by Gate::before).
    'capabilities' => [
        'applicant.create',
        'applicant.edit',
        'applicant.delete', // admin only
        'active',           // activate / deactivate applicant
        'screen',
        'docs.verify',
        'payment.record',
        'payment.void',
        'attendance',
        'assess',
        'cert.assessor',    // edit the assessor printed on a trainee's certificate
        'id.issue',
        'program.manage',
        'event.manage',
        'pii.view',
        'finance.view',     // ₱ aggregates (admin + cashier)
        'export',
        'download.request', // request a report/CSV export (queued for admin approval)
        'download.approve', // approve/reject export requests + download directly (admin)
        'settings',         // admin only
    ],

    // role => caps (admin omitted — granted everything by Gate::before)
    'matrix' => [
        'manager' => [
            'applicant.create', 'applicant.edit', 'active', 'screen', 'docs.verify',
            'attendance', 'assess', 'cert.assessor', 'id.issue', 'program.manage', 'event.manage', 'pii.view',
            'download.request',
        ],
        'registrar' => [
            'applicant.create', 'applicant.edit', 'active', 'screen', 'docs.verify',
            'cert.assessor', 'id.issue', 'export', 'pii.view', 'download.request',
        ],
        'cashier' => [
            'payment.record', 'payment.void',
            // Cashier now sees the finance aggregates + collections chart, but every
            // report/CSV export must be approved by an admin (see the Downloads module).
            'finance.view', 'download.request',
        ],
        'coordinator' => [
            'attendance', 'assess', 'cert.assessor', 'program.manage',
        ],
    ],

    // Sidebar nav. icon = lucide-react icon name (kebab → mapped in React). group = sidebar section.
    'modules' => [
        ['id' => 'dashboard',  'label' => 'Dashboard',             'icon' => 'layout-dashboard', 'group' => 'Overview',       'roles' => ['admin', 'manager', 'registrar', 'cashier', 'coordinator']],
        ['id' => 'applicants', 'label' => 'Applicants',            'icon' => 'users',            'group' => 'Enrollment',     'roles' => ['admin', 'manager', 'registrar', 'cashier', 'coordinator']],
        ['id' => 'screening',  'label' => 'Screening',             'icon' => 'clipboard-check',  'group' => 'Enrollment',     'roles' => ['admin', 'manager', 'registrar']],
        ['id' => 'cashier',    'label' => 'Cashier',               'icon' => 'banknote',         'group' => 'Enrollment',     'roles' => ['admin', 'cashier']],
        ['id' => 'programs',   'label' => 'Programs & batches',    'icon' => 'calendar-days',    'group' => 'Training',       'roles' => ['admin', 'manager', 'coordinator']],
        ['id' => 'training',   'label' => 'Training & attendance', 'icon' => 'graduation-cap',   'group' => 'Training',       'roles' => ['admin', 'manager', 'coordinator']],
        ['id' => 'assessment', 'label' => 'Assessment & certs',    'icon' => 'award',            'group' => 'Training',       'roles' => ['admin', 'manager', 'registrar', 'coordinator']],
        ['id' => 'idsystem',   'label' => 'ID system',             'icon' => 'id-card',          'group' => 'Training',       'roles' => ['admin', 'manager', 'registrar']],
        ['id' => 'messages',   'label' => 'Messages',              'icon' => 'message-square',   'group' => 'Communication',  'roles' => ['admin', 'manager', 'registrar', 'cashier', 'coordinator']],
        ['id' => 'events',     'label' => 'Calendar & events',     'icon' => 'megaphone',        'group' => 'Communication',  'roles' => ['admin', 'manager', 'registrar', 'cashier', 'coordinator']],
        ['id' => 'reports',    'label' => 'Reports',               'icon' => 'bar-chart-3',      'group' => 'Administration', 'roles' => ['admin', 'manager']],
        ['id' => 'downloads',  'label' => 'Downloads',             'icon' => 'download',         'group' => 'Administration', 'roles' => ['admin', 'manager', 'registrar', 'cashier']],
        ['id' => 'activity',   'label' => 'Activity log',          'icon' => 'history',          'group' => 'Administration', 'roles' => ['admin', 'manager']],
        ['id' => 'users',      'label' => 'Users',                 'icon' => 'user-cog',         'group' => 'Administration', 'roles' => ['admin']],
        ['id' => 'settings',   'label' => 'Settings',              'icon' => 'settings',         'group' => 'Administration', 'roles' => ['admin']],
    ],

];
