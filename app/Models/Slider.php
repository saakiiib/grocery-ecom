<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = [
        'badge',
        'title',
        'subtitle',
        'btn_text',
        'btn_url',
        'btn_text2',
        'btn_url2',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
