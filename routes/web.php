<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApplicantController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BatchController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FormBuilderController;
use App\Http\Controllers\IdController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScreeningController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Users (Admin only)
    Route::middleware('module:users')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Applicants (P2) — all roles may view the module; action-level caps
    // (create/edit/delete/active/pii.view) are enforced inside the controller.
    Route::middleware('module:applicants')->group(function () {
        Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('/applicants/create', [ApplicantController::class, 'create'])->name('applicants.create');
        Route::get('/applicants/export.csv', [ApplicantController::class, 'exportCsv'])->middleware('can:download.approve')->name('applicants.export');
        Route::get('/applicants/report', [ApplicantController::class, 'report'])->middleware('can:download.approve')->name('applicants.report');
        Route::post('/applicants', [ApplicantController::class, 'store'])->name('applicants.store');
        Route::get('/applicants/{applicant}/print', [ApplicantController::class, 'print'])->name('applicants.print');
        Route::get('/applicants/{applicant}', [ApplicantController::class, 'show'])->name('applicants.show');
        Route::get('/applicants/{applicant}/edit', [ApplicantController::class, 'edit'])->name('applicants.edit');
        Route::post('/applicants/{applicant}', [ApplicantController::class, 'update'])->name('applicants.update');
        Route::delete('/applicants/{applicant}', [ApplicantController::class, 'destroy'])->name('applicants.destroy');
        Route::put('/applicants/{applicant}/active', [ApplicantController::class, 'toggleActive'])->name('applicants.toggleActive');
        Route::put('/applicants/{applicant}/trainee-status', [ApplicantController::class, 'updateTraineeStatus'])->name('applicants.traineeStatus');

        // Documents — note-only: a typed note + status per requirement (no uploads).
        // docs.verify gates writing; the checklist is pii.view-gated in the controller.
        Route::post('/applicants/{applicant}/documents', [DocumentController::class, 'save'])->name('documents.save');
    });

    // Screening (P3) — registrar/secretary/admin; screen cap gates the actions.
    Route::middleware('module:screening')->group(function () {
        Route::get('/screening', [ScreeningController::class, 'index'])->name('screening.index');
        Route::put('/screening/{applicant}/qualify', [ScreeningController::class, 'qualify'])->name('screening.qualify');
        Route::put('/screening/{applicant}/disqualify', [ScreeningController::class, 'disqualify'])->name('screening.disqualify');
    });

    // Programs & batches (P5) — admin/secretary/coordinator; program.manage gates writes.
    Route::middleware('module:programs')->group(function () {
        Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index');
        Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store');
        Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update');
        Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy');
        Route::post('/programs/{program}/learners', [ProgramController::class, 'assign'])->name('programs.assign');
        Route::post('/batches', [BatchController::class, 'store'])->name('batches.store');
        Route::put('/batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
        Route::delete('/batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
        Route::post('/batches/{batch}/learners', [BatchController::class, 'assign'])->name('batches.assign');
        Route::delete('/batches/{batch}/learners/{applicant}', [BatchController::class, 'unassign'])->name('batches.unassign');
    });

    // Cashier (P6) — admin/cashier; payment.record / payment.void gate the actions.
    Route::middleware('module:cashier')->group(function () {
        Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');
        // Direct download is admin-only; other staff route through the Downloads approval queue.
        Route::get('/cashier/report', [CashierController::class, 'report'])->middleware('can:download.approve')->name('cashier.report');
        Route::get('/cashier/export.csv', [CashierController::class, 'exportCsv'])->middleware('can:download.approve')->name('cashier.export');
        Route::post('/cashier/{applicant}/payments', [CashierController::class, 'record'])->name('cashier.record');
        Route::get('/cashier/payments/{payment}/receipt', [CashierController::class, 'receipt'])->name('cashier.receipt');
        Route::put('/cashier/payments/{payment}/void', [CashierController::class, 'void'])->name('cashier.void');
    });

    // Training & attendance (P7) — admin/secretary/coordinator; attendance cap gates marking.
    Route::middleware('module:training')->group(function () {
        Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
        Route::get('/training/{batch}', [TrainingController::class, 'show'])->name('training.show');
        Route::post('/training/{applicant}/attendance', [TrainingController::class, 'mark'])->name('training.mark');
    });

    // Assessment & certs (P8) — admin/secretary/coordinator; assess cap gates actions.
    Route::middleware('module:assessment')->group(function () {
        Route::get('/assessment', [AssessmentController::class, 'index'])->name('assessment.index');
        Route::put('/assessment/{applicant}/for-assessment', [AssessmentController::class, 'markForAssessment'])->name('assessment.mark');
        Route::post('/assessment/{applicant}/result', [AssessmentController::class, 'record'])->name('assessment.record');
        Route::put('/assessment/{applicant}/assessor', [AssessmentController::class, 'updateAssessor'])->name('assessment.assessor');
        Route::get('/assessment/{applicant}/certificate', [AssessmentController::class, 'certificate'])->name('assessment.certificate');
    });

    // ID system (P9) — admin/secretary/registrar; id.issue cap gates issuing.
    Route::middleware('module:idsystem')->group(function () {
        Route::get('/idsystem', [IdController::class, 'index'])->name('idsystem.index');
        Route::get('/idsystem/{applicant}', [IdController::class, 'card'])->name('idsystem.card');
        Route::put('/idsystem/{applicant}/issue', [IdController::class, 'issue'])->name('idsystem.issue');
    });

    // Reports (P10) — admin/secretary. Payments CSV additionally gated finance.view.
    Route::middleware('module:reports')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/applicants.csv', [ReportController::class, 'applicantsCsv'])->middleware('can:download.approve')->name('reports.applicants');
        Route::get('/reports/payments.csv', [ReportController::class, 'paymentsCsv'])->middleware('can:download.approve')->name('reports.payments');
    });

    // Downloads — admin-approved report/CSV exports (request → approve → download).
    Route::middleware('module:downloads')->group(function () {
        Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
        Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
        Route::get('/downloads/{download}/file', [DownloadController::class, 'file'])->name('downloads.file');
        Route::put('/downloads/{download}/approve', [DownloadController::class, 'approve'])->name('downloads.approve');
        Route::put('/downloads/{download}/reject', [DownloadController::class, 'reject'])->name('downloads.reject');
    });

    // Calendar & events (P11) — all roles view; event.manage gates writes.
    Route::middleware('module:events')->group(function () {
        Route::get('/events', [EventController::class, 'index'])->name('events.index');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    });

    // Activity log (P12) — admin/secretary; read-only audit feed.
    Route::middleware('module:activity')->group(function () {
        Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    });

    // Settings (admin) — configuration hub. Form Builder lives here.
    Route::middleware('module:settings')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/health', [SettingsController::class, 'health'])->name('settings.health');
        Route::get('/settings/security', [SettingsController::class, 'security'])->name('settings.security');
        Route::get('/settings/signatories', [SettingsController::class, 'signatories'])->name('settings.signatories');
        Route::put('/settings/signatories', [SettingsController::class, 'updateSignatories'])->name('settings.signatories.update');
        Route::get('/settings/institution', [SettingsController::class, 'institution'])->name('settings.institution');
        Route::put('/settings/institution', [SettingsController::class, 'updateInstitution'])->name('settings.institution.update');
        Route::get('/settings/requirements', [SettingsController::class, 'requirements'])->name('settings.requirements');
        Route::put('/settings/requirements', [SettingsController::class, 'updateRequirements'])->name('settings.requirements.update');
        Route::get('/settings/education', [SettingsController::class, 'education'])->name('settings.education');
        Route::put('/settings/education', [SettingsController::class, 'updateEducation'])->name('settings.education.update');
        Route::get('/settings/lists', [SettingsController::class, 'lists'])->name('settings.lists');
        Route::put('/settings/lists', [SettingsController::class, 'updateLists'])->name('settings.lists.update');
        Route::get('/settings/academic', [SettingsController::class, 'academic'])->name('settings.academic');
        Route::put('/settings/academic', [SettingsController::class, 'updateAcademic'])->name('settings.academic.update');
        Route::get('/settings/branding', [SettingsController::class, 'branding'])->name('settings.branding');
        Route::post('/settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::get('/settings/access', [SettingsController::class, 'access'])->name('settings.access');
        Route::get('/settings/backups', [BackupController::class, 'index'])->name('settings.backups');
        Route::post('/settings/backups/run', [BackupController::class, 'run'])->name('settings.backups.run');
        Route::put('/settings/backups/schedule', [BackupController::class, 'updateSchedule'])->name('settings.backups.schedule');
        Route::put('/settings/backups/path', [BackupController::class, 'updatePath'])->name('settings.backups.path');
        Route::get('/settings/backups/{name}/download', [BackupController::class, 'download'])->name('settings.backups.download');
        Route::delete('/settings/backups/{name}', [BackupController::class, 'destroy'])->name('settings.backups.destroy');
        Route::get('/settings/form-builder', [FormBuilderController::class, 'index'])->name('formbuilder.index');
        // Categories
        Route::post('/settings/form-builder/sections', [FormBuilderController::class, 'storeSection'])->name('formbuilder.storeSection');
        Route::put('/settings/form-builder/sections-reorder', [FormBuilderController::class, 'reorderSections'])->name('formbuilder.reorderSections');
        Route::put('/settings/form-builder/sections/{section}', [FormBuilderController::class, 'updateSection'])->name('formbuilder.updateSection');
        Route::put('/settings/form-builder/sections/{section}/toggle', [FormBuilderController::class, 'toggleSection'])->name('formbuilder.toggleSection');
        Route::delete('/settings/form-builder/sections/{section}', [FormBuilderController::class, 'destroySection'])->name('formbuilder.destroySection');
        // Built-in fields
        Route::put('/settings/form-builder/builtin/{key}/restore', [FormBuilderController::class, 'restoreBuiltin'])->name('formbuilder.restoreBuiltin');
        Route::put('/settings/form-builder/builtin/{key}', [FormBuilderController::class, 'updateBuiltin'])->name('formbuilder.updateBuiltin');
        Route::delete('/settings/form-builder/builtin/{key}', [FormBuilderController::class, 'destroyBuiltin'])->name('formbuilder.destroyBuiltin');
        // Custom fields
        Route::post('/settings/form-builder/fields', [FormBuilderController::class, 'storeField'])->name('formbuilder.storeField');
        Route::put('/settings/form-builder/fields/{field}', [FormBuilderController::class, 'updateField'])->name('formbuilder.updateField');
        Route::delete('/settings/form-builder/fields/{field}', [FormBuilderController::class, 'destroyField'])->name('formbuilder.destroyField');
        // Layout (drag-and-drop reorder / move across categories)
        Route::put('/settings/form-builder/layout', [FormBuilderController::class, 'reorderLayout'])->name('formbuilder.reorderLayout');
    });

    // Messages (P13) — all roles; staff 1:1 DMs.
    Route::middleware('module:messages')->group(function () {
        Route::get('/messages/attachment/{message}', [MessageController::class, 'attachment'])->name('messages.attachment');
        Route::post('/messages/react/{message}', [MessageController::class, 'react'])->name('messages.react');
        Route::get('/messages/{user?}', [MessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/{user}', [MessageController::class, 'send'])->name('messages.send');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
