# BlogVerse Pro — Studi Kasus P8 (Laravel 13, Inertia.js & React)

Proyek: `ifs24021-pabwe2026-sk-p8-laravel-inertia`

Platform publikasi artikel tiga tingkat (Publik, Penulis, Admin) dengan arsitektur **Clean Architecture & Feature-First**, dibangun dengan Laravel 13, Inertia.js 2, React 18, PostgreSQL (`pabwe_p8_blog`), PhpSpreadsheet, TOAST UI Editor, dan integrasi OpenAI-compatible API.

## Hasil mutu

| Kriteria | Hasil |
| --- | --- |
| Pengujian | 106 test, 578 assertion, 0 failure / warning / notice |
| Code coverage `app/` | 100% kelas (23/23), 100% method (105/105), 100% baris (690/690) |
| Gerbang coverage Jenkinsfile | Lines 690/690, Methods 105/105, Elements 795/795 |
| `npm audit` | 0 vulnerabilities |
| `composer audit` | jalankan di laptop (`composer audit`); Symfony sudah 8.1.11 |

## Menjalankan di Windows

1. **PostgreSQL 17** terpasang dan servisnya berjalan. Buat database:
   ```powershell
   psql -U postgres -c "CREATE DATABASE pabwe_p8_blog;"
   ```
2. Di `php.ini`, aktifkan (hapus `;`): `extension=pdo_pgsql`, `extension=pgsql`, `extension=gd`, `extension=zip`.
3. Di folder proyek:
   ```powershell
   composer install
   copy .env.example .env
   php artisan key:generate
   ```
