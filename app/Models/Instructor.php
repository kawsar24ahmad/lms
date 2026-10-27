<?php

namespace App\Models;

use Database\Factories\InstructorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instructor extends Model
{
    /** @use HasFactory<InstructorFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'designation',
        'qualification',
        'profile_image',
        'bio',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
