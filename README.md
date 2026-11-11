# BlogVerse Pro — PABWE 2026 Studi Kasus Praktikum 8 (Laravel 13, Inertia.js & React)

Platform publikasi artikel **multi-tier** dengan arsitektur **Clean Architecture & Feature-First**, dibangun menggunakan **Laravel 13**, **Inertia.js**, **React**, **PostgreSQL** (`pabwe_p8_blog`), **PhpOffice/PhpSpreadsheet**, dan integrasi **OpenAI**.

Nama proyek: `{username}-pabwe2026-sk-p8-laravel-inertia` (contoh: `ifs24035-pabwe2026-sk-p8-laravel-inertia`).

---

## Tiga Tingkatan Akses

| Tingkat | URL | Hak akses |
|---|---|---|
| 1. Publik (Guest) | `/`, `/articles`, `/articles/{slug}`, `/login`, `/register` | Membaca galeri artikel, mencari (judul/isi), filter kategori, membaca artikel Markdown + cover. Tanpa sidebar: **Sticky Top Navbar**, tata letak baca leluasa, **footer editorial**, dan tautan Login/Register di navbar, hero banner, kartu ajakan komunitas di akhir artikel, serta footer. |
| 2. Anggota (Penulis) | `/dashboard/posts`, `/dashboard/posts/{post}` | CRUD artikel milik sendiri, draf ↔ publikasi, foto cover, ekspor/impor Excel, AI generator (OpenAI). |
| 3. Admin | `/admin/users`, `/admin/posts`, `/admin/users/{user}/posts` | Kelola pengguna, pantau seluruh artikel (read-only), ekspor semua / per-user ke Excel. |

## Daftar Rute

| Method | URI | Akses |
|---|---|---|
| GET | `/`, `/articles`, `/articles/{slug}` | Publik |
| GET/POST | `/login`, `/register` | Guest |
| POST | `/logout` | Auth |
| GET | `/dashboard/posts` · `/dashboard/posts/{post}` | Member |
| POST | `/dashboard/posts` | Member |
| PUT / DELETE | `/dashboard/posts/{post}` | Member (pemilik) |
| PATCH | `/dashboard/posts/{post}/publish` | Member (pemilik) |
| POST / DELETE | `/dashboard/posts/{post}/cover` | Member (pemilik) |
| GET | `/dashboard/posts/export` | Member |
| POST | `/dashboard/posts/import` · `/ai-generate` · `/ai-save` | Member |
| GET | `/profile` (+ `PATCH /profile`, `PUT /profile/password`, `POST|DELETE /profile/photo`) | Auth |
| GET | `/admin/users` · `/admin/users/{user}/posts` · `/admin/users/{user}/posts/export` | Admin |
| GET | `/admin/posts` · `/admin/posts/export` | Admin |

## Struktur Folder (Feature-First)

```
app/
├── Features/
│   ├── Admin/    Controllers/AdminUserController · Services/AdminUserService
│   ├── Ai/       Contracts/AiClientInterface · DTO/GeneratedPostItem · Services/OpenAiPostGeneratorService
│   ├── Auth/     Actions/{LoginUserAction,RegisterUserAction} · Controllers/AuthController
│   ├── Post/     Contracts/PostRepositoryInterface
│   │             Controllers/{AdminPostController,PostController,PublicPostController}
│   │             Enums/PostCategory · Models/Post · Policies/PostPolicy
│   │             Repositories/PostRepository · Services/{PostExcelService,PostService}
│   └── Profile/  Controllers/ProfileController · Services/ProfileService
├── Http/Middleware/  EnsurePostOwnership · EnsureRole · HandleInertiaRequests
├── Models/User.php
└── Providers/AppServiceProvider.php

resources/js/
├── Layouts/      AppLayout (dashboard/admin, sidebar) · PublicLayout (navbar + footer)
├── Components/   MarkdownEditor · MarkdownViewer · Pagination · Spinner · ProcessingIndicator · ...
└── Pages/        Admin/{PostList,UserList,UserPostList} · Auth/{Login,Register}
                  Post/{Index,Show} · Profile/Edit · Public/{PostIndex,PostShow}
```

## Model Data

`posts`: `id`, `user_id` (FK cascade), `title`, `slug` (unique), `category`, `excerpt` (nullable), `content` (longText), `cover_image_path` (nullable), `is_published`, `published_at` (nullable), `timestamps`.
Kategori (`PostCategory`): Teknologi, Pendidikan, Gaya Hidup, Bisnis, Opini, Umum.

Keputusan desain:
- **Slug** dibuat otomatis dari judul, unik (`-2`, `-3`, …) dan **tidak berubah** saat judul diedit agar tautan publik tidak rusak.
- **Ringkasan** kosong otomatis dibuat dari isi artikel (maks. 160 karakter).
- `published_at` diisi saat dipublikasikan dan dikosongkan saat dijadikan draf.
- Pencarian memakai `LOWER(...) LIKE` sehingga **tidak sensitif huruf besar/kecil** di PostgreSQL maupun SQLite.
- Artikel hasil **AI disimpan sebagai draf** agar ditinjau penulis sebelum terbit.
- Sel Excel bertipe teks ditulis eksplisit sebagai string (mencegah *formula injection*).
- Penulis (termasuk admin) hanya dapat membuka/mengubah artikel miliknya di `/dashboard`; admin memantau lewat halaman `/admin/*` (read-only).

## Menjalankan Proyek

```bash
psql -U postgres -c "CREATE DATABASE pabwe_p8_blog;"

composer install
cp .env.example .env          # sesuaikan DB_*, OPENAI_ENDPOINT, OPENAI_KEY, OPENAI_MODEL
php artisan key:generate
php artisan migrate --seed
php artisan storage:link

npm install
npm run build                 # atau: npm run dev
php artisan serve
```

Akun seeder: `admin / admin` (Admin) dan `member / member` (Anggota). Seeder juga membuat artikel contoh agar galeri publik langsung terisi.

Konfigurasi `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pabwe_p8_blog
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

## Pengujian

```bash
composer test            # php vendor/bin/phpunit --coverage-text
composer audit           # harus 0 vulnerabilities
```

Tes memakai SQLite in-memory dan `withoutVite()` sehingga tidak memerlukan `npm run build`. Pengukuran coverage membutuhkan driver **PCOV** atau **Xdebug**. Target: **100% coverage** pada `app/`.

## Format Excel Impor / Ekspor

Kolom: `A No`, `B Title`, `C Category`, `D Excerpt`, `E Content (Markdown)`, `F Status (Published/Draft)`, `G Published At`, `H Created At`, `I Author`. Impor hanya membaca A–F; baris tanpa judul atau isi dilewati. Hasil ekspor dapat langsung diimpor kembali.
