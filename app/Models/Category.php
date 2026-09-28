<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'image', 'parent_id', 'status', 'sort_order',
        'meta_title', 'meta_description', 'meta_keywords', 'meta_image', 'video_url',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    // Parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Subcategories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // Recursive children loading
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** Option-group template inherited by products in this category. */
    public function optionGroups(): BelongsToMany
    {
        return $this->belongsToMany(OptionGroup::class, 'category_option_group')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
