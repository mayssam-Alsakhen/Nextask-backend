<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Task;
use App\Models\Comment;
use App\Models\Project;

class UserActivityController extends Controller
{
    public function getUserActivities($userId)
    {
        $user = User::findOrFail($userId);

        $activities = [];

        // 1. New task assigned to user or updated task
        foreach ($user->tasks as $task) {
            $activities[] = [
                'type' => 'task_assigned',
                'message' => 'You were assigned a task: ' . $task->title,
                'created_at' => $task->created_at,
            ];

            if ($task->updated_at > $task->created_at) {
                $activities[] = [
                    'type' => 'task_updated',
                    'message' => 'Task updated: ' . $task->title,
                    'created_at' => $task->updated_at,
                ];
            }
        }

        // 2. User added to project
        foreach ($user->projects as $project) {
            $pivotCreatedAt = $project->pivot->created_at;
            $activities[] = [
                'type' => 'added_to_project',
                'message' => 'You were added to project: ' . $project->name,
                'created_at' => $pivotCreatedAt,
            ];

            // 3. Project updated
            if ($project->updated_at > $project->created_at) {
                $activities[] = [
                    'type' => 'project_updated',
                    'message' => 'Project updated: ' . $project->name,
                    'created_at' => $project->updated_at,
                ];
            }

            // 4. Comments in tasks (user is admin or assigned)
            foreach ($project->tasks as $task) {
                $isAssigned = $task->users->contains($user->id);
                $isAdmin = $project->pivot->is_admin ?? false;

                foreach ($task->comments as $comment) {
                    if ($isAssigned || $isAdmin) {
                        $activities[] = [
                            'type' => 'comment_added',
                            'message' => 'New comment on task: ' . $task->title,
                            'created_at' => $comment->created_at,
                        ];
                    }
                }

                // 5. Notify admin when a task is moved to "Test" category
                if ($isAdmin && $task->category === 'Test') {
                    $activities[] = [
                        'type' => 'task_testing',
                        'message' => "Task moved to 'Test' category: " . $task->title,
                        'created_at' => $task->updated_at,
                    ];
                }
            }
        }

        // 6. Sort by latest
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return response()->json([
            'status' => 200,
            'message' => 'User activity feed',
            'data' => $activities,
        ]);
    }
}
