<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Task;

class DashboardController extends Controller
{
    public function summary(Request $request)
    {
        $user = auth()->user();

        // Define date ranges
        $startOfToday = now()->startOfDay();
        $endOfNextWeek = now()->addDays(7)->endOfDay();

        // === 1. Upcoming tasks (next 7 days, not completed)
        $upcomingTasks = $user->tasks()
            ->whereBetween('due_date', [$startOfToday, $endOfNextWeek])
            ->where('category', '!=', 'Completed');

        $importantTasksNextWeek = (clone $upcomingTasks)->where('is_important', true)->count();
        $normalTasksNextWeek = (clone $upcomingTasks)->where('is_important', false)->count();

        // === 2. Due today or overdue (not completed)
        $dueOrOverdueTasks = $user->tasks()
            ->where('due_date', '<=', now())
            ->where('category', '!=', 'Completed');

        $importantDueTodayOrOverdue = (clone $dueOrOverdueTasks)->where('is_important', true)->count();
        $normalDueTodayOrOverdue = (clone $dueOrOverdueTasks)->where('is_important', false)->count();

        // === 3. Unassigned tasks (admin projects only)
        $adminProjectIds = $user->projects()
            ->wherePivot('is_admin', true)
            ->pluck('projects.id');

        $unassignedTasks = Task::whereIn('project_id', $adminProjectIds)
            ->whereDoesntHave('users')
            ->where('category', '!=', 'Completed');

        $unassignedImportantTasks = (clone $unassignedTasks)->where('is_important', true)->count();
        $unassignedNormalTasks = (clone $unassignedTasks)->where('is_important', false)->count();

        // Return structured data
        return response()->json([
            'upcoming_tasks' => [
                'important' => $importantTasksNextWeek,
                'normal' => $normalTasksNextWeek,
                'total' => $importantTasksNextWeek + $normalTasksNextWeek,
            ],
            'due_or_overdue' => [
                'important' => $importantDueTodayOrOverdue,
                'normal' => $normalDueTodayOrOverdue,
                'total' => $importantDueTodayOrOverdue + $normalDueTodayOrOverdue,
            ],
            'unassigned_tasks' => [
                'important' => $unassignedImportantTasks,
                'normal' => $unassignedNormalTasks,
                'total' => $unassignedImportantTasks + $unassignedNormalTasks,
            ],
        ]);
    }
  
    public function filteredTasks(Request $request)
    {
        $user = Auth::user(); 
        $filter = $request->input('filter');
    
        $tasks = Task::query();
        $now = now();
        $weekAhead = now()->addDays(7);
    
        if ($filter === 'due_or_overdue') {
            $tasks->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->where('due_date', '<=', $now);
    
        } elseif ($filter === 'upcoming_tasks') {
            $tasks->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })->whereBetween('due_date', [$now, $weekAhead]);
    
        } elseif ($filter === 'unassigned_tasks') {
            // Get the admin project IDs directly within this function
            $adminProjectIds = Project::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id)
                      ->where('project_user.is_admin', true); // assuming `is_admin` is a column in the pivot table
            })->pluck('id'); // Get the project IDs where the user is an admin
    
            // Fetch tasks that are unassigned and belong to projects the user is an admin for
            $tasks->whereDoesntHave('users')
                  ->whereIn('project_id', $adminProjectIds);
        }
    
        return response()->json($tasks->get());
    }
    
}
