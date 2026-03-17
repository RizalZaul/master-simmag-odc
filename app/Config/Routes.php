<?php

/**
 * ============================================================
 * SIMMAG ODC — Routes Configuration
 * app/Config/Routes.php
 *
 * Tambahkan semua baris di bawah ini ke dalam
 * Routes.php yang sudah ada di project kamu.
 *
 * Pastikan AuthFilter sudah terdaftar di app/Config/Filters.php:
 *
 *   public array $aliases = [
 *       'auth' => \App\Filters\AuthFilter::class,
 *       // ... filter lain
 *   ];
 * ============================================================
 */

// ── Default CI4 setup ──────────────────────────────────────────
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('AuthController');
$routes->setDefaultMethod('login');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

// ==============================================================
// AUTH ROUTES
// ==============================================================

// Halaman login (guest only — jika sudah login, redirect ke dashboard)
$routes->get('auth/login',  'AuthController::login',        ['filter' => 'auth:guest']);
$routes->post('auth/login', 'AuthController::processLogin');

// Logout (GET, tidak perlu auth filter — session::destroy() aman)
$routes->get('auth/logout', 'AuthController::logout');

// Root → redirect ke login (atau dashboard jika sudah login)
$routes->get('/', 'DashboardAdminController::index', ['filter' => 'auth:admin']);


// ==============================================================
// ADMIN ROUTES  (filter: auth:admin)
// ==============================================================

$routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {

    // Dashboard
    $routes->get('',          'DashboardAdminController::index');
    $routes->get('dashboard', 'DashboardAdminController::index');

    // ── Placeholder routes (isi controller sesuai kebutuhan) ──

    // Manajemen PKL
    $routes->get('manajemen-pkl',             'ManajemenPklController::index');
    $routes->get('manajemen-pkl/detail/(:num)', 'ManajemenPklController::detail/$1');

    // Data Modul
    $routes->get('data-modul',                    'DataModulController::index');
    $routes->get('data-modul/detail/(:num)',       'DataModulController::detail/$1');

    // Penugasan
    $routes->get('penugasan',                    'PenugasanController::index');
    $routes->get('penugasan/detail/(:num)',       'PenugasanController::detail/$1');
    $routes->get('penugasan/tambah',             'PenugasanController::tambah');
    $routes->post('penugasan/simpan',            'PenugasanController::simpan');

    // Pengumpulan
    $routes->get('pengumpulan',                  'PengumpulanController::index');
    $routes->get('pengumpulan/detail/(:num)',     'PengumpulanController::detail/$1');

    // Profil & Setting
    $routes->get('profil',    'AdminProfilController::index');
    $routes->get('setting',   'AdminSettingController::index');
});


// ==============================================================
// PKL ROUTES  (filter: auth:pkl)
// ==============================================================

$routes->group('pkl', ['filter' => 'auth:pkl'], function ($routes) {

    // Dashboard
    $routes->get('',          'DashboardPklController::index');
    $routes->get('dashboard', 'DashboardPklController::index');

    // ── Placeholder routes (isi controller sesuai kebutuhan) ──

    // Modul
    $routes->get('modul',                        'PklModulController::index');
    $routes->get('modul/kategori/(:num)',         'PklModulController::kategori/$1');

    // Tugas
    $routes->get('tugas',                        'PklTugasController::index');
    $routes->get('tugas/detail/(:num)',          'PklTugasController::detail/$1');
    $routes->post('tugas/kumpulkan/(:num)',       'PklTugasController::kumpulkan/$1');

    // Profil & Setting
    $routes->get('profil',    'PklProfilController::index');
    $routes->get('setting',   'PklSettingController::index');
});
