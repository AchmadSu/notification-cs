<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

🚀 Notification Queue System

Sistem ini menyediakan REST API untuk membuat job pengiriman notifikasi (email/SMS) dan worker process yang memproses job secara paralel dengan mekanisme retry, exponential backoff, jitter, idempotency, dan anti–double-processing.

✅ Fitur Utama
| Fitur                       | Penjelasan                                                |
| --------------------------- | --------------------------------------------------------- |
| **POST /api/notifications** | Menciptakan job pengiriman notifikasi secara asynchronous |
| **Idempotency Key**         | Request duplikat tidak membuat job baru                   |
| **Worker**                  | Memproses job dengan status `PENDING` / `RETRY`           |
| **Retry otomatis**          | Menggunakan exponential backoff + jitter                  |
| **Anti double-processing**  | Aman untuk banyak worker paralel (concurrency-safe)       |
| **Queue Stats**             | Endpoint untuk melihat statistik job                      |

📌 1. Cara Menjalankan Aplikasi
✅ A. Install Dependencies
```bash
composer install
```
Silakan sesuaikan .env anda dengan .env.example

✅ B. Jalankan migrasi database
```bash
php artisan migrate
```

Secara default akan berjalan pada: http://localhost:8000/

✅ C. Menjalankan REST API
```bash
php artisan serve
```

Anda dapat melihat contoh Request API melalui collection Postman berikut:

https://www.postman.com/warped-shuttle-585736/workspace/cipta-satria/collection/16178191-06262d75-7913-42e7-8f8e-3d9491cee8b5?action=share&creator=16178191

📌 2. Keputusan Teknis Utama
✅ Mengapa tidak memakai Redis Queue / Laravel Horizon?

Untuk memenuhi requirement challenge, seluruh mekanisme dibangun manual menggunakan:

1. Tabel notification_jobs

2. Worker custom

3. Locking / concurrency-safe

4. Retry logic dan backoff

Hal ini menunjukkan pemahaman sistem queue internal tanpa bergantung pada library queue Laravel.

✅ Mengapa PostgreSQL?

Karena PostgreSQL mendukung:
```sql
FOR UPDATE SKIP LOCKED
```
Perintah ini memungkinkan banyak worker paralel menarik job tanpa race condition.

MySQL/MariaDB hanya mendukung fitur ini pada versi tertentu dan sering bermasalah.

📌 3. Strategi Retry, Backoff, dan Jitter

Sistem menerapkan exponential backoff:

```ini
next_delay_seconds = (2 ^ attempts) + random_jitter
```

Tujuannya:

✅ Menghindari spam retry sekaligus

✅ Mengurangi efek “retry storm" jika banyak job gagal bersamaan

✅ Mendistribusikan retry pada waktu acak

Jika attempts >= max_attempts, job berubah menjadi FAILED

📌 4. Mekanisme Anti Double-Processing

Sistem harus aman dengan banyak worker paralel.

✅ Solusinya:
```sql
SELECT ... FOR UPDATE SKIP LOCKED
```

Ketika worker menarik job:

Database mengunci baris (row-level lock)

Job hanya bisa diklaim oleh satu worker

Worker lain akan melewati row yang terkunci (SKIP LOCKED)

Status langsung diubah ke PROCESSING

✅ Tidak ada dua worker yang memproses job sama
✅ Jika worker mati tiba-tiba → job tetap aman dan bisa diproses ulang saat next_run_at tercapai
✅ Full concurrency-safe tanpa Redis

❓ Bantuan / Kontak

Jika terdapat kendala atau pertanyaan lebih lanjut, silakan hubungi:

📧 Email: ecepentis@gmail.com
📱 WhatsApp: 0896-5842-0438

Regards,
Ecep Achmad Sutisna
