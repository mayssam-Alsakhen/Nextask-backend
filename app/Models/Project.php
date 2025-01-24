<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'user_id'];

    public function users()
{
    return $this->belongsToMany(User::class)->withPivot('is_admin')->withTimestamps();
}

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
