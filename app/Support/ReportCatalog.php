<?php

namespace App\Support;

use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\ReportController;

/**
 * The exports that flow through the admin-approval download queue. Each type
 * maps to an existing report generator (controller method) so we don't
 * duplicate any logic — the download endpoint invokes it with the requester's
 * context. `cap` is the capability a user must hold to *request* that export.
 */
class ReportCatalog
{
    public const TYPES = [
        'cashier_pdf' => [
            'label' => 'Cashier payments report (PDF)', 'format' => 'pdf',
            'cap' => 'finance.view', 'module' => 'cashier',
            'controller' => CashierController::class, 'method' => 'report',
        ],
        'cashier_csv' => [
            'label' => 'Cashier payments (CSV)', 'format' => 'csv',
            'cap' => 'finance.view', 'module' => 'cashier',
            'controller' => CashierController::class, 'method' => 'exportCsv',
        ],
        'applicants_pdf' => [
            'label' => 'Applicants report (PDF)', 'format' => 'pdf',
            'cap' => 'export', 'module' => 'applicants',
            'controller' => ApplicantController::class, 'method' => 'report',
        ],
        'applicants_csv' => [
            'label' => 'Applicants list (CSV)', 'format' => 'csv',
            'cap' => 'export', 'module' => 'applicants',
            'controller' => ApplicantController::class, 'method' => 'exportCsv',
        ],
        'reports_applicants_csv' => [
            'label' => 'Applicants export', 'format' => 'csv',
            'cap' => 'pii.view', 'module' => 'reports',
            'controller' => ReportController::class, 'method' => 'applicantsCsv',
        ],
        'reports_payments_csv' => [
            'label' => 'Payments export', 'format' => 'csv',
            'cap' => 'finance.view', 'module' => 'reports',
            'controller' => ReportController::class, 'method' => 'paymentsCsv',
        ],
    ];

    public static function has(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    public static function get(string $type): ?array
    {
        return self::TYPES[$type] ?? null;
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function format(string $type): string
    {
        return self::TYPES[$type]['format'] ?? 'csv';
    }

    /** Human summary of the chosen filters, for the approval queue. */
    public static function describe(?array $params): string
    {
        if (empty($params)) {
            return 'No filters (all records)';
        }

        $parts = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $parts[] = \Illuminate\Support\Str::headline($key) . ': ' . (is_array($value) ? implode(', ', $value) : $value);
        }

        return $parts === [] ? 'No filters (all records)' : implode(' · ', $parts);
    }
}
