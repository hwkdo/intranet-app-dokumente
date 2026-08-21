<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Database\Factories;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'gueltig_bis' => null,
            'aktiv' => true,
            'requires_acknowledgment' => false,
            'is_onboarding_it' => false,
            'is_onboarding_perso' => false,
            'uploader_id' => null,
            'responsible_id' => null,
            'gvp_id' => null,
            'category_id' => DocumentCategory::query()->value('id') ?? DocumentCategory::factory(),
        ];
    }

    public function requiresAcknowledgment(): static
    {
        return $this->state(fn (): array => ['requires_acknowledgment' => true]);
    }
}
