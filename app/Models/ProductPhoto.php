<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'pro_id',
        'image',
        'thumbnail',
        'active',
        'seq',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class, 'pro_id');
    }
}
