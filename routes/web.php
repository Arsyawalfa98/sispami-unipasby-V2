<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DokumenSPMIAMIController;
use App\Http\Controllers\KategoriDokumenController;
use App\Http\Controllers\LembagaAkreditasiController;
use App\Http\Controllers\JenjangController;
use App\Http\Controllers\JudulKriteriaDokumenController;
use App\Http\Controllers\KriteriaDokumenController;
use App\Http\Controllers\ProgramStudiController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\KelolaKebutuhanKriteriaDokumenController;
use App\Http\Controllers\TipeDokumenController;
use App\Http\Controllers\JadwalAmiController;
use App\Http\Controllers\PemenuhanDokumenController;
use App\Http\Controllers\DokumenPersyaratanPemenuhanDokumenController;
use App\Http\Controllers\PenilaianKriteriaController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\ForecastingController;
use App\Http\Controllers\LaporanStatusDokumenController;
use App\Http\Controllers\KelengkapanDokumenController;
use App\Http\Controllers\PTKRHRController;
use App\Http\Controllers\AuditorUploadController;
use App\Http\Controllers\MonevController;

// PRODUCTION: Redirect ke Mitra Portal
// EMERGENCY ACCESS: Uncomment kode di bawah jika perlu akses langsung
// Route::get('/', function () {
//     return redirect()->away('https://mitra.unipasby.ac.id');
// });
Route::get('/', function () {
    return view('welcome');
});
// Route::get('/', function () {
//     abort(404);
// });

// Auth routes without register
Auth::routes(['register' => false]);

