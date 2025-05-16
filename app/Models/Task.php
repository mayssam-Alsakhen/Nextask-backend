<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['title', 'description', 'category', 'is_important', 'project_id', 'due_date',  'progress'];
    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function users()
{
    return $this->belongsToMany(User::class)->withTimestamps();
}   

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
