<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'code');
    }
}
