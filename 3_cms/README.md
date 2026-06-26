# LamP CMS | Simulasi Pembelian

Project ini telah diimplementasikan menjadi CMS sederhana untuk simulasi pembelian produk dengan fitur CRUD:

- CRUD Member
- CRUD Produk
- CRUD Transaksi
- Otomatis pengurangan stok saat transaksi dibuat
- Otomatis penyesuaian stok saat transaksi diubah atau dihapus

## Tentang Proyek

### Struktur tabel utama

- `users`
- `products`
- `transactions`

### SQL dump

File export database tersedia di:

- `dumps/cms_dump.sql`

### Menjalankan project

1. Copy file `env` menjadi `.env`.
2. Atur konfigurasi database pada `.env`:

```ini
database.default.hostname = localhost
database.default.database = nama_database
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
database.default.port = 3306
```

3. Buat tabel dengan salah satu cara berikut:

- Buat database SQL bernama cms_pembelian
- Import file SQL dump `dumps/cms_dump.sql`
- Atau jalankan migration:

```bash
php spark migrate
```

1. Jalankan server lokal:

```bash
php spark serve
```

5. Akses aplikasi di:

- `http://localhost:8080`

## Server Requirements

PHP versi 8.2, dengan ekstensi berikut:

- json
- [intl](http://php.net/manual/en/intl.requirements.php)
- [mbstring](http://php.net/manual/en/mbstring.installation.php)
- [mysqlnd](http://php.net/manual/en/mysqlnd.install.php)
- [libcurl](http://php.net/manual/en/curl.requirements.php)

serta pastikan client tersambung ke internet, karena proyek ini memanfaatkan Bootstrap CDN
