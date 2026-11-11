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
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
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
                'author' => $member,
                'title' => 'Memulai Perjalanan dengan Laravel 13 dan Inertia.js',
                'slug' => 'memulai-perjalanan-dengan-laravel-13-dan-inertia-js',
                'category' => 'Teknologi',
                'excerpt' => 'Panduan singkat membangun aplikasi web modern tanpa harus memisahkan backend dan frontend.',
                'content' => "## Mengapa Inertia.js?\n\nInertia.js memungkinkan kita membangun *single-page application* menggunakan **Laravel** di sisi server dan **React** di sisi klien, tanpa perlu membuat REST API terpisah.\n\n### Langkah awal\n\n1. Buat proyek Laravel baru.\n2. Pasang adapter Inertia untuk Laravel dan React.\n3. Render halaman React langsung dari controller.\n\nDengan pendekatan *feature-first*, setiap fitur memiliki controller, service, dan repository-nya sendiri sehingga kode lebih mudah dirawat.",
                'published' => true,
            ],
            [
                'author' => $member,
                'title' => 'Tiga Kebiasaan Kecil untuk Belajar Lebih Efektif',
                'slug' => 'tiga-kebiasaan-kecil-untuk-belajar-lebih-efektif',
                'category' => 'Pendidikan',
                'excerpt' => 'Belajar tidak harus berjam-jam. Mulailah dari kebiasaan kecil yang konsisten.',
                'content' => "## Konsistensi mengalahkan intensitas\n\nBanyak orang menyerah karena menargetkan terlalu besar di awal. Cobalah tiga kebiasaan berikut:\n\n- **Belajar 25 menit** dengan teknik Pomodoro.\n- **Tulis ulang** materi dengan bahasa sendiri.\n- **Uji diri** dengan soal latihan setiap akhir pekan.\n\nLakukan secara rutin, hasilnya akan terasa dalam beberapa minggu.",
                'published' => true,
            ],
            [
                'author' => $member,
                'title' => 'Menata Hari dengan Prinsip Prioritas Sederhana',
                'slug' => 'menata-hari-dengan-prinsip-prioritas-sederhana',
                'category' => 'Gaya Hidup',
                'excerpt' => 'Produktif bukan berarti sibuk setiap saat, tetapi memilih hal yang benar-benar penting.',
                'content' => "## Pilih tiga hal utama\n\nSetiap pagi, tuliskan **tiga tugas terpenting** yang harus selesai hari itu. Kerjakan yang paling sulit lebih dulu saat energi masih penuh.\n\n> Fokus pada hal yang penting, bukan sekadar yang mendesak.\n\nSisihkan juga waktu istirahat agar tetap bugar sepanjang hari.",
                'published' => true,
            ],
            [
                'author' => $admin,
                'title' => 'Strategi Membangun Karir di Era Kerja Fleksibel',
                'slug' => 'strategi-membangun-karir-di-era-kerja-fleksibel',
                'category' => 'Bisnis',
                'excerpt' => 'Kerja jarak jauh membuka peluang baru sekaligus menuntut disiplin dan komunikasi yang kuat.',
                'content' => "## Komunikasi adalah kunci\n\nDi era kerja fleksibel, hasil kerja lebih dinilai daripada jam duduk di kantor. Beberapa strategi yang dapat dicoba:\n\n1. Bangun portofolio yang terlihat.\n2. Biasakan **update progres** secara tertulis.\n3. Investasikan waktu untuk belajar keterampilan baru.\n\nKarir yang kuat dibangun dari kebiasaan kecil yang dilakukan terus-menerus.",
                'published' => true,
            ],
            [
                'author' => $admin,
                'title' => 'Mengapa Menulis Itu Penting bagi Setiap Profesi',
                'slug' => 'mengapa-menulis-itu-penting-bagi-setiap-profesi',
                'category' => 'Opini',
                'excerpt' => 'Menulis melatih cara berpikir yang runtut dan membantu kita menyampaikan gagasan dengan jelas.',
                'content' => "## Menulis adalah berpikir\n\nKetika menulis, kita dipaksa menyusun gagasan secara runtut. Itulah mengapa **menulis** bermanfaat bagi programmer, dokter, guru, maupun wirausahawan.\n\nMulailah dari catatan harian singkat, lalu kembangkan menjadi artikel yang dapat dibagikan kepada khalayak.",
                'published' => true,
            ],
            [
                'author' => $member,
                'title' => 'Catatan Umum: Selamat Datang di BlogVerse Pro',
                'slug' => 'catatan-umum-selamat-datang-di-blogverse-pro',
                'category' => 'Umum',
                'excerpt' => 'Perkenalan singkat platform publikasi artikel BlogVerse Pro dan cara memulai menulis.',
                'content' => "## Selamat datang!\n\n**BlogVerse Pro** adalah platform publikasi artikel multi-tier. Daftarkan diri sebagai penulis, tulis artikel dengan editor Markdown, unggah foto cover, lalu publikasikan agar dapat dibaca semua orang.\n\nSelamat menulis!",
                'published' => true,
            ],
            [
                'author' => $member,
                'title' => 'Draf: Rencana Seri Tutorial React',
                'slug' => 'draf-rencana-seri-tutorial-react',
                'category' => 'Teknologi',
                'excerpt' => 'Draf outline seri tutorial React untuk pemula.',
                'content' => "## Outline\n\n- Dasar JSX\n- State dan props\n- Hooks\n\n*Masih dalam tahap penyusunan.*",
                'published' => false,
            ],
        ];

        foreach ($samples as $index => $sample) {
            Post::firstOrCreate(
                ['slug' => $sample['slug']],
                [
                    'user_id' => $sample['author']->id,
                    'title' => $sample['title'],
                    'category' => $sample['category'],
                    'excerpt' => $sample['excerpt'],
                    'content' => $sample['content'],
                    'is_published' => $sample['published'],
                    'published_at' => $sample['published'] ? now()->subDays(count($samples) - $index) : null,
                ]
            );
        }
    }
}
