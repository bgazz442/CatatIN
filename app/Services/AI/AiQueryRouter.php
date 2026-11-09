<?php

namespace App\Services\AI;

use Illuminate\Support\Str;

class AiQueryRouter
{
    public function route(string $question, string $history = '', string $summary = ''): array
    {
        $normalized = mb_strtolower(trim($question));
        $compact = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $compact = preg_replace('/[^\p{L}\p{N}\s?!,.-]/u', ' ', $compact) ?? $compact;
        $compact = preg_replace('/\s+/u', ' ', trim($compact)) ?? trim($compact);

        $compact = $this->normalizeAliases($compact);

        if (preg_match('/\b(?:daily[\s-]*activit(?:y|ies)|activity logs?|aktivitas(?:\s+harian)?|catat aktivitas)\b/iu', $compact) === 1) {
            return $this->result('retired_feature', [], 'retired-feature');
        }

        if (Str::contains($compact, ['another user', 'other user', 'pengguna lain', 'akun lain', 'user lain'])
            && Str::contains($compact, [
                'access', 'show', 'reveal', 'read', 'display', 'list',
                'akses', 'tampilkan', 'lihat', 'baca', 'ungkap',
            ])) {
            return $this->result('privacy_boundary', [], 'cross-user-data-request');
        }

        if (Str::contains($compact, [
            'apa saja fitur', 'fitur yang tersedia', 'fitur aktif', 'fitur tersedia',
            'what features are available', 'what features does catatin have',
        ])) {
            return $this->result('feature_list', [], 'active-feature-list');
        }

        if (Str::contains($compact, [
            'data yang bisa kamu akses', 'data yang dapat kamu akses', 'data apa yang bisa kamu akses',
            'what data can you access', 'what data do you access',
        ])) {
            return $this->result('data_access', [], 'data-access');
        }

        if (preg_match('/^(hai|halo|hi|hello)(?:[,.]?\s+(bro|gan|kak|teman|guys?|semuanya))?[!.?\s]*$/iu', $compact) === 1
            || preg_match('/^selamat (pagi|siang|sore|malam)(?:\s+(semuanya|kawan|teman))?[!.?\s]*$/iu', $compact) === 1
            || preg_match('/^(apa kabar|gimana kabar kamu)[!?.\s]*$/iu', $compact) === 1) {
            return $this->result('greeting', ['greeting'], 'greeting');
        }

        if (preg_match('/^(itu gimana|itu bagaimana|gimana itu|bagaimana dengan itu|terus itu|lalu itu|what about that|how so|kenapa|mengapa|kok|why)[?!.]*$/iu', $compact) === 1) {
            if (trim($history.$summary) === '') {
                return $this->result('ambiguous', [], 'ambiguous');
            }

            $topicIntent = $this->inferTopicIntent($history."\n".$summary);

            return $topicIntent === null
                ? $this->result('ambiguous', [], 'follow-up-clarification')
                : $this->result('follow_up', [$topicIntent], 'follow-up');
        }

        if (preg_match('/^(terima kasih|makasih|mksih|thanks?|thank you|oke(?:\s+|,\s*)sip|ok(?:ey)?(?:\s+sip)?|siap)[!?.\s]*$/iu', $compact) === 1
            || preg_match('/^(terima kasih|makasih|mksih)\b/iu', $compact) === 1) {
            return $this->result('acknowledgment', ['acknowledgment'], 'acknowledgment');
        }

        $explicitWorkspace = Str::contains($compact, [
            'kondisi workspace', 'workspace saya', 'apa saja yang saya punya',
            'jumlah seluruh catatan', 'jumlah catatan saya', 'catatan terbaru saya',
            'kondisi catatan saya', 'jumlah kategori', 'kategori saya',
            'kategori apa saja', 'kategori yang tersedia', 'kategori mana',
            'hubungan kategori', 'catatan dalam kategori', 'catatan kategori',
            'kategori tanpa catatan', 'kategori belum memiliki catatan',
            'berapa catatan dalam kategori', 'jumlah catatan dalam kategori',
            'task dalam kategori', 'tugas dalam kategori', 'jumlah seluruh workspace',
            'ringkas seluruh workspace', 'ringkasan workspace',
            'kondisi produktivitas saya', 'kondisi catatin saya',
            'berapa task yang belum selesai', 'berapa tugas yang belum selesai',
            'progres goal saya', 'bagaimana progres goal saya',
        ]);
        if ($explicitWorkspace) {
            $intents = ['workspace'];
            if (Str::contains($compact, [
                'task dalam kategori', 'tugas dalam kategori',
            ])) {
                $intents[] = 'productivity';
            }
            $asksForNoteItems = Str::contains($compact, [
                'apa saja catatan', 'apa catatan dalam kategori', 'daftar catatan',
                'sebutkan catatan', 'catatan kategori apa saja', 'isi catatan',
                'ringkas catatan kategori', 'rangkum catatan kategori',
            ]) && ! Str::contains($compact, ['berapa catatan', 'jumlah catatan']);
            if ($asksForNoteItems) {
                $intents[] = 'learning';
            }

            return $this->result('workspace', array_values(array_unique($intents)), 'workspace-or-category');
        }

        $explicitLearning = Str::contains($compact, [
            'catatan saya', 'catatan saya yang', 'catatan belajar saya',
            'rangkum catatan', 'coba rangkum catatan', 'ringkas catatan',
            'materi saya', 'materi yang saya pelajari', 'materi yang dipelajari',
            'apa yang saya pelajari', 'yang saya pelajari tentang',
            'materi yang saya simpan', 'apa yang saya simpan', 'yang pernah saya catat',
            'apa yang saya catat', 'catatan yang saya buat',
            'jelaskan materi', 'lampiran saya', 'dokumen saya',
            'buat soal latihan', 'soal latihan dari', 'materi ini',
            'my notes', 'my materials', 'materials i saved', 'summarize my notes',
            'explain my notes', 'saved notes', 'saved material', 'summarize the note',
            'explain the note', 'compare my notes', 'make flashcards from my notes',
            'create a quiz from my notes', 'favorite media', 'favorite videos', 'saved media',
            'videos i saved', 'media favorit', 'video favorit', 'catatan kategori',
            'notes in category', 'notes under category', 'what did i learn',
            'what did i save', 'what did i write down', 'my study materials',
            'in my notes', 'from my notes',
        ]) || (Str::contains($compact, ['perulangan', 'algoritma'])
            && preg_match('/^(apa itu|definisi|what is|what s|define)\b/iu', $compact) !== 1);
        if ($explicitLearning) {
            return $this->result('learning', ['learning'], 'learning');
        }

        if (preg_match('/^bagaimana\s+(progres|progress)\s+saya[?!.]*$/iu', $compact) === 1
            || $compact === 'bagaimana progres saya') {
            return $this->result('ambiguous', [], 'ambiguous');
        }

        $explicitProductivity = Str::contains($compact, [
            'tugas saya', 'task saya', 'belum selesai',
            'minggu ini',
            'goal saya', 'goal gw', 'target saya', 'progres saya',
            'progres goal', 'progress saya', 'deadline', 'sisa target',
            'kemajuan target', 'perkembangan target', 'pencapaian goal',
            'apa yang harus saya prioritaskan', 'prioritas tugas',
            'nama tugas', 'nama task', 'deskripsi tugas', 'deskripsi task',
            'status tugas saya', 'status task saya',
            'my tasks', 'my task', 'unfinished tasks', 'incomplete tasks',
            'my goals', 'my goal', 'my targets', 'my progress',
            'how many tasks', 'how many goals', 'task status',
            'tasks are unfinished', 'tasks not done',
            'pending tasks', 'completed tasks', 'list my goals', 'number of tasks',
        ]);
        if ($explicitProductivity) {
            return $this->result('productivity', ['productivity'], 'productivity');
        }

        $followUp = preg_match('/^(jelaskan lebih detail|jelaskan lebih lanjut|berikan contoh|beri contoh|bagaimana caranya|bagaimana cara melakukannya|buat contohnya|lanjutkan|jelaskan bagian|jelaskan lagi|explain more|give an example|continue|elaborate|what about that|apa maksudnya|maksudnya|kenapa begitu|mengapa begitu|kok begitu|how so|then what)\b/iu', $compact) === 1
            || preg_match('/^(kenapa|mengapa|kok|why|lalu|terus)(?:\s+(begitu|so|then))?[?!.]*$/iu', $compact) === 1
            || preg_match('/^(jelaskan|jelaskan|rangkum|ringkas|buat soal|contoh)\b/iu', $compact) === 1
                && ! Str::contains($compact, [
                    'catatan saya', 'catatan belajar', 'materi saya', 'lampiran saya',
                    'tugas saya', 'goal saya', 'target saya',
                    'perulangan', 'algoritma', 'materi yang saya pelajari',
                    'jelaskan konsep',
                ]);
        if ($followUp) {
            $topicIntent = $this->inferTopicIntent($history."\n".$summary);
            if ($topicIntent !== null) {
                return $this->result('follow_up', [$topicIntent], 'follow-up');
            }

            return $this->result('ambiguous', [], 'follow-up-clarification');
        }

        if (preg_match('/^(apa itu|apa perbedaan|beda|jelaskan apa|jelaskan konsep|bagaimana cara belajar|cara belajar|apa arti|siapa|kapan|mengapa|definisi|what is|what are|what s|explain|how does|how do|how can|why|who|when|define|compare)\b/iu', $compact) === 1
            || Str::contains($compact, ['php dan javascript', 'php sama javascript', 'php vs javascript'])) {
            return $this->result('general', [], 'general-knowledge');
        }

        if (Str::contains($compact, [
            'bagaimana progres saya',
            'apa yang harus saya lakukan',
            'apa yang harus dilakukan',
        ]) || $compact === '') {
            return $this->result('ambiguous', [], 'ambiguous');
        }

        $website = Str::contains($compact, [
            'dashboard', 'task manager', 'apa saja status task', 'status task',
            'status tugas', 'status tugas ada apa', 'tugas ada apa',
            'cara membuat task', 'cara membuat tugas', 'cara membuat goal',
            'bagaimana cara buat task', 'bagaimana cara buat tugas',
            'cara menggunakan goal', 'goal tracker', 'catatan belajar', 'pengaturan',
            'registrasi', 'daftar akun', 'login', 'masuk', 'logout',
            'catatin ai', 'validasi',
            'aturan bisnis', 'fitur yang tidak tersedia', 'fitur tersedia',
            'apa saja fitur', 'fitur catatin', 'data yang bisa kamu akses',
            'data yang dapat kamu akses', 'troubleshooting',
            'gimana cara buat task', 'gimana cara buat tugas',
            'fitur dashboard buat apa', 'fungsi dashboard',
            'how do i create', 'how to create', 'how do i use', 'how to use',
        ]);
        if ($website) {
            return $this->result('website', ['website'], 'website');
        }

        if (preg_match('/^(apa itu|apa perbedaan|beda|jelaskan apa|jelaskan konsep|bagaimana cara belajar|cara belajar|apa arti|siapa|kapan|mengapa|definisi)\b/iu', $compact) === 1
            || Str::contains($compact, ['php dan javascript', 'php sama javascript', 'php vs javascript'])) {
            return $this->result('general', [], 'general-knowledge');
        }

        if (preg_match('/^(bagaimana progres saya|bagaimana progres saya\?|apa yang harus saya lakukan|apa yang harus dilakukan|bagaimana progress saya)\b/iu', $compact) === 1
            || $compact === '') {
            return $this->result('ambiguous', [], 'ambiguous');
        }

        if (Str::contains($compact, [
            'saham saat ini', 'harga saham', 'kurs saat ini', 'nilai tukar saat ini',
            'cuaca hari ini', 'berita terbaru', 'latest news', 'current stock price',
            'current weather', 'exchange rate today',
        ]) || (
            Str::contains($compact, ['cuaca', 'prakiraan cuaca', 'suhu', 'weather', 'temperature', 'harga', 'price', 'kurs', 'nilai tukar', 'saham', 'stock', 'berita', 'news'])
            && Str::contains($compact, ['saat ini', 'sekarang', 'hari ini', 'terkini', 'real time', 'real-time', 'today', 'right now', 'currently'])
        )) {
            return $this->result('unsupported', [], 'live-data-unavailable');
        }

        if (
            Str::contains($compact, ['catatin']) && Str::contains($compact, ['fitur', 'pembayaran', 'berlangganan'])
        ) {
            return $this->result('unsupported', [], 'undocumented-website-feature');
        }

        return $this->result('general', [], 'general-knowledge');
    }

