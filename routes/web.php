<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TeacherCertificateController;
use App\Http\Controllers\AdminTeacherController;
use App\Http\Controllers\SimpleTeacherController;
use App\Http\Controllers\StudentCertificatesController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\InstitutionController;
use App\Http\Controllers\Auth\ActivityLogController;
use App\Http\Controllers\Auth\TrackController;
use App\Http\Controllers\Auth\ApiClientController;
use App\Http\Controllers\TemplateImageController;
use App\Http\Controllers\Auth\StorageController;
use App\Models\Permission;
use App\Models\Role;

// ===== Authentication Routes (Public) =====
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ===== Dashboard (Authenticated) =====
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
});

// ===== User Management Routes =====
Route::middleware(['auth', 'active'])->prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])
        ->middleware('permission:' . Permission::USERS_VIEW)
        ->name('index');
    Route::get('/create', [UserController::class, 'create'])
        ->middleware('permission:' . Permission::USERS_CREATE)
        ->name('create');
    Route::post('/', [UserController::class, 'store'])
        ->middleware('permission:' . Permission::USERS_CREATE)
        ->name('store');
    Route::get('/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:' . Permission::USERS_EDIT)
        ->name('edit');
    Route::put('/{user}', [UserController::class, 'update'])
        ->middleware('permission:' . Permission::USERS_EDIT)
        ->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:' . Permission::USERS_DELETE)
        ->name('destroy');
    Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('permission:' . Permission::USERS_EDIT)
        ->name('toggle-active');
});

// ===== Role Management Routes =====
Route::middleware(['auth', 'active'])->prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])
        ->middleware('permission:' . Permission::ROLES_VIEW)
        ->name('index');
    Route::get('/create', [RoleController::class, 'create'])
        ->middleware('permission:' . Permission::ROLES_CREATE)
        ->name('create');
    Route::post('/', [RoleController::class, 'store'])
        ->middleware('permission:' . Permission::ROLES_CREATE)
        ->name('store');
    Route::get('/{role}/edit', [RoleController::class, 'edit'])
        ->middleware('permission:' . Permission::ROLES_EDIT)
        ->name('edit');
    Route::put('/{role}', [RoleController::class, 'update'])
        ->middleware('permission:' . Permission::ROLES_EDIT)
        ->name('update');
    Route::delete('/{role}', [RoleController::class, 'destroy'])
        ->middleware('permission:' . Permission::ROLES_DELETE)
        ->name('destroy');
});

