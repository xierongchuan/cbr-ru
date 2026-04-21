<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['char_code', 'name', 'nominal', 'cbr_id'])]
class Currency extends Model
{
    protected $casts = [
        'char_code' => 'string',
        'name' => 'string',
        'nominal' => 'integer',
        'cbr_id' => 'string',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
