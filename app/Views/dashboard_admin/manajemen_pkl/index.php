<?php

/**
 * Views/dashboard_admin/manajemen_pkl/index.php
 * Wrapper utama — routing ke partial berdasarkan $active_tab
 */

$kotaListJson  = json_encode($kotaList ?? [], JSON_UNESCAPED_UNICODE);
$urlBase       = base_url('admin/manajemen-pkl');
$urlPklBase    = base_url('admin/manajemen-pkl/pkl');
$urlStore      = base_url('admin/manajemen-pkl/instansi/store');
$urlUpdateBase = base_url('admin/manajemen-pkl/instansi/update');
$urlDeleteBase = base_url('admin/manajemen-pkl/instansi/delete');

$currentMode   = $mode    ?? 'list';
$currentEditId = $edit_id ?? 0;
$editRow       = $edit_data ?? null;
$isFormMode    = in_array($currentMode, ['tambah', 'edit']);
$activeTab     = $active_tab ?? 'instansi';
?>

<!-- ── Welcome Card ── -->
<div class="welcome-card">
    <h2 class="page-heading">Manajemen PKL</h2>
    <p class="page-subheading">Kelola data instansi dan siswa PKL</p>
</div>

<!-- ══ TAB NAVIGATION ══ -->
<div class="mpkl-tab-nav" <?= $isFormMode ? 'style="display:none"' : '' ?>>
    <button class="mpkl-tab-btn <?= $activeTab === 'instansi' ? 'active' : '' ?>"
        data-tab="instansi" data-sub="Data Instansi">
        <i class="fas fa-building"></i> Data Instansi
    </button>
    <button class="mpkl-tab-btn <?= $activeTab === 'pkl' ? 'active' : '' ?>"
        data-tab="pkl" data-sub="Data PKL">
        <i class="fas fa-user-graduate"></i> Data PKL
    </button>
</div>

<!-- TAB: DATA INSTANSI -->
<div class="mpkl-tab-content <?= $activeTab === 'instansi' ? 'active' : '' ?>" id="tab-instansi">
    <?= view('dashboard_admin/manajemen_pkl/_tab_instansi', [
        'isFormMode'    => $isFormMode,
        'currentMode'   => $currentMode,
        'currentEditId' => $currentEditId,
        'editRow'       => $editRow,
        'kotaList'      => $kotaList ?? [],
        'instansiList'  => $instansiList ?? [],
    ]) ?>
</div>

<!-- TAB: DATA PKL -->
<div class="mpkl-tab-content <?= $activeTab === 'pkl' ? 'active' : '' ?>" id="tab-pkl">
    <?= view('dashboard_admin/manajemen_pkl/_tab_pkl', [
        'sub_tab'      => $sub_tab ?? 'aktif',
        'stat_aktif'   => $stat_aktif ?? 0,
        'stat_selesai' => $stat_selesai ?? 0,
        'stat_nonaktif' => $stat_nonaktif ?? 0,
        'pkl_aktif'    => $pkl_aktif ?? [],
        'pkl_selesai'  => $pkl_selesai ?? [],
        'pkl_nonaktif' => $pkl_nonaktif ?? [],
        'instansiList' => $instansiList_raw ?? [],
    ]) ?>
</div>

<script>
    window.MPKL = {
        kotaList: <?= $kotaListJson ?>,
        urlStore: '<?= $urlStore ?>',
        urlUpdate: '<?= $urlUpdateBase ?>',
        urlDelete: '<?= $urlDeleteBase ?>',
        urlBase: '<?= $urlBase ?>',
        urlPklBase: '<?= $urlPklBase ?>',
        activeTab: '<?= $activeTab ?>',
        initMode: '<?= $currentMode ?>',
        initEditId: <?= $currentEditId ?>,
        csrfName: document.querySelector('meta[name="csrf-token-name"]')?.content ?? '',
        csrfHash: document.querySelector('meta[name="csrf-token-hash"]')?.content ?? '',
    };
</script>