<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Task;
use App\Models\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     public function index(Request $request)
{
    try {
        $user = Auth::user();

        // Check if a project_id is provided in the query parameters
        $projectId = $request->query('project_id');

        if ($projectId) {
            // Fetch tasks for a specific project where the logged-in user is part of the project
            $tasks = Task::whereHas('project.users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->where('project_id', $projectId)
            ->with('users', 'project')
            ->get();
        } else {
            // Fetch all tasks assigned to the logged-in user
            $tasks = Task::whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->with('users', 'project')
            ->get();
        }

        return response()->json([
            'success' => true,
            'tasks' => $tasks
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch tasks.',
            'error' => $e->getMessage()
        ], 500);
    }
}    

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    // Log the incoming request to debug
    Log::info('Incoming Request:', $request->all());

    try {
        // Validate the request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'required|exists:projects,id',
            'due_date' => 'required|date',
            'progress' => 'nullable|integer|min:0|max:100',
            'isImportant' => 'required|boolean',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        // Log validated data for debugging
        Log::info('Validated Data:', $validated);

        // Create the task
        $task = Task::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'project_id' => $validated['project_id'],
            'due_date' => $validated['due_date'],
            'is_important' => $validated['isImportant'],
            'progress' => $validated['progress'] ?? 0,
        ]);

        // Assign multiple users to the task
        if (!empty($validated['assigned_users'])) {
            $task->users()->sync($validated['assigned_users']);
        }

        if ($task->project) {
            $project = $task->project;
            $project->progress = $project->updateProgressFromTasks();
            $project->save();
        }
        // Return a success response
        return response()->json([
            'message' => 'Task created and assigned successfully!',
            'task' => $task,
        ], 201);
    } catch (\Exception $e) {
        // Log any errors for debugging
        Log::error('Error creating task: ' . $e->getMessage());

        // Return a failure response
        return response()->json([
            'message' => 'Failed to create task.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show($id)
{
    // Find the task
    $task = Task::with(['users:id,name,email', 'project:id,name'])->find($id);

    if (!$task) {
        return response()->json(['message' => 'Task not found.'], 404);
    }

    // Get the logged-in user
    $user = Auth::user();

    // Check if the user is part of the project
    $isUserInProject = $task->project->users->contains($user->id);

    if (!$isUserInProject) {
        return response()->json(['message' => 'You are not authorized to view this task.'], 403);
    }

    // Return task details
    return response()->json([
        'task' => $task,
        'assigned_users' => $task->users,
        'project' => $task->project,
    ]);
}


    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    // Validate input
    $validatedData = $request->validate([
        'title' => 'string|max:255|nullable',
        'description' => 'string|nullable',
        'due_date' => 'date|nullable',
        'isImportant' => 'boolean|nullable',
        'category' => 'string|in:Pending,In Progress,Completed,Test|nullable',
        'assigned_users' => 'array|nullable',
        'assigned_users.*' => 'exists:users,id',
    ]);

    // Find the task
    $task = Task::find($id);
    if (!$task) {
        return response()->json(['message' => 'Task not found.'], 404);
    }

    // Get the logged-in user
    $user = Auth::user();

    // Check if the user is assigned to the task or is an admin of the project
    $isAdmin = $task->project->users()
        ->where('user_id', $user->id)
        ->wherePivot('is_admin', true)
        ->exists();
    $isAssignedUser = $task->users()
        ->where('user_id', $user->id)
        ->exists();

    if (!$isAdmin && !$isAssignedUser) {
        return response()->json(['message' => 'You are not authorized to update this task.'], 403);
    }

    // Admin: Full control over task updates
    if ($isAdmin) {
        if ($request->has('title')) {
            $task->title = $validatedData['title'];
        }
        if ($request->has('description')) {
            $task->description = $validatedData['description'];
        }
        if ($request->has('due_date')) {
            $task->due_date = $validatedData['due_date'];
        }
        if ($request->has('isImportant')) {
            $task->is_important = $validatedData['isImportant'];
        }
        if ($request->has('category')) {
            $newCategory = $validatedData['category'];
        
            // If marked as Completed and not already set
            if ($newCategory === 'Completed' && !$task->completed_at) {
                $task->completed_at = now();
            }
        
            // If changed back from Completed to something else
            if ($task->category === 'Completed' && $newCategory !== 'Completed') {
                $task->completed_at = null;
            }
        
            $task->category = $newCategory;
        }
        

        // Handle assigned users
        if ($request->has('assigned_users')) {
            $task->users()->sync($validatedData['assigned_users']);
        }

        $task->save();
        $task->load(['users', 'project.users']);
        return response()->json(['message' => 'Task updated successfully by admin.', 'task' => $task], 200);
    }

    // Assigned User: Only allowed to update category
   // Assigned User: Only allowed to update category
if ($isAssignedUser) {
    if ($request->has('category')) {
        $newCategory = $validatedData['category'];

        // Handle completed_at updates like in admin section
        if ($newCategory === 'Completed' && !$task->completed_at) {
            $task->completed_at = now();
        }
        if ($task->category === 'Completed' && $newCategory !== 'Completed') {
            $task->completed_at = null;
        }

        $task->category = $newCategory;
        $task->save();

        return response()->json(['message' => 'Task updated successfully.', 'task' => $task], 200);
    }

    return response()->json(['message' => 'You are only allowed to update the task category.'], 403);
}
}   

    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
{
    // Find the task
    $task = Task::find($id);
    if (!$task) {
        return response()->json(['message' => 'Task not found.'], 404);
    }

    // Get the logged-in user
    $user = Auth::user();

    // Check if the user is an admin of the project
    $isAdmin = $task->project->users()->where('user_id', $user->id)->wherePivot('is_admin', true)->exists();

    if (!$isAdmin) {
        return response()->json(['message' => 'You are not authorized to delete this task.'], 403);
    }

    // Delete the task and its relations
    $task->users()->detach(); // Remove all assigned users from the task
    $task->delete();
    if ($task->project) {
        $project = $task->project;
        $project->progress = $project->updateProgressFromTasks();
        $project->save();
    }
    return response()->json(['message' => 'Task deleted successfully.'], 200);
}

/**
 * Update the progress of the task.
 */
public function updateProgress(Request $request, $id)
{
    $validatedData = $request->validate([
        'progress' => 'required|integer|min:0|max:100',
    ]);

    $task = Task::find($id);
    if (!$task) {
        return response()->json(['message' => 'Task not found.'], 404);
    }
    $user = Auth::user();
    $isAdmin = $task->project->users()->where('user_id', $user->id)->wherePivot('is_admin', true)->exists();
    $isAssignedUser = $task->users()->where('user_id', $user->id)->exists();

    if (!$isAdmin && !$isAssignedUser) {
        return response()->json(['message' => 'You are not authorized to update this task.'], 403);
    }
    $task->progress = $validatedData['progress'];
    $task->save();
    $project = $task->project;
$project->progress = $project->updateProgressFromTasks();
$project->save();
    return response()->json([
        'message' => 'Task progress updated successfully.',
        'task' => $task,
    ], 200);
}


}
