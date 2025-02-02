<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('tasks', 'users')->get();
        $respond =[
            'status' => 200,
            'message' => 'These are all the projects',
            'data' => $projects
        ];
        
        return response($respond, 200);


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
        //
        \Log::info($request->all()); // Log the incoming data
        
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
        ]);

        if ($validator->fails()) {
            return response([
                'status' => 401,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 401);
        }
        if (Auth::check()) {
            $project = new Project;
            $project->name = $request->name;
            $project->description = $request->description;
            $project->save();
            $project->users()->attach(Auth::user()->id, ['is_admin' => true]);
            $respond = [
                'status' => 200,
                'message' => 'Project added successfully!',
                'data' => $project
            ];
            return response($respond, 200);
        }

        $respond = [
            'status' => 403,
            'message' => 'Unauthorized',
            'data' => null
        ];
        return response($respond, 403);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
        $project = Project::with('users', 'tasks')->find($id);
        if (isset($project)) {
            $respond = [
                'status' => 200,
                'message' => "Project with id $id",
                'data' => $project
            ];
            return response($respond, 200);
        }

        $respond = [
            'status' => 401,
            'message' => 'Please enter an existing id'
        ];
        return response($respond, 401);
    }

    // get projects for specific user
    public function getProjectsByUserId($id)
{
    $user = \App\Models\User::find($id);

    if (!$user) {
        return response([
            'status' => 404,
            'message' => 'User not found',
            'data' => null
        ], 404);
    }
    $projects = $user->projects()->with('tasks')->get();

    return response([
        'status' => 200,
        'message' => "Projects for user ID $id",
        'data' => $projects
    ], 200);
}

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);
    
        $project = Project::find($id);
        if (!$project) {
            return response([
                'status' => 404,
                'message' => "This project id $id does not exist",
                'data' => null
            ], 404);
        }

        $isAdmin = $project->users()->where('user_id', Auth::id())->wherePivot('is_admin', true)->exists();

        if (!$isAdmin) {
            return response([
                'status' => 403,
                'message' => 'Unauthorized',
                'data' => null
            ], 403);
        }

        $project->name = $validated['name'];
        $project->description = $validated['description'];
        $project->save();
        return response([
            'status' => 200,
            'message' => "Project with id $id updated successfully!",
            'data' => $project
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $project = Project::find($id);
        $isAdmin = $project->users()->where('user_id', Auth::id())->wherePivot('is_admin', true)->exists();

if (!$isAdmin) {
    return response([
        'status' => 403,
        'message' => 'Unauthorized',
        'data' => null
    ], 403);
}
        if (isset($project)) {
            $project->delete();
            $respond = [
                'status' => 200,
                'message' => "Project with id $id is deleted successfully!",
                'data' => Project::all()
            ];
            return response($respond, 200);
        }
        $respond = [
            'status' => 401,
            'message' => "This project id $id does not exist",
            'data' => null
        ];
        return response($respond, 401);
    }

    // add user
    public function addUserToProject(Request $request, $projectId)
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $project = Project::find($projectId);
    if (!$project) {
        return response()->json(['message' => 'Project not found'], 404);
    }

    $requestingUser = Auth::user();
    if (!$project->users()->where('user_id', $requestingUser->id)->where('is_admin', true)->exists()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    $user = User::where('email', $request->email)->first();
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if ($project->users()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'User already assigned to this project'], 400);
    }

    $project->users()->attach($user->id, ['is_admin' => false]);

    Log::info("User {$user->id} was added to Project {$project->id} by Admin {$requestingUser->id}");

    return response()->json(['message' => 'User added to project successfully'], 200);
}

