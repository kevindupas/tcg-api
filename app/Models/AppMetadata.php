<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppMetadata extends Model
{
    protected $fillable = ['version', 'published'];
    protected $casts = [
        'published' => 'boolean'
    ];
}