// PRODUCTION: Redirect login ke Mitra Portal
// EMERGENCY ACCESS: Uncomment kode di bawah jika perlu akses langsung
// Route::get('/login', function () {
//     return redirect()->away('https://mitra.unipasby.ac.id');
// })->name('login');
// Route::get('/login', function () {
//     abort(404);
// })->name('login');
// USER MANAGEMENT
Route::middleware(['auth', 'user.active'])->group(function () {
    // HOME
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Di web.php, urutkan route sebagai berikut:

    // Users Routes dengan Permission Check
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:view-users')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:create-users')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:create-users')->name('users.store');
    // Di routes/web.php
    Route::get('users/{user}/login-as', [UserController::class, 'loginAs'])->name('users.login-as');
    Route::get('/return-to-admin', [UserController::class, 'returnToAdmin'])->name('return.admin');

    // PENTING: Route integrasi ditempatkan SEBELUM route dengan parameter {user}
    Route::get('/users/insert-integrate', [UserController::class, 'insertIntegrateForm'])->middleware('permission:insert-integrate-users')->name('users.insert-integrate');
    Route::post('/users/insert-integrate', [UserController::class, 'insertIntegrateStore'])->middleware('permission:insert-integrate-users')->name('users.insert-integrate.store');
    Route::get('/username/search', [UserController::class, 'searchUsername'])->middleware('permission:insert-integrate-users')->name('username.search');

    // Route dengan parameter {user} SETELAH route spesifik
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:view-users')->name('users.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('permission:edit-users')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:edit-users')->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete-users')->name('users.destroy');
    Route::post('/users/{user}/sync-prodi', [UserController::class, 'syncProdi'])->name('users.sync-prodi');

    // Roles Routes
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:view-roles')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:create-roles')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create-roles')->name('roles.store');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:view-roles')->name('roles.show');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('permission:edit-roles')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:edit-roles')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:delete-roles')->name('roles.destroy');

    // Permissions Routes
    Route::get('/permissions', [PermissionController::class, 'index'])->middleware('permission:view-permissions')->name('permissions.index');
    Route::get('/permissions/create', [PermissionController::class, 'create'])->middleware('permission:create-permissions')->name('permissions.create');
    Route::post('/permissions', [PermissionController::class, 'store'])->middleware('permission:create-permissions')->name('permissions.store');
    Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->middleware('permission:view-permissions')->name('permissions.show');
    Route::get('/permissions/{permission}/edit', [PermissionController::class, 'edit'])->middleware('permission:edit-permissions')->name('permissions.edit');
    Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('permission:edit-permissions')->name('permissions.update');
    Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('permission:delete-permissions')->name('permissions.destroy');

    // Menu Management Routes
    Route::get('/menus', [MenuController::class, 'index'])->middleware('permission:view-menus')->name('menus.index');
    Route::get('/menus/create', [MenuController::class, 'create'])->middleware('permission:create-menus')->name('menus.create');
    Route::post('/menus', [MenuController::class, 'store'])->middleware('permission:create-menus')->name('menus.store');
    Route::get('/menus/{menu}', [MenuController::class, 'show'])->middleware('permission:view-menus')->name('menus.show');
    Route::get('/menus/{menu}/edit', [MenuController::class, 'edit'])->middleware('permission:edit-menus')->name('menus.edit');
    Route::put('/menus/{menu}', [MenuController::class, 'update'])->middleware('permission:edit-menus')->name('menus.update');
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy'])->middleware('permission:delete-menus')->name('menus.destroy');

    // Menu Activity logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('permission:view-activity-logs')->name('activity-logs.index');

    // Dokumen SPMI & AMI Routes
    Route::get('/dokumen-spmi-ami', [DokumenSPMIAMIController::class, 'index'])->middleware('permission:view-dokumen-spmi-ami')->name('dokumen-spmi-ami.index');
    Route::get('/dokumen-spmi-ami/create', [DokumenSPMIAMIController::class, 'create'])->middleware('permission:create-dokumen-spmi-ami')->name('dokumen-spmi-ami.create');
    Route::post('/dokumen-spmi-ami/upload-temp', [DokumenSPMIAMIController::class, 'uploadTemp'])->middleware(['auth', 'permission:create-dokumen-spmi-ami'])->name('dokumen-spmi-ami.upload-temp');
    Route::post('/dokumen-spmi-ami', [DokumenSPMIAMIController::class, 'store'])->middleware('permission:create-dokumen-spmi-ami')->name('dokumen-spmi-ami.store');
    Route::get('/dokumen-spmi-ami/{dokumenSPMIAMI}', [DokumenSPMIAMIController::class, 'show'])->middleware('permission:view-dokumen-spmi-ami')->name('dokumen-spmi-ami.show');
    Route::get('/dokumen-spmi-ami/{dokumenSPMIAMI}/edit', [DokumenSPMIAMIController::class, 'edit'])->middleware('permission:edit-dokumen-spmi-ami')->name('dokumen-spmi-ami.edit');
    Route::put('/dokumen-spmi-ami/{dokumenSPMIAMI}', [DokumenSPMIAMIController::class, 'update'])->middleware('permission:edit-dokumen-spmi-ami')->name('dokumen-spmi-ami.update');
    Route::delete('/dokumen-spmi-ami/{dokumenSPMIAMI}', [DokumenSPMIAMIController::class, 'destroy'])->middleware('permission:delete-dokumen-spmi-ami')->name('dokumen-spmi-ami.destroy');
    Route::post('/dokumen-spmi-ami/cleanup-temp', [DokumenSPMIAMIController::class, 'cleanupTemp']);

    // Kategori Dokumen
    Route::get('/kategori-dokumen', [KategoriDokumenController::class, 'index'])->middleware('permission:view-kategori-dokumen')->name('kategori-dokumen.index');
    Route::get('/kategori-dokumen/create', [KategoriDokumenController::class, 'create'])->middleware('permission:create-kategori-dokumen')->name('kategori-dokumen.create');
    Route::post('/kategori-dokumen', [KategoriDokumenController::class, 'store'])->middleware('permission:create-kategori-dokumen')->name('kategori-dokumen.store');
    Route::get('/kategori-dokumen/{kategori}', [KategoriDokumenController::class, 'show'])->middleware('permission:view-kategori-dokumen')->name('kategori-dokumen.show');
    Route::get('/kategori-dokumen/{kategori}/edit', [KategoriDokumenController::class, 'edit'])->middleware('permission:edit-kategori-dokumen')->name('kategori-dokumen.edit');
    Route::put('/kategori-dokumen/{kategori}', [KategoriDokumenController::class, 'update'])->middleware('permission:edit-kategori-dokumen')->name('kategori-dokumen.update');
    Route::delete('/kategori-dokumen/{kategori}', [KategoriDokumenController::class, 'destroy'])->middleware('permission:delete-kategori-dokumen')->name('kategori-dokumen.destroy');

    // Master Data Lembaga Akreditasi Routes
    Route::get('/lembaga-akreditasi', [LembagaAkreditasiController::class, 'index'])->middleware('permission:view-lembaga-akreditasi')->name('lembaga-akreditasi.index');
    Route::get('/lembaga-akreditasi/create', [LembagaAkreditasiController::class, 'create'])->middleware('permission:create-lembaga-akreditasi')->name('lembaga-akreditasi.create');
    Route::post('/lembaga-akreditasi', [LembagaAkreditasiController::class, 'store'])->middleware('permission:create-lembaga-akreditasi')->name('lembaga-akreditasi.store');
    Route::get('/lembaga-akreditasi/{lembagaAkreditasi}', [LembagaAkreditasiController::class, 'show'])->middleware('permission:view-lembaga-akreditasi')->name('lembaga-akreditasi.show');
    Route::get('/lembaga-akreditasi/{lembagaAkreditasi}/edit', [LembagaAkreditasiController::class, 'edit'])->middleware('permission:edit-lembaga-akreditasi')->name('lembaga-akreditasi.edit');
    Route::put('/lembaga-akreditasi/{lembagaAkreditasi}', [LembagaAkreditasiController::class, 'update'])->middleware('permission:edit-lembaga-akreditasi')->name('lembaga-akreditasi.update');
    Route::delete('/lembaga-akreditasi/{lembagaAkreditasi}', [LembagaAkreditasiController::class, 'destroy'])->middleware('permission:delete-lembaga-akreditasi')->name('lembaga-akreditasi.destroy');

    // Master Data Jenjang Routes
    Route::get('/jenjang', [JenjangController::class, 'index'])->middleware('permission:view-jenjang')->name('jenjang.index');
    Route::get('/jenjang/create', [JenjangController::class, 'create'])->middleware('permission:create-jenjang')->name('jenjang.create');
    Route::post('/jenjang', [JenjangController::class, 'store'])->middleware('permission:create-jenjang')->name('jenjang.store');
    Route::get('/jenjang/{jenjang}', [JenjangController::class, 'show'])->middleware('permission:view-jenjang')->name('jenjang.show');
    Route::get('/jenjang/{jenjang}/edit', [JenjangController::class, 'edit'])->middleware('permission:edit-jenjang')->name('jenjang.edit');
    Route::put('/jenjang/{jenjang}', [JenjangController::class, 'update'])->middleware('permission:edit-jenjang')->name('jenjang.update');
    Route::delete('/jenjang/{jenjang}', [JenjangController::class, 'destroy'])->middleware('permission:delete-jenjang')->name('jenjang.destroy');

    //Judul Kriteria Dokumen
    Route::get('/judul-kriteria-dokumen', [JudulKriteriaDokumenController::class, 'index'])->middleware('permission:view-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.index');
    Route::get('/judul-kriteria-dokumen/create', [JudulKriteriaDokumenController::class, 'create'])->middleware('permission:create-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.create');
    Route::post('/judul-kriteria-dokumen', [JudulKriteriaDokumenController::class, 'store'])->middleware('permission:create-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.store');
    Route::get('/judul-kriteria-dokumen/{judulKriteriaDokumen}', [JudulKriteriaDokumenController::class, 'show'])->middleware('permission:view-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.show');
    Route::get('/judul-kriteria-dokumen/{judulKriteriaDokumen}/edit', [JudulKriteriaDokumenController::class, 'edit'])->middleware('permission:edit-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.edit');
    Route::put('/judul-kriteria-dokumen/{judulKriteriaDokumen}', [JudulKriteriaDokumenController::class, 'update'])->middleware('permission:edit-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.update');
    Route::delete('/judul-kriteria-dokumen/{judulKriteriaDokumen}', [JudulKriteriaDokumenController::class, 'destroy'])->middleware('permission:delete-judul-kriteria-dokumen')->name('judul-kriteria-dokumen.destroy');

    //Kriteria Dokumen
    // Route untuk CRUD utama
    Route::get('/kriteria-dokumen', [KriteriaDokumenController::class, 'index'])->middleware('permission:view-kriteria-dokumen')->name('kriteria-dokumen.index');
    Route::get('/kriteria-dokumen/create', [KriteriaDokumenController::class, 'create'])->middleware('permission:create-kriteria-dokumen')->name('kriteria-dokumen.create');
    Route::post('/kriteria-dokumen', [KriteriaDokumenController::class, 'store'])->middleware('permission:create-kriteria-dokumen')->name('kriteria-dokumen.store');
    Route::delete('/kriteria-dokumen/{lembagaId}/{jenjangId}/destroy-group', [KriteriaDokumenController::class, 'destroyGroup'])->middleware('permission:delete-kriteria-dokumen')->name('kriteria-dokumen.destroyGroup');

    // Route untuk group dan detail
    Route::get('/kriteria-dokumen/{lembagaId}/{jenjangId}/group', [KriteriaDokumenController::class, 'showGroup'])->middleware('permission:view-kriteria-dokumen')->name('kriteria-dokumen.showGroup');
    Route::get('/kriteria-dokumen/{lembagaId}/{jenjangId}/create-detail', [KriteriaDokumenController::class, 'createDetail'])->middleware('permission:create-kriteria-dokumen')->name('kriteria-dokumen.createDetail');
    Route::post('/kriteria-dokumen/{lembagaId}/{jenjangId}/store-detail', [KriteriaDokumenController::class, 'storeDetail'])->middleware('permission:create-kriteria-dokumen')->name('kriteria-dokumen.storeDetail');

    // Route untuk operasi pada detail
    Route::get('/kriteria-dokumen/{kriteriaDokumen}', [KriteriaDokumenController::class, 'show'])->middleware('permission:view-kriteria-dokumen')->name('kriteria-dokumen.show');
    Route::get('/kriteria-dokumen/{kriteriaDokumen}/edit', [KriteriaDokumenController::class, 'edit'])->middleware('permission:edit-kriteria-dokumen')->name('kriteria-dokumen.edit');
    Route::put('/kriteria-dokumen/{kriteriaDokumen}', [KriteriaDokumenController::class, 'update'])->middleware('permission:edit-kriteria-dokumen')->name('kriteria-dokumen.update');
    Route::delete('/kriteria-dokumen/{kriteriaDokumen}', [KriteriaDokumenController::class, 'destroy'])->middleware('permission:delete-kriteria-dokumen')->name('kriteria-dokumen.destroy');
    Route::post('/kriteria-dokumen/{lembagaId}/{jenjangId}/copy', [KriteriaDokumenController::class, 'copyGroup'])->middleware('permission:create-kriteria-dokumen')->name('kriteria-dokumen.copyGroup');

    // Program studi
    Route::get('/program-studi', [ProgramStudiController::class, 'index'])->middleware('permission:view-program-studi')->name('program-studi.index');
    Route::post('/program-studi', [ProgramStudiController::class, 'store'])->middleware('permission:create-program-studi')->name('program-studi.store');
    Route::put('/program-studi/{programStudi}', [ProgramStudiController::class, 'update'])->middleware('permission:edit-program-studi')->name('program-studi.update');
    Route::delete('/program-studi/{programStudi}', [ProgramStudiController::class, 'destroy'])->middleware('permission:delete-program-studi')->name('program-studi.destroy');
    Route::post('/program-studi/upload-bukti', [ProgramStudiController::class, 'uploadBukti'])->middleware('permission:edit-program-studi')->name('program-studi.upload-bukti');
    Route::post('/dokumen-spmi-ami/upload-temp', [DokumenSPMIAMIController::class, 'uploadTemp'])->name('dokumen-spmi-ami.upload-temp');
    Route::post('/program-studi/cleanup-temp', [ProgramStudiController::class, 'cleanupTemp'])->name('program-studi.cleanup-temp');

    // Kelola kebutuhan
    Route::get('/kelola-kebutuhan-kriteria-dokumen', [KelolaKebutuhanKriteriaDokumenController::class, 'index'])->middleware('permission:view-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.index');
    Route::get('/kelola-kebutuhan-kriteria-dokumen/create', [KelolaKebutuhanKriteriaDokumenController::class, 'create'])->middleware('permission:create-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.create');
    Route::post('/kelola-kebutuhan-kriteria-dokumen', [KelolaKebutuhanKriteriaDokumenController::class, 'store'])->middleware('permission:create-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.store');
    Route::get('/kelola-kebutuhan-kriteria-dokumen/{kelolaKebutuhanKriteriaDokumen}/edit', [KelolaKebutuhanKriteriaDokumenController::class, 'edit'])->middleware('permission:edit-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.edit');
    Route::put('/kelola-kebutuhan-kriteria-dokumen/{kelolaKebutuhanKriteriaDokumen}', [KelolaKebutuhanKriteriaDokumenController::class, 'update'])->middleware('permission:edit-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.update');
    Route::delete('/kelola-kebutuhan-kriteria-dokumen/{kelolaKebutuhanKriteriaDokumen}', [KelolaKebutuhanKriteriaDokumenController::class, 'destroy'])->middleware('permission:delete-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.destroy');
    Route::get('/kelola-kebutuhan-kriteria-dokumen/{kelolaKebutuhanKriteriaDokumen}', [KelolaKebutuhanKriteriaDokumenController::class, 'show'])->middleware('permission:view-kelola-kebutuhan-kriteria-dokumen')->name('kelola-kebutuhan-kriteria-dokumen.show');



    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->middleware('permission:view-profile')->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->middleware('permission:edit-profile')->name('profile.update');

    // About
    Route::get('/about', function () {
        return view('about');
    })->middleware('permission:view-about')->name('about');

    // Error logs
    Route::get('/error-logs', [ErrorLogController::class, 'index'])->middleware('permission:view-errorlog')->name('error-logs.index');
    Route::get('/error-logs/clear', [ErrorLogController::class, 'clear'])->middleware('permission:clear-errorlog')->name('error-logs.clear');

    // Jadwal ami
    Route::get('/jadwal-ami', [JadwalAmiController::class, 'index'])->middleware('permission:view-jadwal-ami')->name('jadwal-ami.index');
    Route::get('/jadwal-ami/create', [JadwalAmiController::class, 'create'])->middleware('permission:create-jadwal-ami')->name('jadwal-ami.create');
    Route::post('/jadwal-ami', [JadwalAmiController::class, 'store'])->middleware('permission:create-jadwal-ami')->name('jadwal-ami.store');
    Route::get('/jadwal-ami/{jadwalAmi}', [JadwalAmiController::class, 'show'])->middleware('permission:view-jadwal-ami')->name('jadwal-ami.show');
    Route::get('/jadwal-ami/{jadwalAmi}/edit', [JadwalAmiController::class, 'edit'])->middleware('permission:edit-jadwal-ami')->name('jadwal-ami.edit');
    Route::put('/jadwal-ami/{jadwalAmi}', [JadwalAmiController::class, 'update'])->middleware('permission:edit-jadwal-ami')->name('jadwal-ami.update');
    Route::delete('/jadwal-ami/{jadwalAmi}', [JadwalAmiController::class, 'destroy'])->middleware('permission:delete-jadwal-ami')->name('jadwal-ami.destroy');
    Route::get('/get-fakultas', [JadwalAmiController::class, 'getFakultas'])->name('get-fakultas');
    Route::patch('/jadwal-ami/{jadwalAmi}/toggle-upload', [JadwalAmiController::class, 'toggleUpload'])->middleware('permission:edit-jadwal-ami')->name('jadwal-ami.toggle-upload');
    Route::get('/jadwal-ami/{jadwalAmi}/upload-stats', [JadwalAmiController::class, 'getUploadStats'])->middleware('permission:view-jadwal-ami')->name('jadwal-ami.upload-stats');

    //Auditor Upload
    Route::get('/auditor-upload', [AuditorUploadController::class, 'index'])->middleware('permission:view-auditor-upload')->name('auditor-upload.index');
    Route::get('/auditor-upload/{jadwalId}/group', [AuditorUploadController::class, 'showGroup'])->middleware('permission:view-auditor-upload')->name('auditor-upload.showGroup');
    Route::get('/auditor-upload/lembaga/{lembagaId}/jenjang/{jenjangId}', [AuditorUploadController::class, 'showGroupByLembaga'])->middleware('permission:view-auditor-upload')->name('auditor-upload.showGroupByLembaga');
    Route::post('/auditor-upload/store', [AuditorUploadController::class, 'store'])->middleware('permission:upload-auditor-files')->name('auditor-upload.store');
    Route::get('/auditor-upload/{id}/download', [AuditorUploadController::class, 'download'])->middleware('permission:view-auditor-upload')->name('auditor-upload.download');
    Route::get('/auditor-upload/{id}/view', [AuditorUploadController::class, 'view'])->middleware('permission:view-auditor-upload')->name('auditor-upload.view');
    Route::delete('/auditor-upload/{id}', [AuditorUploadController::class, 'destroy'])->middleware('permission:upload-auditor-files')->name('auditor-upload.destroy');
    Route::post('/auditor-upload/{jadwalId}/comment', [AuditorUploadController::class, 'storeComment'])->middleware('permission:manage-auditor-upload')->name('auditor-upload.comment.store');
    Route::put('/auditor-upload/{jadwalId}/comment/{commentId}', [AuditorUploadController::class, 'updateComment'])->middleware('permission:manage-auditor-upload')->name('auditor-upload.comment.update');
    Route::delete('/auditor-upload/{jadwalId}/comment/{commentId}', [AuditorUploadController::class, 'destroyComment'])->middleware('permission:manage-auditor-upload')->name('auditor-upload.comment.destroy');
    Route::post('/auditor-upload/{jadwalId}/bulk-download', [AuditorUploadController::class, 'bulkDownload'])->middleware('permission:view-auditor-upload')->name('auditor-upload.bulk-download');
    Route::patch('auditor-upload/{id}/update-keterangan', [AuditorUploadController::class, 'updateKeterangan'])->middleware('permission:upload-auditor-files')->name('auditor-upload.update-keterangan');

    // Tipe Dokumen Routes
    Route::get('/tipe-dokumen', [TipeDokumenController::class, 'index'])->middleware('permission:view-tipe-dokumen')->name('tipe-dokumen.index');
    Route::get('/tipe-dokumen/create', [TipeDokumenController::class, 'create'])->middleware('permission:create-tipe-dokumen')->name('tipe-dokumen.create');
    Route::post('/tipe-dokumen', [TipeDokumenController::class, 'store'])->middleware('permission:create-tipe-dokumen')->name('tipe-dokumen.store');
    Route::get('/tipe-dokumen/{tipeDokumen}', [TipeDokumenController::class, 'show'])->middleware('permission:view-tipe-dokumen')->name('tipe-dokumen.show');
    Route::get('/tipe-dokumen/{tipeDokumen}/edit', [TipeDokumenController::class, 'edit'])->middleware('permission:edit-tipe-dokumen')->name('tipe-dokumen.edit');
    Route::put('/tipe-dokumen/{tipeDokumen}', [TipeDokumenController::class, 'update'])->middleware('permission:edit-tipe-dokumen')->name('tipe-dokumen.update');
    Route::delete('/tipe-dokumen/{tipeDokumen}', [TipeDokumenController::class, 'destroy'])->middleware('permission:delete-tipe-dokumen')->name('tipe-dokumen.destroy');

    // Pemenuhan dokumen
    Route::get('/pemenuhan-dokumen', [PemenuhanDokumenController::class, 'index'])->middleware('permission:view-pemenuhan-dokumen')->name('pemenuhan-dokumen.index');
    Route::get('/pemenuhan-dokumen/{lembagaId}/{jenjangId}/group', [PemenuhanDokumenController::class, 'showGroup'])->middleware('permission:view-pemenuhan-dokumen')->name('pemenuhan-dokumen.showGroup');
    Route::get('/pemenuhan-dokumen/create/{kriteriaDokumenId}', [PemenuhanDokumenController::class, 'create'])->middleware('permission:create-pemenuhan-dokumen')->name('pemenuhan-dokumen.create');
    Route::post('/pemenuhan-dokumen', [PemenuhanDokumenController::class, 'store'])->middleware('permission:create-pemenuhan-dokumen')->name('pemenuhan-dokumen.store');
    Route::post('/pemenuhan-dokumen/{lembagaId}/{jenjangId}/ajukan', [PemenuhanDokumenController::class, 'ajukan'])->name('pemenuhan-dokumen.ajukan');
    Route::post('/pemenuhan-dokumen/{lembagaId}/{jenjangId}/update-status', [PemenuhanDokumenController::class, 'updateStatus'])->name('pemenuhan-dokumen.updateStatus');
    Route::get('/pemenuhan-dokumen/{lembagaId}/{jenjangId}/detail', [PemenuhanDokumenController::class, 'showDetail'])->name('pemenuhan-dokumen.detail');

    // Pemenuhan dokumen persyaratan - dengan prefix yang jelas
    Route::prefix('dokumen-persyaratan-pemenuhan-dokumen')->group(function () {
        Route::get('/{kriteriaDokumenId}', [DokumenPersyaratanPemenuhanDokumenController::class, 'index'])->middleware('permission:view-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.index');
        Route::get('/{kriteriaDokumenId}/create', [DokumenPersyaratanPemenuhanDokumenController::class, 'create'])->middleware('permission:create-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.create');
        Route::post('/{kriteriaDokumenId}/store', [DokumenPersyaratanPemenuhanDokumenController::class, 'store'])->middleware('permission:create-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.store');
        Route::get('/{id}/show', [DokumenPersyaratanPemenuhanDokumenController::class, 'show'])->middleware('permission:view-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.show');
        Route::get('/{id}/edit', [DokumenPersyaratanPemenuhanDokumenController::class, 'edit'])->middleware('permission:edit-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.edit');
        Route::delete('/{id}', [DokumenPersyaratanPemenuhanDokumenController::class, 'destroy'])->middleware('permission:delete-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.destroy');
        Route::put('/{id}/update', [DokumenPersyaratanPemenuhanDokumenController::class, 'update'])->middleware('permission:edit-pemenuhan-dokumen')->name('dokumen-persyaratan-pemenuhan-dokumen.update');
        Route::post('/upload-file', [DokumenPersyaratanPemenuhanDokumenController::class, 'uploadFile'])->name('pemenuhan-dokumen.upload-file');
        Route::post('/cleanup-temp', [DokumenPersyaratanPemenuhanDokumenController::class, 'cleanupTemp'])->name('pemenuhan-dokumen.cleanup-temp');
    });

    // Penilaian Kriteria - dengan prefix yang jelas
    Route::prefix('penilaian-kriteria')->group(function () {
        Route::get('/{kriteriaDokumenId}/id', [PenilaianKriteriaController::class, 'index'])->middleware('permission:view-penilaian-kriteria')->name('penilaian-kriteria.index');
        Route::post('/{kriteriaDokumenId}/store', [PenilaianKriteriaController::class, 'store'])->middleware('permission:create-penilaian-kriteria')->name('penilaian-kriteria.store');
        Route::put('/{id}/update', [PenilaianKriteriaController::class, 'update'])->middleware('permission:edit-penilaian-kriteria')->name('penilaian-kriteria.update');
    });

    // Forecasting
    Route::get('/forecasting', [ForecastingController::class, 'index'])->middleware('permission:view-forecasting')->name('forecasting.index');
    Route::get('/forecasting/{lembagaId}/{jenjangId}/group', [ForecastingController::class, 'showGroup'])->middleware('permission:view-forecasting')->name('forecasting.showGroup');
    Route::get('/forecasting/{lembagaId}/{jenjangId}/export-pdf', [ForecastingController::class, 'exportPdf'])->middleware('permission:view-forecasting')->name('forecasting.exportPdf');
    Route::get('/forecasting/{lembagaId}/{jenjangId}/visualize', [ForecastingController::class, 'visualize'])->middleware('permission:view-forecasting')->name('forecasting.visualize');
    Route::get('/prodi/search', [UserController::class, 'searchProdi'])->name('prodi.search');
    Route::get('/search-prodi', [HomeController::class, 'searchProdi'])->name('search.prodi');

    // Laporan Status Dokumen
    Route::get('/laporan-status-dokumen', [LaporanStatusDokumenController::class, 'index'])->middleware('permission:view-laporan-status-dokumen')->name('laporan-status-dokumen.index');
    Route::get('/laporan-status-dokumen/export-pdf', [LaporanStatusDokumenController::class, 'exportPdf'])->middleware('permission:view-laporan-status-dokumen')->name('laporan-status-dokumen.exportPdf');
    Route::get('/search-lembaga', [LaporanStatusDokumenController::class, 'searchLembaga'])->name('search.lembaga');
    Route::get('/search-jenjang', [LaporanStatusDokumenController::class, 'searchJenjang'])->name('search.jenjang');
    Route::get('/search-tahun', [LaporanStatusDokumenController::class, 'searchTahun'])->name('search.tahun');
    Route::get('/search-prodi-status', [LaporanStatusDokumenController::class, 'searchProdi'])->name('search.prodi.status');

    // Kelengkapan Dokumen (Checklist)
    Route::get('/kelengkapan-dokumen', [KelengkapanDokumenController::class, 'index'])->middleware('permission:view-kelengkapan-dokumen')->name('kelengkapan-dokumen.index');
    Route::get('/kelengkapan-dokumen/{lembagaId}/{jenjangId}/group', [KelengkapanDokumenController::class, 'showGroup'])->middleware('permission:view-kelengkapan-dokumen')->name('kelengkapan-dokumen.showGroup');
    Route::get('/kelengkapan-dokumen/{lembagaId}/{jenjangId}/export-excel', [KelengkapanDokumenController::class, 'exportExcel'])->middleware('permission:view-kelengkapan-dokumen')->name('kelengkapan-dokumen.exportExcel');
    Route::get('kelengkapan-dokumen/exportPdf/{lembagaId}/{jenjangId}', [KelengkapanDokumenController::class, 'exportPdf'])->name('kelengkapan-dokumen.exportPdf');

    // PTKRHR Routes (Updated)
    Route::get('/ptkrhr', [PTKRHRController::class, 'index'])->middleware('permission:view-ptkrhr')->name('ptkrhr.index');
    Route::get('/ptkrhr/{lembagaId}/{jenjangId}/group', [PTKRHRController::class, 'showGroup'])->middleware('permission:view-ptkrhr')->name('ptkrhr.showGroup');
    Route::get('/ptkrhr/{lembagaId}/{jenjangId}/pdf/{dokumenId}/{prodi}', [PTKRHRController::class, 'generatePdf'])->middleware('permission:view-ptkrhr')->name('ptkrhr.generatePdf');
    Route::get('/ptkrhr/{lembagaId}/{jenjangId}/rekapitulasi-pdf/{dokumenId}/{prodi}', [PTKRHRController::class, 'generateRekapitulasiPdf'])->middleware('permission:view-ptkrhr')->name('ptkrhr.generateRekapitulasiPdf');
    Route::get('/ptkrhr/{lembagaId}/{jenjangId}/excel/{dokumenId}/{prodi}', [PTKRHRController::class, 'generateExcel'])->middleware('permission:view-ptkrhr')->name('ptkrhr.generateExcel');

    // Monev KTS (Ketidaksesuaian)
    Route::prefix('monev-kts')->group(function () {
        Route::get('/', [MonevController::class, 'index'])->middleware('permission:view-monev')->defaults('status_temuan', 'KETIDAKSESUAIAN')->name('monev-kts.index');
        Route::get('/{lembagaId}/{jenjangId}/group', [MonevController::class, 'showGroup'])->middleware('permission:view-monev')->defaults('status_temuan', 'KETIDAKSESUAIAN')->name('monev-kts.showGroup');
        // Komentar Element
        Route::patch('/komentar-element', [MonevController::class, 'storeKomentarElement'])->middleware('permission:edit-monev')->name('monev-kts.storeKomentarElement');
        // Komentar Global
        Route::get('/{lembagaId}/{jenjangId}/global-komentar', [MonevController::class, 'showGlobalKomentar'])->middleware('permission:view-monev')->defaults('status_temuan', 'KETIDAKSESUAIAN')->name('monev-kts.showGlobalKomentar');
        Route::post('/{lembagaId}/{jenjangId}/global-komentar', [MonevController::class, 'storeGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-kts.storeGlobalKomentar');
        Route::put('/{lembagaId}/{jenjangId}/global-komentar/{komentarId}', [MonevController::class, 'updateGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-kts.updateGlobalKomentar');
        Route::delete('/{lembagaId}/{jenjangId}/global-komentar/{komentarId}', [MonevController::class, 'destroyGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-kts.destroyGlobalKomentar');
    });

    // NEW: Monev TERCAPAI (Reuse Controller)
    Route::prefix('monev-tercapai')->group(function () {
        Route::get('/', [MonevController::class, 'index'])->middleware('permission:view-monev')->defaults('status_temuan', 'TERCAPAI')->name('monev-tercapai.index');
        Route::get('/{lembagaId}/{jenjangId}/group', [MonevController::class, 'showGroup'])->middleware('permission:view-monev')->defaults('status_temuan', 'TERCAPAI')->name('monev-tercapai.showGroup');
        // Komentar Element untuk Tercapai
        Route::patch('/komentar-element', [MonevController::class, 'storeKomentarElement'])->middleware('permission:edit-monev')->name('monev-tercapai.storeKomentarElement');
        // Komentar Global untuk Tercapai
        Route::get('/{lembagaId}/{jenjangId}/global-komentar', [MonevController::class, 'showGlobalKomentar'])->middleware('permission:view-monev')->defaults('status_temuan', 'TERCAPAI')->name('monev-tercapai.showGlobalKomentar');
        Route::post('/{lembagaId}/{jenjangId}/global-komentar', [MonevController::class, 'storeGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-tercapai.storeGlobalKomentar');
        Route::put('/{lembagaId}/{jenjangId}/global-komentar/{komentarId}', [MonevController::class, 'updateGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-tercapai.updateGlobalKomentar');
        Route::delete('/{lembagaId}/{jenjangId}/global-komentar/{komentarId}', [MonevController::class, 'destroyGlobalKomentar'])->middleware('permission:edit-monev')->name('monev-tercapai.destroyGlobalKomentar');
    });

    // Route untuk switch role dan prodi
    Route::get('/switch-role/{role}', [UserRoleController::class, 'switchRole'])->name('switch.role');
    Route::post('/switch-prodi', [UserRoleController::class, 'switchProdi'])->name('switch.prodi');

    Route::get('pemenuhan-dokumen/{lembagaId}/{jenjangId}/export-pdf', [PemenuhanDokumenController::class, 'exportPdf'])->name('pemenuhan-dokumen.exportPdf');
});


//LOGIN BUTTON DARI MITRA ROUTENYA
Route::get('/getLoginMitra/{username}', [LoginController::class, 'loginMitra'])->name('login.mitra')->middleware('guest'); // Tambahkan middleware guest agar bisa diakses tanpa login
