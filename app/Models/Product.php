<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = [
        'name', 'description', 'price',  'stock'
    ];

    public function category()
    {
        return $this->belongsToMany(Category::class);
    }

    public function orderitems(){
        return $this->hasMany(OrderItem::class);
    }
}
