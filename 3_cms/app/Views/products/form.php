<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card app-card">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0"><?= esc($title) ?></h1>
    </div>
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? '/products/store' : '/products/update/' . $product['id'] ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="product_name" class="form-label">Nama Produk</label>
                <input type="text" name="product_name" id="product_name" class="form-control" required value="<?= esc(old('product_name', $product['product_name'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label for="price" class="form-label">Harga</label>
                <input type="number" name="price" id="price" class="form-control" required min="0" step="0.01" value="<?= esc((string) old('price', $product['price'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label for="qty_in_stock" class="form-label">Stok</label>
                <input type="number" name="qty_in_stock" id="qty_in_stock" class="form-control" required min="0" step="1" value="<?= esc((string) old('qty_in_stock', $product['qty_in_stock'] ?? 0)) ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea name="description" id="description" class="form-control" rows="2"><?= esc(old('description', $product['description'] ?? '')) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/products" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
