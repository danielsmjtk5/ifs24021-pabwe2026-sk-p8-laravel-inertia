<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed accounts admin/admin and member/member plus sample articles.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member Penulis',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        $samples = [
            [
                'slug' => 'mengenal-inertia-js-jembatan-laravel-dan-react',
                'title' => 'Mengenal Inertia.js: Jembatan Laravel dan React',
                'category' => 'Teknologi',
                'excerpt' => 'Inertia.js memungkinkan kita membangun aplikasi satu halaman tanpa harus membuat API terpisah.',
                'content' => "## Apa itu Inertia.js?\n\nInertia.js menghubungkan controller Laravel langsung dengan komponen React. Controller cukup mengembalikan `Inertia::render()` beserta data, lalu React menampilkannya.\n\n## Kenapa dipakai?\n\n- Tidak perlu membuat REST API terpisah.\n- Routing dan autentikasi tetap memakai Laravel.\n- Navigasi terasa cepat seperti SPA.",
                'is_published' => true,
            ],
            [
                'slug' => 'tips-produktif-belajar-pemrograman-setiap-hari',
                'title' => 'Tips Produktif Belajar Pemrograman Setiap Hari',
                'category' => 'Gaya Hidup',
                'excerpt' => 'Konsistensi kecil setiap hari lebih bermanfaat daripada belajar lama sekali seminggu.',
                'content' => "## Mulai dari target kecil\n\nTetapkan target 30 menit per hari dan catat kemajuan Anda.\n\n## Bangun proyek nyata\n\nTeori akan lebih melekat ketika langsung dipakai di proyek kecil.",
                'is_published' => true,
            ],
            [
                'slug' => 'draf-rencana-artikel-clean-architecture',
                'title' => 'Draf: Rencana Artikel Clean Architecture',
                'category' => 'Pendidikan',
                'excerpt' => null,
                'content' => "## Kerangka\n\n1. Masalah kode yang menumpuk di controller\n2. Pemisahan lapisan\n3. Contoh repository dan service",
                'is_published' => false,
            ],
        ];

        foreach ($samples as $sample) {
            Post::firstOrCreate(
                ['slug' => $sample['slug']],
                [
                    ...$sample,
                    'user_id' => $member->id,
                    'published_at' => $sample['is_published'] ? now() : null,
                ]
            );
        }
    }
}
