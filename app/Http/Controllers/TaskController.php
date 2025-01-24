<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    
     public function assignUser(Request $request, $taskId)
     {
         // Validate the request
         $request->validate([
             'user_id' => 'required|exists:users,id',
         ]);
 
         // Find the task
         $task = Task::findOrFail($taskId);
 
         // Attach the user to the task
         $task->users()->attach($request->user_id);
 
         // Return a success response
         return response()->json([
             'message' => 'User assigned to task successfully',
         ], 200);
     }


    public function index()
    {
        $tasks = Task::with('user', 'project', 'comment')->get();
        $respond = [
            'status' => 200,
            'message' => 'These are all the tasks',
            'data' => $tasks
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
        if(Auth::check()){
            $vlaidator= Validator::make($request->all(),[
                'title' => 'required',
                'description' => 'required',
                'category' => 'required',
                'is_important' => 'nullable',
                'project_id' => 'required',
            ]);

            if($vlaidator->fails()){
                $response=[
                    'status' => 401,
                    'message' => $validator->errors()->first(),
                    'data' => null,
                ];
                return response($response, 401);
            }
            $task = new Task;
            $task->title = $request->title;
            $task->description = $request->description;
            $task->category = $request->category ?? 'pending';
            $task->is_important = $request->is_important ?? false;
            $task->project_id = $request->project_id;
            $task->user_id = Auth::user()->id;
            $task->save();
            $response = [
                'status' => 200,
                'message' => 'Task added successfully!',
                'data' => $task,
            ];
            return response($response, 200);
        }
        $response = [
            'status' => 403,
            'message' => 'Unauthorized',
            'data' => null,
        ];
        return response($response, 403);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $task = Task::with('user', 'project', 'comment')->find($id);
        if (isset($task)) {
            $respond = [
                'status' => 200,
                'message' => "Task with id $id",
                'data' => $task
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
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        $task = Task::find($id);
        if(isset($task)){
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'description' => 'required',
                'category' => 'required',
                'is_important' => 'nullable',
            ]);

            if ($validator->fails()) {
                $response = [
                    'status' => 401,
                    'message' => $validator->errors()->first(),
                    'data' => null,
                ];
                return response($response, 401);
            }

            $task->title = $request->title;
            $task->description = $request->description;
            $task->category = $request->category ?? 'pending';
            $task->is_important = $request->is_important ?? false;
            $task->save();

            $response = [
                'status' => 200,
                'message' => "Task with id $id updated successfully!",
                'data' => $task,
            ];
            return response($response, 200);
        }
        $response = [
            'status' => 401,
            'message' => 'This task id does not exist',
            'data' => null,
        ];
        return response($response, 401);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $task = Task::find($id);
        if(isset($task)){
            $task->delete();
            $response = [
                'status' => 200,
                'message' => "Task with id $id is deleted successfully!",
                'data' => Task::all(),
            ];
            return response($response, 200);
        }
        $response = [
            'status' => 401,
            'message' => 'This task id does not exist',
            'data' => null,
        ];
        return response($response, 401);
    }
}
