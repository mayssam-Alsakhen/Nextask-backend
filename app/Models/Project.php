<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = ['name', 'description', 'status', 'progress', 'created_by', 'updated_by', 'due_date'];

    public function users()
{
    return $this->belongsToMany(User::class)->withPivot('is_admin')->withTimestamps();
}

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}

public function updateProgressFromTasks()
{
    $tasks = $this->tasks;

    if ($tasks->count() === 0) {
        return 0;
    }

    $totalProgress = $tasks->sum('progress');
    return round($totalProgress / $tasks->count());
}

}