4. Sesuaikan `.env` (jangan di-commit):
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=pabwe_p8_blog
   DB_USERNAME=postgres
   DB_PASSWORD=postgres   # sesuaikan password PostgreSQL Anda

   OPENAI_ENDPOINT=https://...   # isi hanya di .env
   OPENAI_KEY=...
   OPENAI_MODEL=...
   ```
5. Migrasi, seeder, aset, dan server:
   ```powershell
   php artisan migrate --seed
   php artisan storage:link
   npm install --ignore-scripts
   npm run build
   php artisan serve
   ```
6. Buka http://127.0.0.1:8000. Akun seeder: **admin / admin** dan **member / member**.

Pengujian: `composer test` (atau `test.bat`). Pengujian memakai SQLite in-memory, jadi tidak menyentuh data PostgreSQL.

## Tiga tingkat akses

| Tingkat | URL | Hak akses |
| --- | --- | --- |
| Umum (Publik) | `/`, `/articles`, `/articles/{slug}`, `/login`, `/register` | Tanpa login. Galeri, cari, filter kategori, baca artikel Markdown. Sticky navbar + footer, tanpa sidebar. |
| Anggota (Penulis) | `/dashboard/posts`, `/dashboard/posts/{post}` | CRUD artikel sendiri, draf ↔ publikasi, sampul, Excel, AI. |
| Admin | `/admin/users`, `/admin/posts`, `/admin/users/{user}/posts` | Kelola pengguna, pantau semua artikel (read-only), ekspor Excel. |

## Peta berkas penting

### Backend (`app/`)

| Berkas | Fungsi |
| --- | --- |
| `Features/Post/Models/Post.php` | Model Eloquent tabel `posts`; relasi `user()`, cast `is_published`/`published_at`, `coverImageUrl()`, `readingMinutes()`. |
| `Features/Post/Enums/PostCategory.php` | Enam kategori (Teknologi … Umum), label, warna badge, `options()` untuk frontend, `fromLoose()` untuk data Excel/AI. |
| `Features/Post/Contracts/PostRepositoryInterface.php` | Kontrak akses data (Dependency Inversion). |
| `Features/Post/Repositories/PostRepository.php` | Satu-satunya tempat query. Pencarian `LOWER(...) LIKE` agar tidak peka huruf besar di PostgreSQL; statistik dalam satu query agregat. |
| `Features/Post/Services/PostService.php` | Aturan bisnis: slug unik otomatis, ringkasan otomatis, waktu publikasi, toggle draf/publish, sampul. |
| `Features/Post/Services/PostExcelService.php` | Ekspor/impor PhpSpreadsheet. Format ekspor = format impor; impor memakai satu `insert`. |
| `Features/Post/Policies/PostPolicy.php` | Admin boleh melihat; hanya penulis yang boleh mengubah/menghapus. |
| `Features/Post/Controllers/PublicPostController.php` | Halaman publik (`/`, `/articles`, `/articles/{slug}`); draf → 404. |
| `Features/Post/Controllers/PostController.php` | Dashboard penulis: CRUD, publish, sampul, Excel, AI generate/save. |
| `Features/Post/Controllers/AdminPostController.php` | Admin: pantau & ekspor semua artikel. |
| `Features/Admin/…` | Daftar pengguna + jumlah artikel, artikel per pengguna, ekspor per pengguna. |
| `Features/Ai/…` | `AiClientInterface`, DTO `GeneratedPostItem`, `OpenAiPostGeneratorService` (balasan JSON dibersihkan oleh `parseContent`). |
| `Features/Auth/…`, `Features/Profile/…` | Login (username/email), register, logout, profil, password, foto. |
| `Http/Middleware/EnsureRole.php` | `role:admin`, `role:member`, atau `role:admin|member`. |
| `Http/Middleware/EnsurePostOwnership.php` | `owns.post`: member hanya menyentuh artikel sendiri; admin hanya GET. |
| `Http/Middleware/HandleInertiaRequests.php` | Data bersama ke React: `auth.user`, `flash`. |
| `Providers/AppServiceProvider.php` | Binding interface, policy, rate limit 1000/detik, force HTTPS. |

### Frontend (`resources/js/`)

| Berkas | Fungsi |
| --- | --- |
| `app.jsx` | `createInertiaApp`; halaman dimuat lazy per chunk sehingga TOAST UI hanya diunduh bila perlu. |
| `Layouts/PublicLayout.jsx` | Sticky top navbar (Masuk/Daftar atau avatar + Dashboard/Panel Admin) dan footer editorial. |
| `Layouts/AppLayout.jsx` | Layout dashboard dengan sidebar untuk penulis/admin. |
| `Pages/Public/PostIndex.jsx` | Hero + CTA Login/Register, pencarian, filter kategori, galeri. |
| `Pages/Public/PostShow.jsx` | Pembaca artikel (TOAST UI Viewer) + kartu ajakan komunitas Login/Register. |
| `Pages/Post/Index.jsx` | List view, 3 kartu statistik, menu kebab ⋮, toggle publish, modal AI (`type="number"` 1–5), impor, hapus. |
| `Pages/Post/Show.jsx` | Detail artikel penulis, sampul, ubah, publish, hapus. |
| `Pages/Admin/*.jsx` | Kelola pengguna, semua artikel, artikel per pengguna (read-only). |
| `Components/PostFormModal.jsx` | Modal Tambah/Ubah full screen, header berwarna beda (indigo/amber), footer kontras, tombol di kanan. |
| `Components/ProcessingIndicator.jsx` | Progress bar atas + pil "Memproses tindakan...". |
| `Components/MarkdownEditor.jsx` / `MarkdownViewer.jsx` | Pembungkus TOAST UI Editor / Viewer. |

### Database & pengujian

- `database/migrations/2026_11_01_000003_create_posts_table.php` — tabel `posts` sesuai modul (slug unik, `is_published`, `published_at`).
- `database/factories/PostFactory.php`, `database/seeders/DatabaseSeeder.php` — data uji & akun admin/member.
- `tests/Unit` — enum, DTO, model, policy, middleware, repository, service, provider.
- `tests/Feature` — alur publik, penulis, admin, autentikasi, profil, akses ilegal (403/404).

## Keamanan & berkas yang tidak di-commit

`.env`, `vendor/`, `node_modules/`, `public/build/`, `coverage*`, dan isi `storage/` (kecuali `.gitignore`-nya) tidak di-commit. API key hanya di `.env`.
