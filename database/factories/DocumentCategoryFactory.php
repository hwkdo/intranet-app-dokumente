<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Database\Factories;

use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentCategory>
 */
class DocumentCategoryFactory extends Factory
{
    protected $model = DocumentCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
