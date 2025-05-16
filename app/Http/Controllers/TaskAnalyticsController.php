<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskAnalyticsController extends Controller
{
    /**
     * Return number of completed tasks grouped by full date (e.g., 2025-01-13)
     */
    public function completedByMonth(Request $request)
    {
        $user = auth()->user();

        $completedTasks = DB::table('task_user')
            ->join('tasks', 'tasks.id', '=', 'task_user.task_id')
            ->whereNotNull('tasks.completed_at')
            ->where('task_user.user_id', $user->id)
            ->selectRaw('DATE(tasks.completed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($completedTasks);
    }
}
