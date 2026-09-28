<?php
require_once __DIR__ . '/../config/app.php';
$topbar_user = $_SESSION['user'] ?? [];
?>
<div class="top-header">
    <div class="global-product-search" id="globalProductSearch" data-endpoint="<?= BASE_URL ?>/products/lookup.php">
        <label class="visually-hidden" for="globalProductSearchInput">Cari barang berdasarkan kode atau nama</label>
        <span class="global-search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.5"></circle><path d="m16 16 4.5 4.5"></path></svg>
        </span>
        <input id="globalProductSearchInput" type="search" class="form-control" placeholder="Cari kode atau nama barang..." autocomplete="off" aria-autocomplete="list" aria-controls="globalProductSearchResults" aria-expanded="false">
        <span class="search-shortcut" aria-hidden="true">Cari stok</span>
        <div id="globalProductSearchResults" class="global-search-results" role="listbox" hidden></div>
    </div>
    <div class="top-header-user">
        <div class="top-user-avatar"><?= strtoupper(substr($topbar_user['name'] ?? 'A', 0, 1)) ?></div>
        <div class="top-user-info">
            <strong><?= htmlspecialchars($topbar_user['name'] ?? 'Administrator') ?></strong>
            <small><?= htmlspecialchars(ucfirst($topbar_user['role'] ?? 'admin')) ?></small>
        </div>
    </div>
</div>

<div class="modal fade" id="globalProductDetailModal" tabindex="-1" aria-labelledby="globalProductDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content product-detail-modal">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title" id="globalProductDetailTitle">Detail Barang</h2>
                    <div class="product-detail-code" id="globalDetailCode">Kode barang</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="product-detail-summary">
                    <div><span>Kategori</span><strong id="globalDetailCategory">-</strong></div>
                    <div><span>Status stok</span><strong id="globalDetailStatus">-</strong></div>
                    <div><span>Stok tersedia</span><strong id="globalDetailStock">-</strong></div>
                    <div><span>Minimum stok</span><strong id="globalDetailMinimum">-</strong></div>
                </div>
                <div class="product-detail-history-heading"><h3>Pergerakan stok terakhir</h3><span id="globalDetailUnit"></span></div>
                <div id="globalDetailMovements" class="product-detail-movements"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div>
    </div>
</div>
