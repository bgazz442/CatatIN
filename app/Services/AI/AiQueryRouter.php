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

        if (Str::contains($compact, ['apa saja fitur', 'fitur yang tersedia', 'fitur aktif', 'fitur tersedia'])) {
            return $this->result('feature_list', [], 'active-feature-list');
        }

        if (Str::contains($compact, ['data yang bisa kamu akses', 'data yang dapat kamu akses', 'data apa yang bisa kamu akses'])) {
            return $this->result('data_access', [], 'data-access');
        }

        if (preg_match('/^(hai|halo|hi|hello)(?:[,.]?\s+(bro|gan|kak|teman|guys?|semuanya))?[!.?\s]*$/iu', $compact) === 1
            || preg_match('/^selamat (pagi|siang|sore|malam)(?:\s+(semuanya|kawan|teman))?[!.?\s]*$/iu', $compact) === 1
            || preg_match('/^(apa kabar|gimana kabar kamu)[!?.\s]*$/iu', $compact) === 1) {
            return $this->result('greeting', ['greeting'], 'greeting');
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
            'jelaskan materi', 'lampiran saya', 'dokumen saya',
            'buat soal latihan', 'soal latihan dari', 'materi ini',
            'perulangan', 'algoritma',
        ]);
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
            'nama tugas', 'nama task', 'deskripsi tugas', 'deskripsi task',
            'status tugas saya', 'status task saya',
        ]);
        if ($explicitProductivity) {
            return $this->result('productivity', ['productivity'], 'productivity');
        }

        if (preg_match('/^(apa itu|apa perbedaan|beda|jelaskan apa|jelaskan konsep|bagaimana cara belajar|cara belajar|apa arti|siapa|kapan|mengapa|definisi)\b/iu', $compact) === 1
            || Str::contains($compact, ['php dan javascript', 'php sama javascript', 'php vs javascript'])) {
            return $this->result('general', [], 'general-knowledge');
        }

        $followUp = preg_match('/^(jelaskan lebih detail|jelaskan lebih lanjut|berikan contoh|beri contoh|bagaimana caranya|bagaimana cara melakukannya|buat contohnya|lanjutkan|jelaskan bagian|jelaskan lagi)\b/iu', $compact) === 1
            || preg_match('/^(jelaskan|jelaskan|rangkum|ringkas|buat soal|contoh)\b/iu', $compact) === 1
                && ! Str::contains($compact, [
                    'catatan saya', 'catatan belajar', 'materi saya', 'lampiran saya',
                    'tugas saya', 'goal saya', 'target saya',
                    'perulangan', 'algoritma', 'materi yang saya pelajari',
                ]);
        if ($followUp) {
            $topicIntent = $this->inferTopicIntent($history."\n".$summary);
            if ($topicIntent !== null) {
                return $this->result('follow_up', [$topicIntent], 'follow-up');
            }

            return $this->result('ambiguous', [], 'follow-up-clarification');
        }

        if (Str::contains($compact, [
            'bagaimana progres saya',
            'apa yang harus saya lakukan',
            'apa yang harus dilakukan',
        ]) || mb_strlen($compact) < 4) {
            return $this->result('ambiguous', [], 'ambiguous');
        }

        $website = Str::contains($compact, [
            'dashboard', 'task manager', 'apa saja status task', 'status task',
            'status tugas', 'status tugas ada apa', 'tugas ada apa',
            'cara membuat task', 'cara membuat tugas', 'cara membuat goal',
            'bagaimana cara buat task', 'bagaimana cara buat tugas',
            'cara menggunakan goal', 'goal tracker', 'catatan belajar', 'pengaturan',
            'registrasi', 'daftar akun', 'login', 'masuk', 'logout',
            'catatin ai', 'bagaimana cara', 'cara menggunakan', 'validasi',
            'aturan bisnis', 'fitur yang tidak tersedia', 'fitur tersedia',
            'apa saja fitur', 'fitur catatin', 'data yang bisa kamu akses',
            'data yang dapat kamu akses', 'troubleshooting',
            'gimana cara buat task', 'gimana cara buat tugas',
            'fitur dashboard buat apa', 'fungsi dashboard',
        ]);
        if ($website) {
            return $this->result('website', ['website'], 'website');
        }

        if (preg_match('/^(apa itu|apa perbedaan|beda|jelaskan apa|jelaskan konsep|bagaimana cara belajar|cara belajar|apa arti|siapa|kapan|mengapa|definisi)\b/iu', $compact) === 1
            || Str::contains($compact, ['php dan javascript', 'php sama javascript', 'php vs javascript'])) {
            return $this->result('general', [], 'general-knowledge');
        }

        if (preg_match('/^(bagaimana progres saya|bagaimana progres saya\?|apa yang harus saya lakukan|apa yang harus dilakukan|bagaimana progress saya)\b/iu', $compact) === 1
            || mb_strlen($compact) < 4) {
            return $this->result('ambiguous', [], 'ambiguous');
        }

        return $this->result('unsupported', [], 'unsupported');
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
            '/\bjs\b/u' => 'javascript',
            '/\btugasnya\b/u' => 'tugas saya',
            '/\bjelasin\b/u' => 'jelaskan',
            '/\bbljr\b/u' => 'belajar',
            '/\bmakasi\b/u' => 'makasih',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        return preg_replace('/\s+/u', ' ', trim($text)) ?? trim($text);
    }

    private function inferTopicIntent(string $context): ?string
    {
        $normalized = mb_strtolower($context);
        if (Str::contains($normalized, ['catatan', 'materi', 'lampiran', 'pdf'])) {
            return 'learning';
        }
        if (Str::contains($normalized, ['dashboard', 'task manager', 'goal tracker', 'pengaturan', 'registrasi', 'login', 'catatin ai'])) {
            return 'website';
        }
        if (Str::contains($normalized, ['cara membuat task', 'cara membuat tugas', 'bagaimana cara'])) {
            return 'website';
        }
        if (Str::contains($normalized, ['tugas', 'task', 'goal', 'target', 'deadline', 'progres'])) {
            return 'productivity';
        }

        return null;
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
