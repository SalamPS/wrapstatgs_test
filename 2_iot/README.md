# Camera Control App

## Purpose Proyek

Proyek ini berfungsi sebagai modul IoT untuk kontrol kamera desktop secara real-time. Fokus utamanya adalah:

- Mengambil stream kamera secara langsung.
- Mengatur parameter kamera (resolusi, exposure, shutter, ISO, autofocus).
- Menyimpan snapshot secara manual atau burst capture.
- Menyediakan GUI yang cepat untuk workflow pengambilan data gambar.

Modul ini cocok dipakai sebagai tahap akuisisi data sebelum dipakai di pipeline AI/training pada folder lain.

## Ringkasan Arsitektur

Aplikasi utama ada di file `camera.py` dengan komponen utama:

- `CameraConfig`: konfigurasi default kamera.
- `build_capture()`: inisialisasi kamera dan set properti OpenCV.
- `CameraWindow`: GUI PyQt untuk preview, kontrol, dan capture.
- Timer frame (`QTimer`): update frame berkala untuk preview real-time.

## Fitur (Realtime Frame Pipeline)

- Baca frame kontinu dari device kamera via OpenCV.
- Fallback backend kamera di Windows.
- Set properti capture: width, height, fps, fourcc (MJPG).
- Kontrol exposure:
  - Auto exposure ON/OFF.
  - Manual shutter speed saat auto exposure OFF.
  - Penyesuaian ISO/gain saat mode manual.
- Resize frame preview agar tetap ringan ditampilkan di GUI.
- Hitung FPS aktual secara rolling per detik.
- Simpan frame menjadi JPEG dengan timestamp unik.
- Burst capture saat tombol Space ditekan dan ditahan.

## Fitur GUI

GUI dibangun dengan PyQt5, dengan fitur berikut:

- Live preview kamera.
- Kontrol parameter:
  - Camera index selector.
  - Resolusi (width dan height).
  - Shutter speed.
  - ISO.
  - Toggle auto exposure.
- Tombol aksi:
  - Apply Resolution.
  - Capture sekali.
  - Burst capture.
- Shortcut keyboard:
  - C: capture sekali.
  - Space (hold): burst capture beruntun.
  - Q: keluar aplikasi.
- Status panel real-time:
  - Kamera aktif.
  - Resolusi.
  - FPS.
  - Mode exposure.
  - Nilai shutter dan ISO.
  - Status burst.
  - Total file tersimpan.
  - Lokasi folder output.
- Visual feedback border preview:
  - Hijau saat capture sekali.
  - Kuning saat burst aktif.

## Dependensi

Dependensi Python yang diperlukan:

- `opencv-python`
- `PyQt5`

Semua dependensi disediakan di file `requirements.txt`.

## Setup Environment

Contoh setup di Bash Linux dari folder:

```bash
python -m venv viot
source viot/bin/activate
pip install --upgrade pip
pip install -r requirements.txt
```

Jika environment sudah aktif, prompt terminal biasanya menampilkan prefix `(viot)`.

## Cara Menjalankan

Jalankan aplikasi dari folder:

```bash
python camera.py
```

Hasil capture otomatis disimpan ke folder `captures/`.

## Struktur Folder

```text
2_iot/
|- camera.py
|- requirements.txt
|- README.md
|- captures/
\- viot/ (virtual environment, lokal)
```

## Catatan Operasional

- Pastikan kamera tidak sedang dipakai aplikasi lain.
- Jika kamera tidak terbuka, ganti camera index lewat dropdown.
- Di beberapa webcam, tidak semua properti (ISO/exposure/gain) didukung driver.
- Untuk pengembangan lanjutan (deteksi objek real-time), modul ini bisa dijadikan basis input stream sebelum model inference ditambahkan.
