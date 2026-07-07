<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Blueprint extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'tone',
        'max_hashtags',
        'max_characters',
        'regle_supp'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function texts()
    {
        return $this->hasMany(Text::class);
    }
}
