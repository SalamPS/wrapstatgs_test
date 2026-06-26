<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card app-card">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0"><?= esc($title) ?></h1>
    </div>
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? '/transactions/store' : '/transactions/update/' . $transaction['id'] ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="user_id" class="form-label">Member</label>
                <select name="user_id" id="user_id" class="form-select" required>
                    <option value="">Pilih member</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= esc((string) $user['id']) ?>" <?= (string) old('user_id', $transaction['user_id'] ?? '') === (string) $user['id'] ? 'selected' : '' ?>>
                            <?= esc($user['name']) ?> (<?= esc($user['email']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="product_id" class="form-label">Produk</label>
                <select name="product_id" id="product_id" class="form-select" required>
                    <option value="">Pilih produk</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= esc((string) $product['id']) ?>" <?= (string) old('product_id', $transaction['product_id'] ?? '') === (string) $product['id'] ? 'selected' : '' ?>>
                            <?= esc($product['product_name']) ?> | Stok: <?= esc((string) $product['qty_in_stock']) ?> | Rp <?= number_format((float) $product['price'], 0, ',', '.') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="qty" class="form-label">Jumlah Beli</label>
                <input type="number" name="qty" id="qty" class="form-control" required min="1" step="1" value="<?= esc((string) old('qty', $transaction['qty'] ?? 1)) ?>">
            </div>
            <div class="mb-3">
                <label for="payment_method" class="form-label">Metode Pembayaran</label>
                <select name="payment_method" id="payment_method" class="form-select" required>
                    <option value="">Pilih metode pembayaran</option>
                    <?php foreach ($paymentMethods as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= old('payment_method', $transaction['payment_method'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= esc($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Catatan</label>
                <textarea name="notes" id="notes" class="form-control" rows="2"><?= esc(old('notes', $transaction['notes'] ?? '')) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/transactions" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
