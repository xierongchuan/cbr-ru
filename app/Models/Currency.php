<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['char_code', 'name', 'nominal'])]
class Currency extends Model
{
    public function rates()
    {
        return $this->hasMany(Rate::class);
    }
}
