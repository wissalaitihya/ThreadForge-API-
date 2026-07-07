<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blueprint extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'tone',
        'max_hashtags',
        'max_words',
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