    private function normalizeAliases(string $text): string
    {
        $replacements = [
            '/\bgmna\b/u' => 'bagaimana',
            '/\bgmana\b/u' => 'bagaimana',
            '/\bgimana\b/u' => 'bagaimana',
            '/\bgmn\b/u' => 'bagaimana',
            '/\bgw\b/u' => 'saya',
            '/\bgue\b/u' => 'saya',
            '/\bgua\b/u' => 'saya',
            '/\baja\b/u' => 'saja',
            '/\bprograming\b/u' => 'programming',
            '/\bjavascrip\b/u' => 'javascript',
            '/\bcra\b/u' => 'cara',
            '/\bjs\b/u' => 'javascript',
            '/\btugasnya\b/u' => 'tugas saya',
            '/\bjelasin\b/u' => 'jelaskan',
            '/\bbljr\b/u' => 'belajar',
            '/\bmakasi\b/u' => 'makasih',
            '/\bknp\b/u' => 'kenapa',
            '/\bklo\b/u' => 'kalau',
            '/\bkalo\b/u' => 'kalau',
            '/\btrs\b/u' => 'terus',
            '/\budh\b/u' => 'sudah',
            '/\bnggak\b/u' => 'tidak',
            '/\benggak\b/u' => 'tidak',
            '/\bgak\b/u' => 'tidak',
            '/\bga\b/u' => 'tidak',
            '/\btdk\b/u' => 'tidak',
            '/\byg\b/u' => 'yang',
            '/\bdgn\b/u' => 'dengan',
            '/\butk\b/u' => 'untuk',
            '/\bbgt\b/u' => 'banget',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    }

    private function inferTopicIntent(string $context): ?string
    {
        $normalized = mb_strtolower($context);
        if (Str::contains($normalized, ['catatan', 'materi', 'lampiran', 'pdf', 'notes', 'materials', 'attachment', 'media favorit', 'favorite media', 'favorite video'])) {
            return 'learning';
        }
        if (Str::contains($normalized, ['dashboard', 'task manager', 'goal tracker', 'pengaturan', 'registrasi', 'login', 'catatin ai'])) {
            return 'website';
        }
        if (Str::contains($normalized, ['cara membuat task', 'cara membuat tugas', 'bagaimana cara'])) {
            return 'website';
        }
        if (Str::contains($normalized, ['tugas', 'task', 'goal', 'target', 'deadline', 'progres', 'progress'])) {
            return 'productivity';
        }

        return trim($context) !== '' ? 'general' : null;
    }

    private function result(string $primaryIntent, array $intents, string $reason): array
    {
        return [
            'primary_intent' => $primaryIntent,
            'intents' => $intents,
            'reason' => $reason,
        ];
    }
}
