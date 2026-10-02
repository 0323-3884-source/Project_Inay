<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminStatisticsController;
use App\Http\Controllers\Admin\EducationalContentController;
use App\Http\Controllers\Admin\MaternalVitalThresholdController;
use App\Http\Controllers\Admin\ProgramStaffController;
use App\Http\Controllers\AppNotificationController;
use App\Http\Controllers\AdminStaffMessageController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\StaffCoordinationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');
Route::get('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'requestForm'])->name('password.request');
Route::post('/forgot-password', [\App\Http\Controllers\ForgotPasswordController::class, 'send'])->middleware('throttle:5,1')->name('password.email');
Route::get('/reset-password/{token}', [\App\Http\Controllers\ForgotPasswordController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [\App\Http\Controllers\ForgotPasswordController::class, 'reset'])->middleware('throttle:10,1')->name('password.update');

Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.store');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
Route::middleware('admin.auth')->group(function () {
    Route::get('/admin/dswd-staff', [\App\Http\Controllers\Admin\DswdStaffController::class, 'index'])->name('admin.dswd-staff.index');
    Route::post('/admin/dswd-staff', [\App\Http\Controllers\Admin\DswdStaffController::class, 'store'])->name('admin.dswd-staff.store');
    Route::patch('/admin/dswd-staff/{dswdStaff}', [\App\Http\Controllers\Admin\DswdStaffController::class, 'update'])->name('admin.dswd-staff.update');
    Route::get('/admin', fn () => redirect()->route('admin.statistics'))->name('admin.dashboard');
    Route::get('/admin/statistics', AdminStatisticsController::class)->name('admin.statistics');
    Route::get('/admin/educational-content', [EducationalContentController::class, 'index'])->name('admin.educational-content.index');
    Route::post('/admin/educational-content', [EducationalContentController::class, 'store'])->name('admin.educational-content.store');
    Route::patch('/admin/educational-content/{educationalContent}', [EducationalContentController::class, 'update'])->name('admin.educational-content.update');
    Route::patch('/admin/educational-content/{educationalContent}/publish', [EducationalContentController::class, 'publish'])->name('admin.educational-content.publish');
    Route::patch('/admin/educational-content/{educationalContent}/unpublish', [EducationalContentController::class, 'unpublish'])->name('admin.educational-content.unpublish');
    Route::delete('/admin/educational-content/{educationalContent}', [EducationalContentController::class, 'destroy'])->name('admin.educational-content.destroy');
    Route::get('/admin/maternal-vital-thresholds', [MaternalVitalThresholdController::class, 'index'])->name('admin.maternal-vital-thresholds.index');
    Route::patch('/admin/maternal-vital-thresholds', [MaternalVitalThresholdController::class, 'update'])->name('admin.maternal-vital-thresholds.update');
    Route::get('/admin/staff-messages', [AdminStaffMessageController::class, 'adminIndex'])->name('admin.staff-messages.index');
    Route::get('/admin/program-staff', [ProgramStaffController::class, 'index'])->name('admin.program-staff.index');
    Route::get('/admin/program-staff/{programStaff}', [ProgramStaffController::class, 'show'])->name('admin.program-staff.show');
    Route::patch('/admin/program-staff/{programStaff}', [ProgramStaffController::class, 'update'])->name('admin.program-staff.update');
    Route::delete('/admin/program-staff/{programStaff}', [ProgramStaffController::class, 'destroy'])->name('admin.program-staff.destroy');
    Route::patch('/admin/program-staff/{programStaff}/verify', [ProgramStaffController::class, 'verify'])->name('admin.program-staff.verify');
    Route::patch('/admin/program-staff/{programStaff}/unverify', [ProgramStaffController::class, 'unverify'])->name('admin.program-staff.unverify');
    Route::patch('/admin/program-staff/{programStaff}/approve', [ProgramStaffController::class, 'approve'])->name('admin.program-staff.approve');
    Route::patch('/admin/program-staff/{programStaff}/reject', [ProgramStaffController::class, 'reject'])->name('admin.program-staff.reject');
});

Route::get('/register/mother', [AuthController::class, 'showMotherRegister'])->name('mother.register');
Route::post('/register/mother', [AuthController::class, 'registerMother'])->name('mother.register.store');

Route::get('/register/staff', [AuthController::class, 'showStaffRegister'])->name('staff.register');
Route::post('/register/staff', [AuthController::class, 'registerStaff'])->name('staff.register.store');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [AppNotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [AppNotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('/{notification}/read', [AppNotificationController::class, 'markRead'])->name('read');
});

$disabledMotherScheduling = static function () {
    $message = 'Appointment scheduling has been removed from the mother portal.';

    if (request()->expectsJson()) {
        return response()->json(['message' => $message], 410);
    }

    return redirect()->route('mother.dashboard')->with('status', $message);
};

Route::get('/mother/dashboard', [AuthController::class, 'motherDashboard'])->name('mother.dashboard');
Route::get('/mother/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('mother.profile.show');
Route::patch('/mother/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('mother.profile.update');
Route::get('/staff/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('staff.profile.show');
Route::patch('/staff/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('staff.profile.update');
foreach (['mother', 'staff'] as $role) {
    Route::get("/$role/settings", [\App\Http\Controllers\ProfileController::class, 'settings'])->name("$role.settings");
    Route::patch("/$role/settings/password", [\App\Http\Controllers\ProfileController::class, 'updatePassword'])
        ->middleware('throttle:5,1')->name("$role.settings.password");
}
Route::get('/maternal-monitoring', [AuthController::class, 'maternalMonitoring'])->name('maternal-monitoring');
Route::get('/child-health', [AuthController::class, 'childHealth'])->name('child-health');
Route::post('/child-health/children', [AuthController::class, 'storeMotherChild'])->name('child-health.children.store');
Route::patch('/child-health/children/{infant}', [AuthController::class, 'updateMotherChild'])->name('child-health.children.update');
Route::patch('/child-health/children/{infant}/photo', [AuthController::class, 'updateMotherChildPhoto'])->name('child-health.children.photo.update');
Route::patch('/mother/profile-photo', [AuthController::class, 'updateMotherProfilePhoto'])->name('mother.profile-photo.update');
Route::get('/inay-kaalaman', [AuthController::class, 'inayKaalaman'])->name('inay-kaalaman');
Route::get('/mother/documents', [\App\Http\Controllers\MotherDocumentController::class, 'index'])->name('mother.documents.index');
Route::post('/mother/documents', [AuthController::class, 'uploadInayKaalamanRecord'])->name('mother.documents.store');
Route::get('/mother/documents/{upload}/preview', [\App\Http\Controllers\MotherDocumentController::class, 'file'])->name('mother.documents.preview');
Route::get('/mother/documents/{upload}/download', [\App\Http\Controllers\MotherDocumentController::class, 'file'])->name('mother.documents.download');
Route::delete('/mother/documents/{upload}', [AuthController::class, 'deleteInayKaalamanRecord'])->name('mother.documents.destroy');
Route::get('/health-services', [AuthController::class, 'healthServices'])->name('health-services');
Route::get('/mother/consultation', [ConsultationController::class, 'mother'])->name('mother.consultation');
Route::get('/mother/clinic-schedule', [AppointmentController::class, 'motherIndex'])->name('mother.clinic-schedule.index');
Route::get('/mother/clinic-schedule/calendar', [AppointmentController::class, 'bookingCalendar'])->name('mother.clinic-schedule.calendar');
Route::get('/mother/clinic-schedule/calendar', [AppointmentController::class, 'bookingCalendar'])->name('mother.clinic-schedule.calendar');
Route::get('/mother/clinic-schedule/workers', $disabledMotherScheduling)->name('mother.clinic-schedule.workers');
Route::get('/mother/clinic-schedule/availability', $disabledMotherScheduling)->name('mother.clinic-schedule.availability');
Route::post('/mother/clinic-schedule', [AppointmentController::class, 'bookFromMother'])->name('mother.clinic-schedule.store');
Route::patch('/mother/clinic-schedule/{appointment}/confirm', $disabledMotherScheduling)->name('mother.clinic-schedule.confirm');
Route::patch('/mother/clinic-schedule/{appointment}/decline', $disabledMotherScheduling)->name('mother.clinic-schedule.decline');
Route::patch('/mother/clinic-schedule/{appointment}/cancel', [AppointmentController::class, 'motherCancel'])->name('mother.clinic-schedule.cancel');
Route::patch('/mother/clinic-schedule/{appointment}/reschedule', $disabledMotherScheduling)->name('mother.clinic-schedule.reschedule');
Route::get('/inay-kaalaman/videos/{month}', [AuthController::class, 'inayKaalamanVideos'])->name('inay-kaalaman.videos');
Route::get('/inay-kaalaman/infographics/{month}/pdf', [AuthController::class, 'inayKaalamanInfographicPdf'])->name('inay-kaalaman.infographic.pdf');
Route::post('/inay-kaalaman/progress', [AuthController::class, 'saveInayKaalamanProgress'])->name('inay-kaalaman.progress');
Route::post('/inay-kaalaman/uploads', [AuthController::class, 'uploadInayKaalamanRecord'])->name('inay-kaalaman.upload');
Route::delete('/inay-kaalaman/uploads/{upload}', [AuthController::class, 'deleteInayKaalamanRecord'])->name('inay-kaalaman.upload.delete');
Route::get('/staff/dashboard', [AuthController::class, 'staffDashboard'])->name('staff.dashboard');
Route::get('/staff/mothers', [AuthController::class, 'staffMothers'])->name('staff.mothers');
Route::get('/staff/neonatal-vaccines', [AuthController::class, 'staffNeonatalVaccines'])->name('staff.neonatal');
Route::get('/staff/dynamic-reports', [AuthController::class, 'staffDynamicReports'])->name('staff.dynamic-reports');
Route::get('/staff/consultation', [ConsultationController::class, 'staff'])->name('staff.consultation');
Route::get('/staff/staff-coordination', [StaffCoordinationController::class, 'index'])->name('staff.coordination');
Route::get('/staff/admin-messages', [AdminStaffMessageController::class, 'staffIndex'])->name('staff.admin-messages');
Route::get('/staff/clinic-schedule', [AppointmentController::class, 'staffIndex'])->name('staff.clinic-schedule.index');
Route::patch('/staff/clinic-schedule/profile', [AppointmentController::class, 'updateSchedulingProfile'])->name('staff.clinic-schedule.profile.update');
Route::post('/staff/clinic-schedule/availability', [AppointmentController::class, 'storeAvailability'])->name('staff.clinic-schedule.availability.store');
Route::patch('/staff/clinic-schedule/availability/{availability}', [AppointmentController::class, 'updateAvailability'])->name('staff.clinic-schedule.availability.update');
Route::patch('/staff/clinic-schedule/availability/{availability}/toggle', [AppointmentController::class, 'toggleAvailability'])->name('staff.clinic-schedule.availability.toggle');
Route::delete('/staff/clinic-schedule/availability/{availability}', [AppointmentController::class, 'destroyAvailability'])->name('staff.clinic-schedule.availability.destroy');
Route::post('/staff/clinic-schedule/blocks', [AppointmentController::class, 'storeAvailabilityBlock'])->name('staff.clinic-schedule.blocks.store');
Route::delete('/staff/clinic-schedule/blocks/{block}', [AppointmentController::class, 'destroyAvailabilityBlock'])->name('staff.clinic-schedule.blocks.destroy');
Route::post('/staff/clinic-schedule', [AppointmentController::class, 'store'])->name('staff.clinic-schedule.store');
Route::patch('/staff/clinic-schedule/{appointment}', [AppointmentController::class, 'update'])->name('staff.clinic-schedule.update');
Route::patch('/staff/clinic-schedule/{appointment}/confirm', [AppointmentController::class, 'staffConfirm'])->name('staff.clinic-schedule.confirm');
Route::patch('/staff/clinic-schedule/{appointment}/reject', [AppointmentController::class, 'reject'])->name('staff.clinic-schedule.reject');
Route::patch('/staff/clinic-schedule/{appointment}/suggest-schedule', [AppointmentController::class, 'suggestSchedule'])->name('staff.clinic-schedule.suggest-schedule');
Route::patch('/staff/clinic-schedule/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('staff.clinic-schedule.cancel');
Route::patch('/staff/clinic-schedule/{appointment}/complete', [AppointmentController::class, 'complete'])->name('staff.clinic-schedule.complete');
Route::post('/staff/neonatal-vaccines/infants', [AuthController::class, 'storeStaffInfant'])->name('staff.neonatal.infants.store');
Route::patch('/staff/neonatal-vaccines/infants/{infant}', [AuthController::class, 'updateStaffInfant'])->name('staff.neonatal.infants.update');
Route::patch('/staff/neonatal-vaccines/infants/{infant}/photo', [AuthController::class, 'updateStaffInfantPhoto'])->name('staff.neonatal.infants.photo.update');
Route::post('/staff/neonatal-vaccines/infants/{infant}/growth', [AuthController::class, 'storeStaffInfantGrowth'])->name('staff.neonatal.growth.store');
Route::patch('/staff/neonatal-vaccines/growth/{growth}', [AuthController::class, 'updateStaffInfantGrowth'])->name('staff.neonatal.growth.update');
Route::delete('/staff/neonatal-vaccines/growth/{growth}', [AuthController::class, 'deleteStaffInfantGrowth'])->name('staff.neonatal.growth.delete');
Route::post('/staff/neonatal-vaccines/infants/{infant}/vaccines', [AuthController::class, 'storeStaffInfantVaccine'])->name('staff.neonatal.vaccines.store');
Route::post('/staff/neonatal-vaccines/vaccines/{vaccine}', [AuthController::class, 'updateStaffInfantVaccine'])->name('staff.neonatal.vaccines.update');
Route::patch('/staff/neonatal-vaccines/vaccines/{vaccine}/cancel', [AuthController::class, 'cancelStaffInfantVaccine'])->name('staff.neonatal.vaccines.cancel');
Route::post('/staff/neonatal-vaccines/infants/{infant}/alerts', [AuthController::class, 'storeStaffChildAlert'])->name('staff.neonatal.alerts.store');
Route::patch('/staff/neonatal-vaccines/alerts/{alert}/resolve', [AuthController::class, 'resolveStaffChildAlert'])->name('staff.neonatal.alerts.resolve');
Route::post('/staff/mothers', [AuthController::class, 'storeStaffMothers'])->name('staff.mothers.store');
Route::get('/staff/mothers/{mother}', [AuthController::class, 'staffMotherCasefile'])->name('staff.mothers.show');
Route::patch('/staff/mothers/{mother}', [AuthController::class, 'updateStaffMother'])->name('staff.mothers.update');
Route::get('/staff/neonatal/{infant}/print', [AuthController::class, 'staffChildRecord'])->defaults('format', 'print')->name('staff.neonatal.print');
Route::get('/staff/neonatal/{infant}/pdf', [AuthController::class, 'staffChildRecord'])->defaults('format', 'pdf')->name('staff.neonatal.pdf');
Route::get('/staff/mothers/{mother}/print', [AuthController::class, 'staffMotherRecord'])->defaults('format', 'print')->name('staff.mothers.print');
Route::get('/staff/mothers/{mother}/pdf', [AuthController::class, 'staffMotherRecord'])->defaults('format', 'pdf')->name('staff.mothers.pdf');
Route::get('/staff/mothers/{mother}/inay-kaalaman/uploads/{upload}/download', [AuthController::class, 'downloadStaffInayKaalamanUpload'])->name('staff.mothers.kaalaman-uploads.download');
Route::get('/staff/mothers/{mother}/inay-kaalaman/uploads/{upload}/preview', [AuthController::class, 'previewStaffInayKaalamanUpload'])->name('staff.mothers.kaalaman-uploads.preview');

Route::get('/api/program-staff/mothers/{mother}/maternal-vitals', [AuthController::class, 'getStaffMaternalVitals'])->name('api.staff.maternal-vitals.index');
Route::post('/api/program-staff/mothers/{mother}/maternal-vitals', [AuthController::class, 'storeStaffMaternalVitals'])->name('api.staff.maternal-vitals.store');
Route::put('/api/program-staff/maternal-vitals/{record}', [AuthController::class, 'updateStaffMaternalVital'])->name('api.staff.maternal-vitals.update');
Route::delete('/api/program-staff/maternal-vitals/{record}', [AuthController::class, 'deleteStaffMaternalVital'])->name('api.staff.maternal-vitals.delete');
Route::get('/api/mother/maternal-vitals', [AuthController::class, 'getMotherMaternalVitals'])->name('api.mother.maternal-vitals');

Route::prefix('consultation')->name('consultation.')->group(function () {
    Route::get('/conversations', [ConsultationController::class, 'conversations'])->name('conversations.index');
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('conversations.messages.index');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('conversations.messages.store');
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead'])->name('conversations.read');
    Route::post('/messages/{message}/unsend', [MessageController::class, 'unsend'])->name('messages.unsend');
    Route::get('/messages/{message}/attachment', [MessageController::class, 'attachment'])->name('messages.attachment');
    Route::get('/calls/incoming', [CallController::class, 'incoming'])->name('calls.incoming');
    Route::get('/calls/{call}', [CallController::class, 'show'])->name('calls.show');
    Route::post('/calls/{call}/signal', [CallController::class, 'signal'])->name('calls.signal');
    Route::post('/conversations/{conversation}/calls', [CallController::class, 'store'])->name('conversations.calls.store');
    Route::patch('/calls/{call}', [CallController::class, 'update'])->name('calls.update');
});

Route::prefix('staff-coordination')->name('staff-coordination.')->group(function () {
    Route::get('/threads', [StaffCoordinationController::class, 'threads'])->name('threads.index');
    Route::get('/threads/{thread}/messages', [StaffCoordinationController::class, 'messages'])->name('threads.messages.index');
    Route::post('/threads/{thread}/messages', [StaffCoordinationController::class, 'store'])->name('threads.messages.store');
    Route::post('/threads/{thread}/read', [StaffCoordinationController::class, 'markRead'])->name('threads.read');
    Route::post('/messages/{message}/unsend', [StaffCoordinationController::class, 'unsend'])->name('messages.unsend');
});

Route::prefix('admin-staff-messages')->name('admin-staff-messages.')->group(function () {
    Route::get('/threads', [AdminStaffMessageController::class, 'threads'])->name('threads.index');
    Route::get('/threads/{thread}/messages', [AdminStaffMessageController::class, 'messages'])->name('threads.messages.index');
    Route::post('/threads/{thread}/messages', [AdminStaffMessageController::class, 'store'])->name('threads.messages.store');
    Route::post('/threads/{thread}/read', [AdminStaffMessageController::class, 'markRead'])->name('threads.read');
    Route::post('/messages/{message}/unsend', [AdminStaffMessageController::class, 'unsend'])->name('messages.unsend');
});


Route::prefix('dswd')->name('dswd.')->middleware('dswd.auth')->controller(\App\Http\Controllers\DswdController::class)->group(function () {
    Route::get('/', fn () => redirect()->route('dswd.dashboard'))->name('home');
    Route::get('/dashboard', [\App\Http\Controllers\F1kdController::class, 'dashboard'])->name('dashboard');
    Route::get('/beneficiaries', 'beneficiaries')->name('beneficiaries');
    Route::get('/beneficiaries/{beneficiary}', 'beneficiary')->whereNumber('beneficiary')->name('beneficiaries.show');
    // DSWD must not access uploaded medical documents.
    Route::get('/statistics', 'statistics')->name('statistics');
    Route::get('/reports', 'reports')->name('reports');
    Route::get('/reports/download', 'reports')->name('reports.download');
    Route::get('/evaluation', 'evaluation')->name('evaluation');
    Route::post('/evaluation', 'storeEvaluation')->middleware('throttle:10,1')->name('evaluation.store');
    Route::get('/profile', 'profile')->name('profile');
    Route::patch('/profile', 'updateProfile')->name('profile.update');
});

Route::prefix('dswd/f1kd')->name('dswd.f1kd.')->middleware('dswd.auth')->controller(\App\Http\Controllers\F1kdController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/reports', 'reports')->name('reports');
    Route::get('/reports/download', 'reports')->name('reports.download');
    Route::get('/{subject}', 'show')->where('subject', '(mother|child)-[0-9]+')->name('show');
});
Route::get('/staff/f1kd/{subject}', [\App\Http\Controllers\F1kdController::class, 'edit'])->where('subject', '(mother|child)-[0-9]+')->name('staff.f1kd.edit');
Route::put('/staff/f1kd/{subject}', [\App\Http\Controllers\F1kdController::class, 'update'])->where('subject', '(mother|child)-[0-9]+')->name('staff.f1kd.update');
