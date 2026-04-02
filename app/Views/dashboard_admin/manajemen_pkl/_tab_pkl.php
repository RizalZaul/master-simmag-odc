<?php

/**
 * Views/dashboard_admin/manajemen_pkl/_tab_pkl.php
 */

function tglShort(?string $d): string
{
    if (!$d) return '-';
    $bln = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $p   = explode('-', $d);
    return sprintf('%02d %s %s', (int)$p[2], $bln[(int)$p[1]], $p[0]);
}

function kategoriLabel(string $k): string
{
    return match ($k) {
        'kampus'  => 'Kuliah',
        'sekolah' => 'SMK',
        default   => 'Mandiri',
    };
}

$subTab       = $sub_tab ?? 'aktif';
$urlPklBase   = base_url('admin/manajemen-pkl/pkl');
$instansiOpts = '';
foreach (($instansiList ?? []) as $ins) {
    $instansiOpts .= '<option value="' . esc($ins['nama_instansi']) . '">' . esc($ins['nama_instansi']) . '</option>';
}
?>

<!-- ── Stat Cards ── -->
<div class="pkl-stat-cards">
    <div class="pkl-stat-card aktif <?= $subTab === 'aktif' ? 'selected' : '' ?>"
        onclick="switchSubTab('aktif', this)">
        <div class="stat-icon"><i class="fas fa-user-check"></i></div>
        <div class="stat-info">
            <span class="stat-label">PKL Aktif</span>
            <span class="stat-count"><?= $stat_aktif ?></span>
        </div>
    </div>
    <div class="pkl-stat-card selesai <?= $subTab === 'selesai' ? 'selected' : '' ?>"
        onclick="switchSubTab('selesai', this)">
        <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <span class="stat-label">PKL Selesai</span>
            <span class="stat-count"><?= $stat_selesai ?></span>
        </div>
    </div>
    <div class="pkl-stat-card nonaktif <?= $subTab === 'nonaktif' ? 'selected' : '' ?>"
        onclick="switchSubTab('nonaktif', this)">
        <div class="stat-icon"><i class="fas fa-user-slash"></i></div>
        <div class="stat-info">
            <span class="stat-label">PKL Non-Aktif</span>
            <span class="stat-count"><?= $stat_nonaktif ?></span>
        </div>
    </div>
</div>

<!-- ── Sub-Tab Nav ── -->
<div class="pkl-subtab-nav">
    <button class="pkl-subtab-btn <?= $subTab === 'aktif' ? 'active' : '' ?>"
        onclick="switchSubTab('aktif', null)">
        <i class="fas fa-user-check"></i> PKL Aktif
    </button>
    <button class="pkl-subtab-btn <?= $subTab === 'selesai' ? 'active' : '' ?>"
        onclick="switchSubTab('selesai', null)">
        <i class="fas fa-user-graduate"></i> PKL Selesai
    </button>
    <button class="pkl-subtab-btn <?= $subTab === 'nonaktif' ? 'active' : '' ?>"
        onclick="switchSubTab('nonaktif', null)">
        <i class="fas fa-user-slash"></i> PKL Non-Aktif
    </button>
</div>