// remove user
public function removeUserFromProject(Request $request, $projectId, $userId)
{
    $project = Project::findOrFail($projectId);
    $user = User::findOrFail($userId);

    // Check if the user is assigned to the project
    if (!$project->users()->where('user_id', $userId)->exists()) {
        return response([
            'status' => 404,
            'message' => 'User not found in this project.',
            'data' => null
        ], 404);
    }

    // Check if the user being removed is the only admin
    $isOnlyAdmin = $project->users()->wherePivot('is_admin', true)->count() == 1;

    if ($isOnlyAdmin && $project->users()->wherePivot('is_admin', true)->where('user_id', $userId)->exists()) {
        return response([
            'status' => 403,
            'message' => 'Cannot remove this user. They are the only admin of the project.',
            'data' => null
        ], 403);
    }

    // Case 1: Admin removes a user
    if ($project->users()->where('user_id', $request->user()->id)->wherePivot('is_admin', true)->exists()) {
        $project->users()->detach($userId);
        Log::info("User {$user->id} removed from project {$project->id} by admin {$request->user()->id}");

        return response([
            'status' => 200,
            'message' => 'User removed from project successfully.',
            'data' => $project
        ], 200);
    }

    // Case 2: User leaves the project
    if ($request->user()->id === $userId) {
        $project->users()->detach($userId);
        Log::info("User {$user->id} left the project {$project->id}");

        return response([
            'status' => 200,
            'message' => 'You have successfully left the project.',
            'data' => $project
        ], 200);
    }

    // If neither case applies, return unauthorized
    return response([
        'status' => 403,
        'message' => 'You do not have permission to remove this user.',
        'data' => null
    ], 403);
}



// set user as admin
public function setUserAsAdmin(Request $request, $projectId, $userId)
{
    $project = Project::findOrFail($projectId);
    $user = User::findOrFail($userId);

    // Check if user is already admin
    $isAdmin = $project->users()->where('user_id', $userId)->wherePivot('is_admin', true)->exists();
    if ($isAdmin) {
        return response([
            'status' => 400,
            'message' => 'User is already an admin.',
            'data' => null
        ], 400);
    }

    // Check if the current user is an admin
    if (!$project->users()->where('user_id', $request->user()->id)->wherePivot('is_admin', true)->exists()) {
        return response([
            'status' => 403,
            'message' => 'You must be an admin to promote users.',
            'data' => null
        ], 403);
    }

    // Update user to be admin
    $project->users()->updateExistingPivot($userId, ['is_admin' => true]);

    Log::info("User {$user->id} is promoted to admin in project {$project->id} by admin {$request->user()->id}");

    return response([
        'status' => 200,
        'message' => 'User promoted to admin successfully.',
        'data' => $project
    ], 200);
}

// stop user from being admin
public function removeAdminPrivilege(Request $request, $projectId, $userId)
{
    $project = Project::findOrFail($projectId);
    $user = User::findOrFail($userId);

    // Check if the user is an admin of the project
    $userIsAdmin = $project->users()->where('user_id', $userId)->wherePivot('is_admin', true)->exists();

    if (!$userIsAdmin) {
        return response([
            'status' => 404,
            'message' => 'User is not an admin of this project.',
            'data' => null
        ], 404);
    }

    // Case 1: The user trying to remove admin privileges is the project creator (admin)
    $isCreator = $project->users()->where('user_id', $request->user()->id)->wherePivot('is_admin', true)->exists();

    if ($isCreator) {
        // Check if there's more than one admin in the project
        $adminCount = $project->users()->wherePivot('is_admin', true)->count();

        // If the admin is the only one, prevent removing their admin status
        if ($adminCount === 1) {
            return response([
                'status' => 403,
                'message' => 'You cannot remove admin privileges when you are the only admin.',
                'data' => null
            ], 403);
        }

        // Remove admin privileges from the user
        $project->users()->updateExistingPivot($userId, ['is_admin' => false]);

        Log::info("Admin privilege removed from user {$user->id} in project {$project->id} by admin {$request->user()->id}");

        return response([
            'status' => 200,
            'message' => 'Admin privileges removed from the user successfully.',
            'data' => $project
        ], 200);
    }

    // If the user is not the project creator, return an unauthorized response
    return response([
        'status' => 403,
        'message' => 'You do not have permission to remove admin privileges from this user.',
        'data' => null
    ], 403);
}
}
