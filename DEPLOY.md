# AbsenPIB — cPanel Deployment Guide

## 1. Upload Project

Upload isi `backend/` ke folder `/home/user/public_html/api-pib/` atau folder yang diinginkan.

Struktur di cPanel:
```
public_html/api-pib/
├── public/
│   ├── index.php        # Entry point
│   ├── .htaccess         # URL rewrite
│   └── dashboard/        # Web dashboard
├── src/
│   ├── controllers/
│   ├── utils/
│   ├── migrations/
│   └── ...
├── uploads/              # Pastikan writable (chmod 755)
├── vendor/               # Composer dependencies
├── composer.json
├── .env                  # Konfigurasi production
└── .htaccess             # Security
```

## 2. Setup Database

1. Buka phpMyAdmin cPanel → pilih database
2. Tab SQL → copy-paste isi `src/migrations/001_initial.sql`
3. Execute
4. Superadmin otomatis terbuat: `superadmin@absenpib.com` / `admin123`

## 3. Konfigurasi .env

Edit `.env` di root backend:
```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=nama_database_cpanel
DB_USER=nama_user_database
DB_PASS=password_database
JWT_SECRET=random-string-panjang-minimal-32-karakter
JWT_EXPIRY=86400
APP_URL=https://api-pib.domainanda.com
UPLOAD_DIR=../uploads
NODE_ENV=production
```

## 4. Composer Install

Via SSH:
```bash
cd public_html/api-pib
composer install --no-dev
```

Atau install lokal lalu upload folder `vendor/`.

## 5. Folder Permission

```bash
chmod 755 uploads/
chmod 755 uploads/attendance-photos/
chmod 755 uploads/leave-attachments/
```

## 6. Domain / Subdomain

Di cPanel → Domains, arahkan subdomain `api-pib.domainanda.com` ke folder `public_html/api-pib/public/`.

## 7. Test

```bash
curl https://api-pib.domainanda.com/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"superadmin@absenpib.com","password":"admin123"}'
```

## 8. Mobile App

Update `.env` di root mobile:
```
EXPO_PUBLIC_API_URL=https://api-pib.domainanda.com
```

## 9. Generate JWT Secret (random string)

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Copy hasilnya ke `.env` → `JWT_SECRET=`

## Troubleshooting

| Masalah | Solusi |
|---------|--------|
| 404 semua route | Cek `.htaccess` RewriteEngine |
| Token tidak ditemukan | Cek `.htaccess` baris `HTTP_AUTHORIZATION` |
| Upload foto gagal | Cek permission folder `uploads/` |
| Database error | Cek kredensial di `.env` |
| CORS error | Cek `index.php` header CORS, ubah origin jika perlu |
