<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamps = false;


    public function portfolios()
    {
        return $this->belongsToMany(Portfolio::class, 'category_portfolios');
    }
}
