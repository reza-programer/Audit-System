<?php

use App\Http\Controllers\Admin\ActionPlanController as AdminActionPlanController;
use App\Http\Controllers\Admin\AuditCategoryController as AdminAuditCategoryController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FindingController as AdminFindingController;
use App\Http\Controllers\Admin\NotificationRuleController as AdminNotificationRuleController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SopController as AdminSopController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auditor\AuditController as AuditorAuditController;
use App\Http\Controllers\Auditor\AuditDocumentController as AuditorAuditDocumentController;
use App\Http\Controllers\Auditor\DashboardController as AuditorDashboardController;
use App\Http\Controllers\Auditor\EvidenceVerificationController;
use App\Http\Controllers\Auditor\FindingController as AuditorFindingController;
use App\Http\Controllers\Auditor\QualityFindingController as AuditorQualityFindingController;
use App\Http\Controllers\Coordinator\ActionPlanController as CoordinatorActionPlanController;
use App\Http\Controllers\Coordinator\AuditController as CoordinatorAuditController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\FindingController as CoordinatorFindingController;
use App\Http\Controllers\Coordinator\ReportController as CoordinatorReportController;
use App\Http\Controllers\Coordinator\StoreController as CoordinatorStoreController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Root redirect ---
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->getRedirectRoute());
    }

    return redirect()->route('login');
});

// --- Profile (Breeze default) ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/admin', fn () => redirect()->route('admin.dashboard'));

