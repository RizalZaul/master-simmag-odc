<?php

/**
 * ============================================================
 * SIMMAG ODC — Routes Configuration
 * app/Config/Routes.php
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

$routes->get('auth/login',  'AuthController::login',        ['filter' => 'auth:guest']);
$routes->post('auth/login', 'AuthController::processLogin');
$routes->get('auth/lupa-password', 'AuthController::forgotPassword', ['filter' => 'auth:guest']);
$routes->post('auth/lupa-password/send-otp', 'AuthController::sendForgotPasswordOtp', ['filter' => 'auth:guest']);
$routes->post('auth/lupa-password/verify-otp', 'AuthController::verifyForgotPasswordOtp', ['filter' => 'auth:guest']);
$routes->post('auth/lupa-password/reset', 'AuthController::resetForgotPassword', ['filter' => 'auth:guest']);
$routes->get('auth/logout', 'AuthController::logout');
$routes->get('/', 'DashboardAdminController::index', ['filter' => 'auth:admin']);


// ==============================================================
// ADMIN ROUTES  (filter: auth:admin)
// ==============================================================

$routes->group('admin', ['filter' => 'auth:admin'], function ($routes) {

    // Dashboard
    $routes->get('',                                            'DashboardAdminController::index');
    $routes->get('dashboard',                                   'DashboardAdminController::index');

    // Manajemen PKL — Data Instansi
    $routes->get('manajemen-pkl',                               'InstansiAdminController::index');
    $routes->post('manajemen-pkl/instansi/store',               'InstansiAdminController::storeInstansi');
    $routes->post('manajemen-pkl/instansi/update/(:num)',       'InstansiAdminController::updateInstansi/$1');
    $routes->post('manajemen-pkl/instansi/delete/(:num)',       'InstansiAdminController::deleteInstansi/$1');
    $routes->get('manajemen-pkl/instansi/kota-list',            'InstansiAdminController::getKotaList');

    // Manajemen PKL — Data PKL
    $routes->get('manajemen-pkl/pkl',                           'MPklAdminController::index');
    $routes->get('manajemen-pkl/pkl/tambah',                    'MPklAdminController::tambah');
    $routes->post('manajemen-pkl/pkl/store',                    'MPklAdminController::store');
    $routes->get('manajemen-pkl/pkl/detail/(:num)',             'MPklAdminController::detail/$1');
    $routes->get('manajemen-pkl/pkl/edit/(:num)',               'MPklAdminController::edit/$1');
    $routes->post('manajemen-pkl/pkl/update/(:num)',            'MPklAdminController::update/$1');
    $routes->post('manajemen-pkl/pkl/delete/(:num)',            'MPklAdminController::delete/$1');
    $routes->post('manajemen-pkl/pkl/toggle-status/(:num)',     'MPklAdminController::toggleStatus/$1');
    $routes->post('manajemen-pkl/pkl/check-email',              'MPklAdminController::checkEmail');

    // Data Modul
    $routes->get('data-modul',                                  'ModulAdminController::index');
    $routes->get('data-modul/detail/(:num)',                    'ModulAdminController::detail/$1');
    $routes->get('data-modul/file/view/(:num)',                 'ModulAdminController::previewFile/$1');
    $routes->get('data-modul/file/download/(:num)',             'ModulAdminController::downloadFile/$1');
    $routes->post('data-modul/kategori/store',                  'ModulAdminController::storeKategori');
    $routes->post('data-modul/kategori/update/(:num)',          'ModulAdminController::updateKategori/$1');
    $routes->post('data-modul/kategori/delete/(:num)',          'ModulAdminController::deleteKategori/$1');
    $routes->post('data-modul/modul/store',                     'ModulAdminController::storeModul');
    $routes->post('data-modul/modul/update/(:num)',             'ModulAdminController::updateModul/$1');
    $routes->post('data-modul/modul/delete/(:num)',             'ModulAdminController::deleteModul/$1');

    // ==============================================================
    // MANAJEMEN TUGAS - PENUGASAN & PENGUMPULAN
    // ==============================================================
    $routes->group('manajemen-tugas', function ($routes) {

        // Halaman Utama & Tabs
        $routes->get('penugasan', 'MTugasAdminController::index');
        $routes->get('penugasan/load-tab/(:segment)', 'MTugasAdminController::loadTab/$1');
        $routes->get('pengumpulan', 'MTugasAdminController::pengumpulan');
        $routes->get('pengumpulan/detail/(:segment)/(:num)', 'MTugasAdminController::detailPengumpulan/$1/$2');
        $routes->get('pengumpulan/detail/(:segment)/(:num)/(:num)', 'MTugasAdminController::detailPengumpulan/$1/$2/$3');
        $routes->post('pengumpulan/item/(:num)/review', 'MTugasAdminController::reviewPengumpulanItem/$1');
        $routes->get('pengumpulan/item/(:num)/view', 'MTugasAdminController::previewPengumpulanItem/$1');
        $routes->get('pengumpulan/item/(:num)/download', 'MTugasAdminController::downloadPengumpulanItem/$1');

        // Kategori Tugas (AJAX CRUD)
        $routes->get('kategori/list', 'MTugasAdminController::getKategoriList');
        $routes->post('kategori/store', 'MTugasAdminController::storeKategori');
        $routes->post('kategori/update/(:num)', 'MTugasAdminController::updateKategori/$1');
        $routes->post('kategori/delete/(:num)', 'MTugasAdminController::deleteKategori/$1');

        // Tugas (Halaman & AJAX CRUD)
        $routes->get('tugas/list', 'MTugasAdminController::getTugasList');
        $routes->get('tugas/tambah', 'MTugasAdminController::tambahTugas');
        $routes->get('tugas/pilih-sasaran', 'MTugasAdminController::sasaranTugas');
        $routes->post('tugas/store', 'MTugasAdminController::storeTugas');
        $routes->get('tugas/detail/(:num)', 'MTugasAdminController::detailTugas/$1');
        $routes->get('tugas/ubah/(:num)', 'MTugasAdminController::ubahTugas/$1');
        $routes->post('tugas/update/(:num)', 'MTugasAdminController::updateTugas/$1');
        $routes->post('tugas/delete/(:num)', 'MTugasAdminController::deleteTugas/$1');

        // API Sasaran Tugas (AJAX)
        $routes->get('api/pkl-aktif', 'MTugasAdminController::getPklAktif');
        $routes->get('api/kelompok-aktif', 'MTugasAdminController::getKelompokAktif');
        $routes->get('api/tim-tugas', 'MTugasAdminController::getTimTugas');
        // $routes->post('api/tim-tugas/store', 'MTugasAdminController::storeTimTugas');

        $routes->get('api/pkl-aktif-with-kategori',       'MTugasAdminController::getPklAktifWithKategori');  // ← BARU
        $routes->post('api/tim-tugas/store',              'MTugasAdminController::storeTimTugas');            // ← BARU
    });

    // Alias lama Pengumpulan agar tautan lama tetap berjalan
    $routes->get('pengumpulan', 'MTugasAdminController::pengumpulan');
    $routes->get('pengumpulan/detail/(:segment)/(:num)', 'MTugasAdminController::detailPengumpulan/$1/$2');
    $routes->get('pengumpulan/detail/(:segment)/(:num)/(:num)', 'MTugasAdminController::detailPengumpulan/$1/$2/$3');

    // Profil Admin
    $routes->get('profil',                                      'ProfilAdminController::index');
    $routes->post('profil/biodata',                             'ProfilAdminController::updateBiodata');
    $routes->post('profil/password',                            'ProfilAdminController::updatePassword');
    $routes->post('profil/toggle-biodata-pkl',                  'ProfilAdminController::toggleBiodataPkl');
    $routes->post('profil/generate-token',                      'ProfilAdminController::generateToken');

    // Setting (placeholder)
    $routes->get('setting',                                     'AdminSettingController::index');
});


// ==============================================================
// PKL ROUTES  (filter: auth:pkl)
// ==============================================================

$routes->group('pkl', ['filter' => 'auth:pkl'], function ($routes) {

    // Dashboard
    $routes->get('',                                            'DashboardPklController::index');
    $routes->get('dashboard',                                   'DashboardPklController::index');

    // Modul
    $routes->get('modul', 'ModulPklController::index');
    $routes->get('modul/kategori/(:num)', 'ModulPklController::kategori/$1');
    $routes->get('modul/file/view/(:num)', 'ModulPklController::previewFile/$1');
    $routes->get('modul/file/download/(:num)', 'ModulPklController::downloadFile/$1');

    // Tugas
    $routes->get('tugas',                                       'MTugasPklController::index');
    $routes->get('tugas/detail/(:num)',                         'MTugasPklController::detail/$1');
    $routes->post('tugas/kumpulkan/(:num)',                     'MTugasPklController::kumpulkan/$1');
    $routes->get('tugas/item/(:num)/download',                  'MTugasPklController::downloadItem/$1');

    // Profil PKL
    $routes->get('profil',                                      'ProfilPklController::index');
    $routes->post('profil/biodata',                             'ProfilPklController::updateBiodata');
    $routes->post('profil/password',                            'ProfilPklController::updatePassword');

    // Setting PKL (placeholder)
    $routes->get('setting',                                     'PklSettingController::index');
});

// ==============================================================
// BIODATA ROUTES
// ==============================================================

$routes->get('biodata-pkl/sukses',            'BiodataPklController::sukses');
$routes->post('biodata-pkl/check-email',        'BiodataPklController::checkEmail');
$routes->post('biodata-pkl/send-otp',           'BiodataPklController::sendOtp');
$routes->post('biodata-pkl/verify-otp',         'BiodataPklController::verifyOtp');
$routes->post('biodata-pkl/store',              'BiodataPklController::store');
// PENTING: route (:segment) harus PALING AKHIR agar tidak menimpa route di atas
$routes->get('biodata-pkl/(:segment)',         'BiodataPklController::index/$1');
