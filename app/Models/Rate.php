<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['currency_id', 'date', 'value', 'vunit_rate'])]
class Rate extends Model
{
    protected $casts = [
        'date' => 'date',
        'value' => 'decimal:4',
        'vunit_rate' => 'decimal:10'
    ];

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }
}
