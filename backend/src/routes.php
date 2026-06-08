<?php
declare(strict_types=1);

/**
 * ABSENPIB ROUTES
 *
 * All routes defined here and included by index.php
 */

use App\Auth;
use App\Middleware;
use App\Response;
use App\Controllers\AuthController;
use App\Controllers\AttendanceController;
use App\Controllers\OvertimeController;
use App\Controllers\LeaveController;
use App\Controllers\AdminController;
use App\Controllers\CompanyController;
use App\Controllers\OfficeController;
use App\Controllers\ReportController;
use App\Controllers\NotificationController;

// ─── Auth ───────────────────────────────────────
$router->post('/auth/register', fn($p, $b) => (new AuthController)->register($b));
$router->post('/auth/login', fn($p, $b) => (new AuthController)->login($b));
$router->post('/auth/forgot-password', fn($p, $b) => (new AuthController)->forgotPassword($b));

// ─── Attendance ─────────────────────────────────
$router->post('/attendance/check-in', fn($p, $b) => (new AttendanceController)->checkIn($b), 'auth');
$router->post('/attendance/check-out', fn($p, $b) => (new AttendanceController)->checkOut($b), 'auth');
$router->get('/attendance/history', fn($p, $b, $q) => (new AttendanceController)->history($q), 'auth');
$router->get('/attendance/today', fn($p, $b, $q) => (new AttendanceController)->today(), 'auth');
$router->get('/attendance/all', fn($p, $b, $q) => (new AttendanceController)->all($q), 'admin');
$router->get('/attendance/:id', fn($p, $b) => (new AttendanceController)->detail((int) $p['id']), 'auth');

// ─── Overtime ───────────────────────────────────
$router->post('/overtime/start', fn($p, $b) => (new OvertimeController)->start($b), 'auth');
$router->post('/overtime/end', fn($p, $b) => (new OvertimeController)->end($b), 'auth');
$router->get('/overtime/history', fn($p, $b, $q) => (new OvertimeController)->history(), 'auth');
$router->get('/overtime/pending', fn($p, $b, $q) => (new OvertimeController)->pending(), 'admin');
$router->get('/overtime/:id', fn($p, $b) => (new OvertimeController)->detail((int) $p['id']), 'auth');
$router->post('/overtime/:id/approve', fn($p, $b) => (new OvertimeController)->approve((int) $p['id'], $b), 'admin');
$router->post('/overtime/:id/reject', fn($p, $b) => (new OvertimeController)->reject((int) $p['id'], $b), 'admin');

// ─── Leave ──────────────────────────────────────
$router->post('/leave/submit', fn($p, $b) => (new LeaveController)->submit($b), 'auth');
$router->get('/leave/history', fn($p, $b, $q) => (new LeaveController)->history(), 'auth');
$router->get('/leave/pending', fn($p, $b, $q) => (new LeaveController)->pending(), 'admin');
$router->get('/leave/quota', fn($p, $b, $q) => (new LeaveController)->quota(), 'auth');
$router->get('/leave/:id', fn($p, $b) => (new LeaveController)->detail((int) $p['id']), 'auth');
$router->post('/leave/:id/approve', fn($p, $b) => (new LeaveController)->approve((int) $p['id'], $b), 'admin');
$router->post('/leave/:id/reject', fn($p, $b) => (new LeaveController)->reject((int) $p['id'], $b), 'admin');

// ─── Admin ──────────────────────────────────────
$router->get('/admin/dashboard', fn($p, $b, $q) => (new AdminController)->dashboard($q), 'admin');
$router->get('/admin/employees', fn($p, $b, $q) => (new AdminController)->employees($q), 'admin');
$router->post('/admin/employees', fn($p, $b) => (new AdminController)->createEmployee($b), 'admin');
$router->get('/admin/employees/:id', fn($p, $b) => (new AdminController)->employeeDetail((int) $p['id']), 'admin');
$router->put('/admin/employees/:id', fn($p, $b) => (new AdminController)->updateEmployee((int) $p['id'], $b), 'owner');

// ─── Office Config ──────────────────────────────
$router->get('/office/config', fn($p, $b, $q) => (new OfficeController)->get(), 'admin');
$router->post('/office/config', fn($p, $b) => (new OfficeController)->save($b), 'owner');

// ─── Company (superadmin) ───────────────────────
$router->get('/companies', fn($p, $b, $q) => (new CompanyController)->list(), 'superadmin');
$router->post('/companies', fn($p, $b) => (new CompanyController)->create($b), 'superadmin');
$router->put('/companies/:id', fn($p, $b) => (new CompanyController)->update((int) $p['id'], $b), 'superadmin');

// ─── Reports ────────────────────────────────────
$router->get('/reports/export', fn($p, $b, $q) => (new ReportController)->export($q), 'admin');

// ─── Notifications ──────────────────────────────
$router->get('/notifications', fn($p, $b, $q) => (new NotificationController)->list(), 'auth');
$router->post('/notifications/register-fcm', fn($p, $b) => (new NotificationController)->registerFcm($b), 'auth');
$router->post('/notifications/:id/read', fn($p, $b) => (new NotificationController)->markRead((int) $p['id']), 'auth');

// ─── Profile ────────────────────────────────────
$router->get('/profile', fn($p, $b, $q) => (new AuthController)->profile(), 'auth');
$router->put('/profile', fn($p, $b) => (new AuthController)->updateProfile($b), 'auth');

// Static file proxy for uploads (simple passthrough)
$router->get('/uploads/attendance-photos/:file', fn($p, $b, $q) => (new AttendanceController)->servePhoto($p['file']));
$router->get('/uploads/leave-attachments/:file', fn($p, $b, $q) => (new AttendanceController)->serveAttachment($p['file']));
