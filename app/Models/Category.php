<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'title', 'description', 'image', 'slug', 'active', 'parent_id', 'seq',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function directChildren()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
