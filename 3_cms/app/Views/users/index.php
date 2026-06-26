<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Data Member</h1>
    <a href="/users/create" class="btn btn-primary">Tambah Member</a>
</div>

<div class="card app-card">
    <div class="table-responsive">
        <table class="table table-striped mb-0 align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Alamat</th>
                <th width="170">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada data member.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= esc((string) $user['id']) ?></td>
                        <td><?= esc($user['name']) ?></td>
                        <td><?= esc($user['email']) ?></td>
                        <td><?= esc($user['phone']) ?></td>
                        <td><?= esc((string) $user['address']) ?></td>
                        <td>
                            <a href="/users/edit/<?= esc((string) $user['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                            <form action="/users/delete/<?= esc((string) $user['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Hapus member ini?');">
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
