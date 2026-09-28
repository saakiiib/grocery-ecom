<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    public const FORMATS = ['PDF Spec', 'CAD Drawing', 'BIM/Revit', 'Installation Manual', 'Care Guide'];

    protected $fillable = [
        'ref', 'product_id', 'title', 'file', 'format', 'size', 'rev', 'downloads_count', 'status', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['downloads_count' => 'integer', 'status' => 'boolean', 'sort_order' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
