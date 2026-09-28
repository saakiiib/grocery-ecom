<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Build a unique URL slug from a name/title, suffixing -2, -3… on collision.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function uniqueSlug(string $base, string $modelClass, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'item';
        $candidate = $slug;
        $suffix = 2;
        while ($modelClass::where('slug', $candidate)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$suffix++;
        }

        return $candidate;
    }
}