// ==========================================
// ADMIN & CHIEF ROUTES (VUE + INERTIA)
// ==========================================
Route::middleware(['auth', 'role:admin|chief'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::patch('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::patch('/users/{user}/reject', [AdminUserController::class, 'reject'])->name('users.reject');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Stores & CSA Import
    Route::get('/stores', [AdminStoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/create', [AdminStoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [AdminStoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/download-template', [AdminStoreController::class, 'downloadTemplate'])->name('stores.download-template');
    Route::post('/stores/import-csv', [AdminStoreController::class, 'importCsv'])->name('stores.import-csv');
    Route::get('/stores/{store}/edit', [AdminStoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [AdminStoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [AdminStoreController::class, 'destroy'])->name('stores.destroy');

    // Audit Categories
    Route::get('/audit-categories', [AdminAuditCategoryController::class, 'index'])->name('audit-categories.index');
    Route::post('/audit-categories', [AdminAuditCategoryController::class, 'store'])->name('audit-categories.store');
    Route::put('/audit-categories/{auditCategory}', [AdminAuditCategoryController::class, 'update'])->name('audit-categories.update');
    Route::patch('/audit-categories/{auditCategory}/toggle-active', [AdminAuditCategoryController::class, 'toggleActive'])->name('audit-categories.toggle-active');
    Route::delete('/audit-categories/{auditCategory}', [AdminAuditCategoryController::class, 'destroy'])->name('audit-categories.destroy');
    Route::post('/audit-categories/{id}/restore', [AdminAuditCategoryController::class, 'restore'])->name('audit-categories.restore');

    // SOP / SE
    Route::get('/sops', [AdminSopController::class, 'index'])->name('sops.index');
    Route::post('/sops', [AdminSopController::class, 'store'])->name('sops.store');
    Route::put('/sops/{sop}', [AdminSopController::class, 'update'])->name('sops.update');
    Route::delete('/sops/{sop}', [AdminSopController::class, 'destroy'])->name('sops.destroy');

    // Notification Rules
    Route::get('/notification-rules', [AdminNotificationRuleController::class, 'index'])->name('notification-rules.index');
    Route::post('/notification-rules', [AdminNotificationRuleController::class, 'store'])->name('notification-rules.store');
    Route::put('/notification-rules/{notificationRule}', [AdminNotificationRuleController::class, 'update'])->name('notification-rules.update');
    Route::patch('/notification-rules/{notificationRule}/toggle-active', [AdminNotificationRuleController::class, 'toggleActive'])->name('notification-rules.toggle-active');
    Route::delete('/notification-rules/{notificationRule}', [AdminNotificationRuleController::class, 'destroy'])->name('notification-rules.destroy');

    // Audits
    Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/create', [AdminAuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [AdminAuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{audit}', [AdminAuditController::class, 'show'])->name('audits.show');
    Route::get('/audits/{audit}/edit', [AdminAuditController::class, 'edit'])->name('audits.edit');
    Route::put('/audits/{audit}', [AdminAuditController::class, 'update'])->name('audits.update');
    Route::delete('/audits/{audit}', [AdminAuditController::class, 'destroy'])->name('audits.destroy');
    Route::post('/audits/{audit}/findings', [AdminAuditController::class, 'storeFinding'])->name('audits.findings.store');
    Route::post('/audits/notifications/{notification}/send-now', [AdminAuditController::class, 'sendNotificationNow'])->name('audits.notifications.send-now');

    // Findings
    Route::get('/findings', [AdminFindingController::class, 'index'])->name('findings.index');
    Route::get('/findings/{finding}', [AdminFindingController::class, 'show'])->name('findings.show');
    Route::patch('/findings/{finding}/review-severity', [AdminFindingController::class, 'reviewSeverity'])->name('findings.review-severity');
    Route::patch('/findings/{finding}/recommendation', [AdminFindingController::class, 'updateRecommendation'])->name('findings.recommendation.update');
    Route::patch('/findings/{finding}/close', [AdminFindingController::class, 'close'])->name('findings.close');
    Route::delete('/findings/{finding}', [AdminFindingController::class, 'destroy'])->name('findings.destroy');

    // Action Plans
    Route::get('/action-plans', [AdminActionPlanController::class, 'index'])->name('action-plans.index');
    Route::patch('/action-plans/{actionPlan}', [AdminActionPlanController::class, 'update'])->name('action-plans.update');
    Route::post('/action-plans/{actionPlan}/send-reminder', [AdminActionPlanController::class, 'sendReminder'])->name('action-plans.send-reminder');
    Route::post('/action-plans/broadcast-reminders', [AdminActionPlanController::class, 'broadcastReminders'])->name('action-plans.broadcast-reminders');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-findings', [AdminReportController::class, 'exportFindings'])->name('reports.export-findings');
    Route::get('/reports/export-stores', [AdminReportController::class, 'exportStores'])->name('reports.export-stores');
    Route::get('/reports/export-summary', [AdminReportController::class, 'exportSummary'])->name('reports.export-summary');

    // System Settings & Maintenance (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings/toggle-demo-accounts', [SystemSettingController::class, 'toggleDemoAccounts'])->name('settings.toggle-demo-accounts');
        Route::post('/settings/reset-transactional', [SystemSettingController::class, 'resetTransactional'])->name('settings.reset-transactional');
        Route::post('/settings/factory-reset', [SystemSettingController::class, 'factoryReset'])->name('settings.factory-reset');
    });
});

// ==========================================
// COORDINATOR, ASMEN, CHIEF ROUTES (KOORDINATOR AUDIT)
// ==========================================
Route::middleware(['auth', 'role:coordinator|asmen|chief|admin'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // Stores & CSA Import (Koordinator)
    Route::get('/stores', [CoordinatorStoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/create', [CoordinatorStoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [CoordinatorStoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/download-template', [CoordinatorStoreController::class, 'downloadTemplate'])->name('stores.download-template');
    Route::post('/stores/import-csv', [CoordinatorStoreController::class, 'importCsv'])->name('stores.import-csv');
    Route::get('/stores/{store}/edit', [CoordinatorStoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [CoordinatorStoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [CoordinatorStoreController::class, 'destroy'])->name('stores.destroy');

    // Severity Reviews / Findings
    Route::get('/findings', [CoordinatorFindingController::class, 'index'])->name('findings.index');
    Route::get('/findings/{finding}', [CoordinatorFindingController::class, 'show'])->name('findings.show');
    Route::patch('/findings/{finding}/review-severity', [CoordinatorFindingController::class, 'reviewSeverity'])->name('findings.review-severity');
    Route::patch('/findings/{finding}/recommendation', [CoordinatorFindingController::class, 'updateRecommendation'])->name('findings.recommendation.update');
    Route::patch('/findings/{finding}/close', [CoordinatorFindingController::class, 'close'])->name('findings.close');

    // Finding Quality Monitoring
    Route::get('/finding-qualities', [AuditorQualityFindingController::class, 'index'])->name('finding-qualities.index');
    Route::get('/finding-qualities/{findingQuality}', [AuditorQualityFindingController::class, 'show'])->name('finding-qualities.show');

    // Audits Monitoring & Management
    Route::get('/audits', [CoordinatorAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/create', [CoordinatorAuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [CoordinatorAuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{audit}', [CoordinatorAuditController::class, 'show'])->name('audits.show');
    Route::get('/audits/{audit}/edit', [CoordinatorAuditController::class, 'edit'])->name('audits.edit');
    Route::put('/audits/{audit}', [CoordinatorAuditController::class, 'update'])->name('audits.update');
    Route::delete('/audits/{audit}', [CoordinatorAuditController::class, 'destroy'])->name('audits.destroy');
    Route::post('/audits/notifications/{notification}/send-now', [CoordinatorAuditController::class, 'sendNotificationNow'])->name('audits.notifications.send-now');

    // Action Plans
    Route::get('/action-plans', [CoordinatorActionPlanController::class, 'index'])->name('action-plans.index');
    Route::patch('/action-plans/{actionPlan}', [CoordinatorActionPlanController::class, 'update'])->name('action-plans.update');
    Route::post('/action-plans/{actionPlan}/send-reminder', [CoordinatorActionPlanController::class, 'sendReminder'])->name('action-plans.send-reminder');
    Route::post('/action-plans/broadcast-reminders', [CoordinatorActionPlanController::class, 'broadcastReminders'])->name('action-plans.broadcast-reminders');

    // Reports
    Route::get('/reports', [CoordinatorReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-findings', [CoordinatorReportController::class, 'exportFindings'])->name('reports.export-findings');
    Route::get('/reports/export-stores', [CoordinatorReportController::class, 'exportStores'])->name('reports.export-stores');
    Route::get('/reports/export-summary', [CoordinatorReportController::class, 'exportSummary'])->name('reports.export-summary');
});

// ==========================================
// AUDITOR ROUTES
// ==========================================
Route::middleware(['auth', 'role:auditor'])->prefix('auditor')->name('auditor.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AuditorDashboardController::class, 'index'])->name('dashboard');

    // Audits
    Route::get('/audits', [AuditorAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditorAuditController::class, 'show'])->name('audits.show');

    // Audit Signed Documents (LHP, BAP, Bukti Lapangan)
    Route::post('/audits/{audit}/documents', [AuditorAuditDocumentController::class, 'store'])->name('audits.documents.store');
    Route::delete('/documents/{document}', [AuditorAuditDocumentController::class, 'destroy'])->name('documents.destroy');

    // Findings
    Route::get('/audits/{audit}/findings/create', [AuditorFindingController::class, 'create'])->name('findings.create');
    Route::post('/audits/{audit}/findings', [AuditorFindingController::class, 'store'])->name('findings.store');
    Route::get('/findings/{finding}', [AuditorFindingController::class, 'show'])->name('findings.show');
    Route::get('/findings/{finding}/edit', [AuditorFindingController::class, 'edit'])->name('findings.edit');
    Route::patch('/findings/{finding}', [AuditorFindingController::class, 'update'])->name('findings.update');
    Route::delete('/findings/{finding}', [AuditorFindingController::class, 'destroy'])->name('findings.destroy');
    Route::patch('/findings/{finding}/action-plan', [AuditorFindingController::class, 'updateActionPlan'])->name('findings.action-plan.update');
    Route::post('/findings/{finding}/evidences', [AuditorFindingController::class, 'storeEvidence'])->name('findings.evidences.store');

    // Finding Quality Reports
    Route::get('/finding-qualities', [AuditorQualityFindingController::class, 'index'])->name('finding-qualities.index');
    Route::get('/finding-qualities/create', [AuditorQualityFindingController::class, 'create'])->name('finding-qualities.create');
    Route::post('/finding-qualities', [AuditorQualityFindingController::class, 'store'])->name('finding-qualities.store');
    Route::get('/finding-qualities/{findingQuality}', [AuditorQualityFindingController::class, 'show'])->name('finding-qualities.show');
    Route::delete('/finding-qualities/{findingQuality}', [AuditorQualityFindingController::class, 'destroy'])->name('finding-qualities.destroy');

    // Evidence Verification
    Route::get('/verification', [EvidenceVerificationController::class, 'index'])->name('verification.index');
    Route::patch('/evidences/{evidence}/approve', [EvidenceVerificationController::class, 'approve'])->name('evidences.approve');
    Route::patch('/evidences/{evidence}/reject', [EvidenceVerificationController::class, 'reject'])->name('evidences.reject');
});

require __DIR__ . '/auth.php';
