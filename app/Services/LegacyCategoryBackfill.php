<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LegacyCategoryBackfill
{
    public function run(string $notesTable = 'catatan', string $categoriesTable = 'categories'): void
    {
        $existingNames = DB::table($notesTable)->distinct()->pluck('kategori')->all();
        foreach ($existingNames as $existingName) {
            $name = trim((string) $existingName);
            if ($name === '') {
                continue;
            }

            $categoryId = DB::table($categoriesTable)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->value('id');

            if ($categoryId === null) {
                DB::table($categoriesTable)->insertOrIgnore([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $categoryId = DB::table($categoriesTable)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->value('id');
            }
        }

        $categoryIds = DB::table($categoriesTable)->get(['id', 'name'])
            ->mapWithKeys(fn ($category) => [mb_strtolower($category->name) => $category->id]);
        DB::table($notesTable)->orderBy('id')->chunkById(500, function ($notes) use ($notesTable, $categoryIds) {
            foreach ($notes as $note) {
                $categoryId = $categoryIds[mb_strtolower(trim($note->kategori))] ?? null;
                if ($categoryId !== null) {
                    DB::table($notesTable)->where('id', $note->id)->update(['category_id' => $categoryId]);
                }
            }
        });
    }
}