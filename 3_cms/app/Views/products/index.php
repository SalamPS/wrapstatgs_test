<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Etalase Produk</h1>
    <a href="/products/create" class="btn btn-primary">Tambah Produk</a>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Deskripsi</th>
                <th width="170">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada data produk.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= esc((string) $product['id']) ?></td>
                        <td><?= esc($product['product_name']) ?></td>
                        <td>Rp <?= number_format((float) $product['price'], 0, ',', '.') ?></td>
                        <td><?= esc((string) $product['qty_in_stock']) ?></td>
                        <td><?= esc((string) $product['description']) ?></td>
                        <td>
                            <a href="/products/edit/<?= esc((string) $product['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form action="/products/delete/<?= esc((string) $product['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?');">
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
