<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card app-card">
    <div class="card-header bg-white">
        <h1 class="h4 mb-0"><?= esc($title) ?></h1>
    </div>
    <div class="card-body">
        <form method="post" action="<?= $mode === 'create' ? '/users/store' : '/users/update/' . $user['id'] ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="name" class="form-label">Nama</label>
                <input type="text" name="name" id="name" class="form-control" required value="<?= esc(old('name', $user['name'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" required value="<?= esc(old('email', $user['email'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label">Telepon</label>
                <input type="text" name="phone" id="phone" class="form-control" required value="<?= esc(old('phone', $user['phone'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Alamat</label>
                <textarea name="address" id="address" class="form-control" rows="2"><?= esc(old('address', $user['address'] ?? '')) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="/users" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
