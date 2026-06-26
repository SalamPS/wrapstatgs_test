<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Data Transaksi</h1>
    <a href="/transactions/create" class="btn btn-primary">Tambah Transaksi</a>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Member</th>
                <th>Produk</th>
                <th>Qty</th>
                <th>Harga Satuan</th>
                <th>Total</th>
                <th>Pembayaran</th>
                <th>Catatan</th>
                <th width="170">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Belum ada data transaksi.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td><?= esc((string) $trx['id']) ?></td>
                        <td><?= esc($trx['user_name']) ?></td>
                        <td><?= esc($trx['product_name']) ?></td>
                        <td><?= esc((string) $trx['qty']) ?></td>
                        <td>Rp <?= number_format((float) $trx['unit_price'], 0, ',', '.') ?></td>
                        <td>Rp <?= number_format((float) $trx['total_price'], 0, ',', '.') ?></td>
                        <td><?= esc($trx['payment_method']) ?></td>
                        <td><?= esc((string) $trx['notes']) ?></td>
                        <td>
                            <a href="/transactions/edit/<?= esc((string) $trx['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form action="/transactions/delete/<?= esc((string) $trx['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus transaksi ini?');">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
