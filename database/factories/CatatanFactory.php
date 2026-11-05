<?php

namespace Database\Factories;

use App\Models\Catatan;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Catatan>
 */
class CatatanFactory extends Factory
{
    protected $model = Catatan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => fake()->sentence(4),
            'kategori' => Category::query()->inRandomOrder()->value('name'),
            'isi' => fake()->paragraphs(3, true),
            'favorit' => fake()->boolean(25),
        ];
    }
}
