<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'post_id'
    ];

    public function user()
    {
        return $this->belongTo(User::class);
    }

    public function post()
    {
        return $this->belongTo(Post::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