<?php
// Helper untuk render tabel per sub-tab
function renderTabelPkl(string $id, array $rows, string $subTab, string $urlPklBase): string
{
    // ── Toolbar ──────────────────────────────────────────────────
    $toolbar = '';
    if ($subTab === 'aktif') {
        $toolbar = '<a href="' . $urlPklBase . '/tambah" class="btn-mpkl-add">
                        <i class="fas fa-plus"></i> Tambah
                    </a>';
    }

    $filterLabel = match ($subTab) {
        'selesai'  => 'PKL Selesai',
        'nonaktif' => 'PKL Non-Aktif',
        default    => 'PKL Aktif',
    };

    // ── Filter rows: 3 baris seragam semua sub-tab ────────────────
    // Row 1: Cari Nama PKL (full width)
    // Row 2: Kategori PKL + Nama Instansi (2 kolom sama lebar)
    // Row 3: Tgl Mulai + Tgl Akhir      (2 kolom sama lebar)
    // Non-Aktif + Row 4: Status Kelompok (full width)
    $idCap = ucfirst($id);

    $filterStatusKel = '';
    if ($subTab === 'nonaktif') {
        $filterStatusKel = '
                <div class="filter-row-full">
                    <label class="filter-label"><i class="fas fa-info-circle"></i> Status Kelompok</label>
                    <select id="filter' . $idCap . 'StatusKel" class="filter-select pkl-filter-field" data-col="6" data-table="tabel-' . $id . '">
                        <option value="">Semua Status</option>
                        <option value="aktif">Aktif</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>';
    }

    $filterBlock = '
        <div class="mpkl-filter-panel pkl-filter-panel" id="filter-' . $id . '" style="display:none">
            <div class="filter-panel-header">
                <span><i class="fas fa-filter"></i> Filter ' . $filterLabel . '</span>
                <button class="btn-filter-reset btn-reset-pkl" type="button" data-table="tabel-' . $id . '">
                    <i class="fas fa-redo"></i> Reset
                </button>
            </div>
            <div class="filter-panel-body filter-pkl-grid">
                <div class="filter-row-full">
                    <label class="filter-label"><i class="fas fa-search"></i> Cari Nama PKL</label>
                    <input type="text" id="filter' . $idCap . 'Nama"
                           class="filter-input pkl-filter-nama" data-col="1" data-table="tabel-' . $id . '"
                           placeholder="Ketik nama PKL...">
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-tags"></i> Kategori PKL</label>
                    <select id="filter' . $idCap . 'Kategori" class="filter-select pkl-filter-field" data-col="2" data-table="tabel-' . $id . '">
                        <option value="">Semua Kategori</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="Instansi">Instansi</option>
                    </select>
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-building"></i> Nama Instansi</label>
                    <select id="filter' . $idCap . 'Instansi" class="filter-select-instansi filter-select pkl-filter-instansi" data-col="3" data-table="tabel-' . $id . '">
                        <option value="">Pilih Instansi</option>
                    </select>
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-calendar-alt"></i> Tanggal Mulai</label>
                    <input type="text" id="filter' . $idCap . 'TglMulai"
                           class="filter-input flatpickr-date pkl-filter-date"
                           data-table="tabel-' . $id . '" data-date-type="mulai"
                           placeholder="Pilih tanggal">
                </div>
                <div class="filter-row-half">
                    <label class="filter-label"><i class="fas fa-calendar-check"></i> Tanggal Akhir</label>
                    <input type="text" id="filter' . $idCap . 'TglAkhir"
                           class="filter-input flatpickr-date pkl-filter-date"
                           data-table="tabel-' . $id . '" data-date-type="akhir"
                           placeholder="Pilih tanggal">
                </div>
                ' . $filterStatusKel . '
            </div>
        </div>';

    // ── Tbody ─────────────────────────────────────────────────────
    $tbody = '';
    foreach ($rows as $i => $row) {
        $kategori    = $row['kategori_pkl'] ?? 'mandiri';
        // Tampil plain: "Instansi" atau "Mandiri" — tanpa badge background
        $katLabel    = $kategori === 'mandiri' ? 'Mandiri' : 'Instansi';
        $instansiCol = $kategori === 'mandiri' ? '-' : esc($row['nama_instansi'] ?? '-');
        $tglMulai    = tglShort($row['tgl_mulai'] ?? null);
        $tglAkhir    = tglShort($row['tgl_akhir'] ?? null);

        if ($subTab === 'nonaktif') {
            $toggleIcon  = 'fa-user-check';
            $toggleTitle = 'Aktifkan';
            $toggleClass = 'btn-tbl-activate';
        } else {
            $toggleIcon  = 'fa-user-slash';
            $toggleTitle = 'Nonaktifkan';
            $toggleClass = 'btn-tbl-deactivate';
        }

        $tbody .= '<tr data-tgl-mulai="' . esc($row['tgl_mulai'] ?? '') . '"
            data-tgl-akhir="' . esc($row['tgl_akhir'] ?? '') . '"
            data-status-kelompok="' . esc($row['status_kelompok'] ?? '') . '">
            <td class="text-center">' . ($i + 1) . '</td>
            <td><strong>' . esc($row['nama_lengkap']) . '</strong></td>
            <td class="col-kategori-pkl">' . $katLabel . '</td>
            <td>' . $instansiCol . '</td>
            <td>' . $tglMulai . '</td>
            <td>' . $tglAkhir . '</td>
            <td class="text-center col-aksi-pkl">
                <a href="' . $urlPklBase . '/detail/' . $row['id_pkl'] . '?sub=' . $subTab . '" class="btn-tbl-view" title="Lihat Detail">
                    <i class="fas fa-eye"></i>
                </a>
                <button class="btn-tbl-toggle ' . $toggleClass . '" type="button" title="' . $toggleTitle . '"
                        data-id="' . $row['id_pkl'] . '"
                        data-nama="' . esc($row['nama_lengkap']) . '"
                        data-status="' . ($row['status_user'] ?? 'aktif') . '">
                    <i class="fas ' . $toggleIcon . '"></i>
                </button>
                <button class="btn-tbl-delete btn-delete-pkl" type="button" title="Hapus"
                        data-id="' . $row['id_pkl'] . '"
                        data-nama="' . esc($row['nama_lengkap']) . '"
                        data-role="' . esc($row['role_kel_pkl'] ?? '') . '">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>';
    }

    return '
    <div class="mpkl-card" id="content-' . $id . '">
        <div class="mpkl-toolbar">
            ' . $toolbar . '
            <button class="btn-mpkl-filter btn-filter-pkl" type="button" data-target="filter-' . $id . '">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
        ' . $filterBlock . '
        <div class="mpkl-table-wrap">
            <table id="tabel-' . $id . '" class="mpkl-table pkl-table" width="100%">
                <thead>
                    <tr>
                        <th class="col-no">No</th>
                        <th>Nama</th>
                        <th class="col-kategori-th">Kategori</th>
                        <th>Instansi</th>
                        <th>Tgl Mulai</th>
                        <th>Tgl Akhir</th>
                        <th class="col-aksi-th">Aksi</th>
                    </tr>
                </thead>
                <tbody>' . $tbody . '</tbody>
            </table>
        </div>
    </div>';
}
?>

