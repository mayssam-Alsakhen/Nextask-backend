<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::with('tasks', 'user')->get();
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
            $project->user_id = Auth::user()->id;
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
        $project = Project::with('user', 'task', 'comment')->find($id);
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
        $project = Project::find($id);
        if (isset($project)) {
            $project->name = $request->name;
            $project->description = $request->description;
            $project->user_id = $request->user_id;
            $project->save();
            $respond = [
                'status' => 200,
                'message' => "Project with id $id updated successfully!",
                'data' => $project
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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
        $project = Project::find($id);

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
}
