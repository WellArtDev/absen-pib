# AbsenPIB

Mobile attendance app with GPS + photo + anti-fake GPS + overtime + leave management.
React Native (Expo) + PHP Native MySQL full-stack.

## Quick Start
```bash
# Backend
cd backend && composer install && cp .env.example .env
php -S localhost:8000 -t public/

# Mobile
npm install --legacy-peer-deps
cp .env.example .env
npx expo start
```

## Stack
- **Mobile**: Expo SDK 52, Expo Router 4, Zustand, TanStack Query, NativeWind
- **Maps**: OpenStreetMap (free tile), Nominatim (free geocode)
- **Backend**: PHP 8.1+ Native, MySQL 8, JWT (built-in, no library)
- **Notifications**: ntfy.sh (free) + DB logging

## Directory
```
app/(auth)/      → login, register, forgot-password
app/(app)/(tabs)/→ home, history, profile
app/(app)/admin/ → dashboard, employees, approvals, reports, config
app/(app)/overtime/ → start/end overtime
app/(app)/leave/ → submit leave
app/(app)/attendance/[id] → detail
services/        → api, auth, attendance, overtime, leave, admin, location, camera, nominatim, offlineQueue
stores/          → auth, attendance, overtime, leave (Zustand)
backend/         → PHP Native REST API
  public/         → index.php (front controller)
  src/controllers/→ 9 controllers
  src/utils/      → Validator, ImageUpload, AntiFakeGps, CsvExport, Notification
  src/migrations/ → MySQL schema
```

## RBAC (5 roles multi-tenant)
- **superadmin** → manage companies, global stats
- **owner** → manage employees, config office, approve overtime/leave
- **admin** → approve overtime/leave, view reports
- **sales** → mobile attendance anywhere, submit overtime/leave
- **karyawan** → office attendance (geofence), submit overtime/leave

## Anti-Fake GPS
- Client: mock location detection, multi-provider cross-check, altitude check
- Server: duplicate coords detection, impossible travel, GPS timestamp sanity
- Suspicion score 0-5, suspect if ≥ 3

## API Endpoints (47 routes)
See `backend/src/routes.php` for full list.
Test: `curl localhost:8000/auth/login -d '{"email":"superadmin@absenpib.com","password":"admin123"}'`

## Database Setup (Laragon)
```bash
# MySQL via Laragon
c:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql -u root -pwella absen_pib < backend/src/migrations/001_initial.sql
```

## Default Superadmin
- Email: superadmin@absenpib.com
- Password: admin123