<!-- ── Sub-Tab Contents ── -->
<div id="subtab-aktif" class="pkl-subtab-section <?= $subTab === 'aktif'    ? 'active' : '' ?>">
    <?= renderTabelPkl('aktif',    $pkl_aktif,    'aktif',    $urlPklBase) ?>
</div>
<div id="subtab-selesai" class="pkl-subtab-section <?= $subTab === 'selesai'  ? 'active' : '' ?>">
    <?= renderTabelPkl('aktif-selesai', $pkl_selesai, 'selesai', $urlPklBase) ?>
</div>
<div id="subtab-nonaktif" class="pkl-subtab-section <?= $subTab === 'nonaktif' ? 'active' : '' ?>">
    <?= renderTabelPkl('aktif-nonaktif', $pkl_nonaktif, 'nonaktif', $urlPklBase) ?>
</div>

<script>
    var instansiListPkl = <?= json_encode(array_column($instansiList ?? [], 'nama_instansi'), JSON_UNESCAPED_UNICODE) ?>;
    var urlPklDelete = '<?= base_url('admin/manajemen-pkl/pkl/delete') ?>';
    var urlPklToggle = '<?= base_url('admin/manajemen-pkl/pkl/toggle-status') ?>';
    var urlPklAnggota = '<?= base_url('admin/manajemen-pkl/pkl/anggota-kelompok') ?>';
    var activeSubTab = '<?= $subTab ?>';
</script>
