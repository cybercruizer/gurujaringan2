<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['label', 'url', 'location', 'is_external', 'is_visible', 'position'])]
class MenuItem extends Model
{
    protected function casts(): array
    {
        return [
            'is_external' => 'boolean',
            'is_visible' => 'boolean',
            'position' => 'integer',
        ];
    }
}
