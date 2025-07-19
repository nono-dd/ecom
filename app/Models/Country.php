<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    public function addresses()
    {
        return $this->hasMany(Address::class, 'country', 'code');
    }
}
