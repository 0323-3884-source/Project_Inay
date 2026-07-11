<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CallController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::get('/register/mother', [AuthController::class, 'showMotherRegister'])->name('mother.register');
Route::post('/register/mother', [AuthController::class, 'registerMother'])->name('mother.register.store');

Route::get('/register/staff', [AuthController::class, 'showStaffRegister'])->name('staff.register');
Route::post('/register/staff', [AuthController::class, 'registerStaff'])->name('staff.register.store');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/mother/dashboard', [AuthController::class, 'motherDashboard'])->name('mother.dashboard');
Route::get('/maternal-monitoring', [AuthController::class, 'maternalMonitoring'])->name('maternal-monitoring');
Route::get('/child-health', [AuthController::class, 'childHealth'])->name('child-health');
Route::post('/child-health/children', [AuthController::class, 'storeMotherChild'])->name('child-health.children.store');
Route::get('/inay-kaalaman', [AuthController::class, 'inayKaalaman'])->name('inay-kaalaman');
Route::get('/health-services', [AuthController::class, 'healthServices'])->name('health-services');
Route::get('/mother/consultation', [ConsultationController::class, 'mother'])->name('mother.consultation');
Route::get('/mother/clinic-schedule', [AppointmentController::class, 'motherIndex'])->name('mother.clinic-schedule.index');
Route::patch('/mother/clinic-schedule/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('mother.clinic-schedule.confirm');
Route::patch('/mother/clinic-schedule/{appointment}/decline', [AppointmentController::class, 'decline'])->name('mother.clinic-schedule.decline');
Route::patch('/mother/clinic-schedule/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('mother.clinic-schedule.reschedule');
Route::get('/inay-kaalaman/videos/{month}', [AuthController::class, 'inayKaalamanVideos'])->name('inay-kaalaman.videos');
Route::get('/inay-kaalaman/infographics/{month}/pdf', [AuthController::class, 'inayKaalamanInfographicPdf'])->name('inay-kaalaman.infographic.pdf');
Route::post('/inay-kaalaman/uploads', [AuthController::class, 'uploadInayKaalamanRecord'])->name('inay-kaalaman.upload');
Route::get('/staff/dashboard', [AuthController::class, 'staffDashboard'])->name('staff.dashboard');
Route::get('/staff/mothers', [AuthController::class, 'staffMothers'])->name('staff.mothers');
Route::get('/staff/neonatal-vaccines', [AuthController::class, 'staffNeonatalVaccines'])->name('staff.neonatal');
Route::get('/staff/consultation', [ConsultationController::class, 'staff'])->name('staff.consultation');
Route::get('/staff/clinic-schedule', [AppointmentController::class, 'staffIndex'])->name('staff.clinic-schedule.index');
Route::post('/staff/clinic-schedule', [AppointmentController::class, 'store'])->name('staff.clinic-schedule.store');
Route::patch('/staff/clinic-schedule/{appointment}', [AppointmentController::class, 'update'])->name('staff.clinic-schedule.update');
Route::patch('/staff/clinic-schedule/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('staff.clinic-schedule.cancel');
Route::patch('/staff/clinic-schedule/{appointment}/complete', [AppointmentController::class, 'complete'])->name('staff.clinic-schedule.complete');
Route::post('/staff/neonatal-vaccines/infants', [AuthController::class, 'storeStaffInfant'])->name('staff.neonatal.infants.store');
Route::post('/staff/neonatal-vaccines/infants/{infant}/growth', [AuthController::class, 'storeStaffInfantGrowth'])->name('staff.neonatal.growth.store');
Route::post('/staff/neonatal-vaccines/vaccines/{vaccine}', [AuthController::class, 'updateStaffInfantVaccine'])->name('staff.neonatal.vaccines.update');
Route::post('/staff/mothers', [AuthController::class, 'storeStaffMothers'])->name('staff.mothers.store');
Route::get('/staff/mothers/{mother}', [AuthController::class, 'staffMotherCasefile'])->name('staff.mothers.show');

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

