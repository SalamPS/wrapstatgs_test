<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'LamP CMS') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --brand-main: #1d2337;
        }

        body {
            background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
            color: var(--brand-text);
        }

        .navbar-brand {
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .app-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.07);
        }

        .badge-soft {
            background-color: rgba(245, 158, 11, 0.12);
            color: #92400e;
        }

        .navbar {
            position: sticky !important;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--brand-main);">
    <div class="container">
        <a class="navbar-brand" href="/">LamP CMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="/">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="/users">Member</a></li>
                <li class="nav-item"><a class="nav-link" href="/products">Produk</a></li>
                <li class="nav-item"><a class="nav-link" href="/transactions">Transaksi</a></li>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?= $this->include('partials/flash') ?>
    <?= $this->renderSection('content') ?>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
