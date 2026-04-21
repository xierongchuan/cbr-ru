<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['char_code', 'name', 'nominal'])]
class Currency extends Model
{
    protected $casts = [
        'char_code' => 'string',
        'name' => 'string',
        'nominal' => 'integer'
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(Rate::class);
    }
}