// ===== Institution Management Routes =====
Route::middleware(['auth', 'active'])->prefix('institutions')->name('institutions.')->group(function () {
    Route::get('/', [InstitutionController::class, 'index'])
        ->middleware('permission:' . Permission::INSTITUTIONS_VIEW)
        ->name('index');
    Route::get('/create', [InstitutionController::class, 'create'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('create');
    Route::post('/', [InstitutionController::class, 'store'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('store');
    Route::get('/{institution}/edit', [InstitutionController::class, 'edit'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('edit');
    Route::put('/{institution}', [InstitutionController::class, 'update'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('update');
    Route::delete('/{institution}', [InstitutionController::class, 'destroy'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('destroy');
    Route::patch('/{institution}/toggle', [InstitutionController::class, 'toggle'])
        ->middleware('permission:' . Permission::INSTITUTIONS_MANAGE)
        ->name('toggle');
    Route::patch('/{institution}/tracks/{track}/toggle', [InstitutionController::class, 'toggleTrack'])
        ->middleware('permission:' . Permission::TRACKS_EDIT)
        ->name('toggle-track');
});

// ===== Activity Logs Routes =====
Route::middleware(['auth', 'active'])->prefix('activity-logs')->name('activity-logs.')->group(function () {
    Route::get('/', [ActivityLogController::class, 'index'])
        ->middleware('permission:' . Permission::ACTIVITY_LOGS_VIEW)
        ->name('index');
    Route::get('/export', [ActivityLogController::class, 'export'])
        ->middleware('permission:' . Permission::ACTIVITY_LOGS_VIEW)
        ->name('export');
    Route::get('/{activityLog}', [ActivityLogController::class, 'show'])
        ->middleware('permission:' . Permission::ACTIVITY_LOGS_VIEW)
        ->name('show');
});

// ===== Track Management Routes =====
Route::middleware(['auth', 'active'])->prefix('tracks')->name('tracks.')->group(function () {
    Route::get('/', [TrackController::class, 'index'])
        ->middleware('permission:' . Permission::TRACKS_VIEW)
        ->name('index');
    Route::get('/export', [TrackController::class, 'export'])
        ->middleware('permission:' . Permission::TRACKS_VIEW)
        ->name('export');
    Route::get('/create', [TrackController::class, 'create'])
        ->middleware('permission:' . Permission::TRACKS_CREATE)
        ->name('create');
    Route::post('/', [TrackController::class, 'store'])
        ->middleware('permission:' . Permission::TRACKS_CREATE)
        ->name('store');
    Route::get('/{track}/edit', [TrackController::class, 'edit'])
        ->middleware('permission:' . Permission::TRACKS_EDIT)
        ->name('edit');
    Route::put('/{track}', [TrackController::class, 'update'])
        ->middleware('permission:' . Permission::TRACKS_EDIT)
        ->name('update');
    Route::patch('/{track}/toggle', [TrackController::class, 'toggle'])
        ->middleware('permission:' . Permission::TRACKS_EDIT)
        ->name('toggle');
    Route::delete('/{track}', [TrackController::class, 'destroy'])
        ->middleware('permission:' . Permission::TRACKS_DELETE)
        ->name('destroy');
});

// ===== API Client Management Routes =====
// Granular permissions: each route accepts its specific permission OR the broad api-clients.manage
Route::middleware(['auth', 'active'])->prefix('api-clients')->name('api-clients.')->group(function () {
    Route::get('/', [ApiClientController::class, 'index'])
        ->middleware('permission:' . Permission::API_CLIENTS_VIEW)
        ->name('index');
    Route::get('/create', [ApiClientController::class, 'create'])
        ->middleware('permission:' . Permission::API_CLIENTS_CREATE . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('create');
    Route::post('/', [ApiClientController::class, 'store'])
        ->middleware('permission:' . Permission::API_CLIENTS_CREATE . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('store');
    Route::get('/{apiClient}', [ApiClientController::class, 'show'])
        ->middleware('permission:' . Permission::API_CLIENTS_VIEW)
        ->name('show');
    Route::get('/{apiClient}/edit', [ApiClientController::class, 'edit'])
        ->middleware('permission:' . Permission::API_CLIENTS_EDIT . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('edit');
    Route::put('/{apiClient}', [ApiClientController::class, 'update'])
        ->middleware('permission:' . Permission::API_CLIENTS_EDIT . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('update');
    Route::delete('/{apiClient}', [ApiClientController::class, 'destroy'])
        ->middleware('permission:' . Permission::API_CLIENTS_DELETE . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('destroy');
    Route::patch('/{apiClient}/toggle', [ApiClientController::class, 'toggle'])
        ->middleware('permission:' . Permission::API_CLIENTS_EDIT . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('toggle');
    Route::post('/{apiClient}/regenerate', [ApiClientController::class, 'regenerateCredentials'])
        ->middleware('permission:' . Permission::API_CLIENTS_CREDENTIALS . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('regenerate');
    Route::get('/{apiClient}/logs', [ApiClientController::class, 'logs'])
        ->middleware('permission:' . Permission::API_CLIENTS_VIEW)
        ->name('logs');

    // Course Mappings
    Route::get('/{apiClient}/mappings', [ApiClientController::class, 'courseMappings'])
        ->middleware('permission:' . Permission::API_CLIENTS_MAPPINGS_VIEW . ',' . Permission::API_CLIENTS_VIEW . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('mappings');
    Route::post('/{apiClient}/mappings', [ApiClientController::class, 'storeCourseMapping'])
        ->middleware('permission:' . Permission::API_CLIENTS_MAPPINGS_CREATE . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('mappings.store');
    Route::put('/{apiClient}/mappings/{mapping}', [ApiClientController::class, 'updateCourseMapping'])
        ->middleware('permission:' . Permission::API_CLIENTS_MAPPINGS_EDIT . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('mappings.update');
    Route::patch('/{apiClient}/mappings/{mapping}/toggle', [ApiClientController::class, 'toggleCourseMapping'])
        ->middleware('permission:' . Permission::API_CLIENTS_MAPPINGS_EDIT . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('mappings.toggle');
    Route::delete('/{apiClient}/mappings/{mapping}', [ApiClientController::class, 'destroyCourseMapping'])
        ->middleware('permission:' . Permission::API_CLIENTS_MAPPINGS_DELETE . ',' . Permission::API_CLIENTS_MANAGE)
        ->name('mappings.destroy');
});

// ===== Home Route =====
Route::get('/', fn() => redirect()->route('dashboard'))->middleware('auth');

// ===== Secure Template Image Serving (Auth Required) =====
// Prevents unauthenticated access to certificate template background images.
// Direct URL access to /images/templates/* is blocked via .htaccess (Apache)
// and redirected here for auth check.
Route::get('/secure/template-image/{path}', [TemplateImageController::class, 'serve'])
    ->where('path', '.*')
    ->middleware(['auth', 'active'])
    ->name('secure.template-image');

// ===== File Manager Routes (Super Admin / Developer Only) =====
// Route prefix is /file-manager (NOT /storage) to avoid conflict with Laravel's
// storage/ directory which the web server may intercept before Laravel routes.
Route::middleware(['auth', 'active', 'role:' . Role::SUPER_ADMIN . ',' . Role::DEVELOPER])
    ->prefix('file-manager')
    ->name('storage.')
    ->group(function () {
        Route::get('/', [StorageController::class, 'index'])->name('index');
        Route::post('/cleanup-temp', [StorageController::class, 'cleanupTemp'])->name('cleanup-temp');
        Route::post('/cleanup-uploads', [StorageController::class, 'cleanupUploads'])->name('cleanup-uploads');
        Route::post('/cleanup-generated', [StorageController::class, 'cleanupGenerated'])->name('cleanup-generated');
        Route::post('/cleanup-all', [StorageController::class, 'cleanupAll'])->name('cleanup-all');
        Route::post('/delete-file', [StorageController::class, 'deleteFile'])->name('delete-file');
    });

// ===== Teacher Admin (Full Editor) - Requires Teacher Admin Permission =====
Route::middleware(['auth', 'active', 'permission:' . Permission::TEACHER_ADMIN_VIEW])
    ->prefix('teacher/admin')
    ->name('teacher.admin.')
    ->group(function () {
        Route::get('/', [AdminTeacherController::class, 'index'])->name('index');
        Route::post('/', [AdminTeacherController::class, 'store'])->name('store');
        Route::post('/preview', [AdminTeacherController::class, 'preview'])->name('preview');
        Route::post('/save-options', [AdminTeacherController::class, 'save'])->name('save');

        // Image generation routes
        Route::post('/preview-image', [AdminTeacherController::class, 'previewImage'])->name('preview.image');
        Route::post('/generate-image', [AdminTeacherController::class, 'storeImage'])->name('store.image');

        // Track management (requires edit permission)
        Route::middleware('permission:' . Permission::TRACKS_CREATE)->group(function () {
            Route::post('/tracks/add', [AdminTeacherController::class, 'addTrack'])->name('tracks.add');
        });
        Route::middleware('permission:' . Permission::TRACKS_EDIT)->group(function () {
            Route::get('/tracks/{id}', [AdminTeacherController::class, 'getTrack'])->name('tracks.get');
            Route::put('/tracks/{id}', [AdminTeacherController::class, 'updateTrack'])->name('tracks.update');
        });
        Route::middleware('permission:' . Permission::TRACKS_DELETE)->group(function () {
            Route::delete('/tracks/{id}', [AdminTeacherController::class, 'deleteTrack'])->name('tracks.delete');
        });
    });

// Serve admin teacher background
Route::get('/teacher/admin/bg/{track}/{gender}', [AdminTeacherController::class, 'bg'])
    ->where('gender', '(male|female)')
    ->name('teacher.admin.bg')
    ->middleware(['auth', 'active', 'permission:' . Permission::TEACHER_ADMIN_VIEW]);

// ===== Teacher Simple (Simplified Interface) - Requires Teacher Simple Permission =====
Route::middleware(['auth', 'active', 'permission:' . Permission::TEACHER_SIMPLE_VIEW])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/', [SimpleTeacherController::class, 'index'])->name('index');
        Route::post('/preview', [SimpleTeacherController::class, 'preview'])->name('preview');
        Route::post('/', [SimpleTeacherController::class, 'store'])->name('store');
        Route::post('/preview-image', [SimpleTeacherController::class, 'previewImage'])->name('preview.image');
        Route::post('/generate-image', [SimpleTeacherController::class, 'storeImage'])->name('store.image');
    });

// Optional template info
Route::get('/template-info', [TemplateController::class, 'info'])->name('template.info');

// Shared signed download (public for signed URLs)
Route::get('/download', [TeacherCertificateController::class, 'download'])
    ->name('download')
    ->middleware('signed');

// ===== Students Admin (Full Editor) - Requires Student Admin Permission =====
Route::middleware(['auth', 'active', 'permission:' . Permission::STUDENT_ADMIN_VIEW])
    ->prefix('students/admin')
    ->name('students.admin.')
    ->group(function () {
        Route::get('/', [StudentCertificatesController::class, 'adminIndex'])->name('index');
        Route::post('/preview', [StudentCertificatesController::class, 'preview'])->name('preview');
        Route::post('/', [StudentCertificatesController::class, 'store'])->name('store');
        Route::post('/clear', [StudentCertificatesController::class, 'clear'])->name('clear');
        Route::post('/save-options', [StudentCertificatesController::class, 'save'])->name('save');

        // Track management
        Route::middleware('permission:' . Permission::TRACKS_CREATE)->group(function () {
            Route::post('/tracks/add', [StudentCertificatesController::class, 'addTrack'])->name('tracks.add');
        });
        Route::middleware('permission:' . Permission::TRACKS_EDIT)->group(function () {
            Route::get('/tracks/{id}', [StudentCertificatesController::class, 'getTrack'])->name('tracks.get');
            Route::put('/tracks/{id}', [StudentCertificatesController::class, 'updateTrack'])->name('tracks.update');
        });
        Route::middleware('permission:' . Permission::TRACKS_DELETE)->group(function () {
            Route::delete('/tracks/{id}', [StudentCertificatesController::class, 'deleteTrack'])->name('tracks.delete');
        });

        // Image generation routes
        Route::post('/preview-image', [StudentCertificatesController::class, 'previewImage'])->name('preview.image');
        Route::post('/generate-images', [StudentCertificatesController::class, 'storeImages'])->name('store.images');
    });

// Serve admin student background
Route::get('/students/admin/bg/{track}/{gender}', [StudentCertificatesController::class, 'bg'])
    ->where('gender', '(male|female)')
    ->name('students.admin.bg')
    ->middleware(['auth', 'active', 'permission:' . Permission::STUDENT_ADMIN_VIEW]);

// Student template download (authenticated)
Route::get('/students/template/{type}', [StudentCertificatesController::class, 'template'])
    ->where('type', '(csv|xlsx)')
    ->name('students.template')
    ->middleware(['auth', 'active']);

// ===== Students Simple (Simplified Interface) - Requires Student Simple Permission =====
Route::middleware(['auth', 'active', 'permission:' . Permission::STUDENT_SIMPLE_VIEW])
    ->prefix('students')
    ->name('students.')
    ->group(function () {
        Route::get('/', [StudentCertificatesController::class, 'index'])->name('index');
        Route::post('/preview', [StudentCertificatesController::class, 'simplePreview'])->name('preview');
        Route::post('/', [StudentCertificatesController::class, 'simpleStore'])->name('store');
        Route::post('/preview-image', [StudentCertificatesController::class, 'simplePreviewImage'])->name('preview.image');
        Route::post('/generate-images', [StudentCertificatesController::class, 'simpleStoreImages'])->name('store.images');
    });

// ===== Students Image-Only Page - Requires Student Admin Permission =====
Route::middleware(['auth', 'active', 'permission:' . Permission::STUDENT_ADMIN_VIEW])->group(function () {
    Route::get('/studentimg', [StudentCertificatesController::class, 'indexImageOnly'])->name('studentimg.index');
    Route::post('/studentimg/preview', [StudentCertificatesController::class, 'previewImageOnly'])->name('studentimg.preview');
    Route::post('/studentimg/generate', [StudentCertificatesController::class, 'storeImagesOnly'])->name('studentimg.store');
    Route::post('/studentimg/save-options', [StudentCertificatesController::class, 'saveImageOptions'])->name('studentimg.save');
    Route::post('/studentimg/clear', [StudentCertificatesController::class, 'clear'])->name('studentimg.clear');
    Route::get('/studentimg/template/{type}', [StudentCertificatesController::class, 'template'])->where('type', '(csv|xlsx)')->name('studentimg.template');
    Route::get('/studentimg/bg/{track}/{gender}', [StudentCertificatesController::class, 'bg'])->where('gender', '(male|female)')->name('studentimg.bg');

    Route::middleware('permission:' . Permission::TRACKS_CREATE)->group(function () {
        Route::post('/studentimg/tracks/add', [StudentCertificatesController::class, 'addTrack'])->name('studentimg.tracks.add');
    });
    Route::middleware('permission:' . Permission::TRACKS_EDIT)->group(function () {
        Route::put('/studentimg/tracks/{id}', [StudentCertificatesController::class, 'updateTrack'])->name('studentimg.tracks.update');
    });
    Route::middleware('permission:' . Permission::TRACKS_DELETE)->group(function () {
        Route::delete('/studentimg/tracks/{id}', [StudentCertificatesController::class, 'deleteTrack'])->name('studentimg.tracks.delete');
    });
});

