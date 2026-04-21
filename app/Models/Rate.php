<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['currency_id', 'date', 'value', 'vunit_rate'])]
class Rate extends Model
{
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
