<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Allergen extends Model
{
    protected $fillable = ['name', 'slug', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** The 14 UK regulated allergens, seeded once. */
    public static function seedDefaults(): void
    {
        $names = [
            'Celery', 'Gluten', 'Crustaceans', 'Eggs', 'Fish', 'Lupin', 'Milk',
            'Molluscs', 'Mustard', 'Nuts', 'Peanuts', 'Sesame', 'Soya', 'Sulphites',
        ];
        foreach (array_values($names) as $i => $name) {
            static::firstOrCreate(
                ['slug' => strtolower($name)],
                ['name' => $name, 'sort_order' => $i]
            );
        }
    }
}
