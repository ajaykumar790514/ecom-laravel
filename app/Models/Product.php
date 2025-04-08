<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Category;
use App\Models\ProductPhoto;

class Product extends Model
{
    protected $fillable = [
        'title', 'code', 'sku', 'tax', 'slug', 'description', 'seq', 'active'
    ];

    public function photos()
    {
        return $this->hasMany(ProductPhoto::class, 'pro_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'cat_pro_maps', 'pro_id', 'cat_id');
    }
}

