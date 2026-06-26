<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center mb-3">
    <div>
        <h1 class="h3 mb-1">Dashboard Transaksi</h1>
        <p class="text-muted mb-0">Lampiran daftar transaksi di Toserba LamP.</p>
    </div>
    <span class="badge badge-soft px-3 py-2 mt-2 mt-lg-0">CodeIgniter 4 CRUD</span>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card app-card">
            <div class="card-body">
                <small class="text-muted">Total Member</small>
                <div class="fs-3 fw-bold"><?= esc((string) $stats['users']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card app-card">
            <div class="card-body">
                <small class="text-muted">Total Produk di Etalase</small>
                <div class="fs-3 fw-bold"><?= esc((string) $stats['products']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card app-card">
            <div class="card-body">
                <small class="text-muted">Total Transaksi</small>
                <div class="fs-3 fw-bold"><?= esc((string) $stats['transactions']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card app-card">
            <div class="card-body">
                <small class="text-muted">Total Stok</small>
                <div class="fs-3 fw-bold"><?= esc((string) ($stats['stock'] ?? 0)) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="card app-card">
    <div class="card-header bg-white">
        <h2 class="h5 mb-0">Transaksi Terbaru</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead>
            <tr>
                <th>#</th>
                <th>Member</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Metode Bayar</th>
                <th>Total</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($recentTransactions)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada transaksi.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($recentTransactions as $trx): ?>
                    <tr>
                        <td><?= esc((string) $trx['id']) ?></td>
                        <td><?= esc($trx['user_name']) ?></td>
                        <td><?= esc($trx['product_name']) ?></td>
                        <td><?= esc((string) $trx['qty']) ?></td>
                        <td><?= esc($trx['payment_method']) ?></td>
                        <td>Rp <?= number_format((float) $trx['total_price'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
