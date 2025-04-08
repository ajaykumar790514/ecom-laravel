<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatProMap extends Model
{
    protected $table = 'cat_pro_maps';

    protected $fillable = [
        'cat_id',
        'pro_id',
    ];

    public $timestamps = false;
    public function category()
    {
        return $this->belongsTo(Category::class, 'cat_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'pro_id');
    }
}
