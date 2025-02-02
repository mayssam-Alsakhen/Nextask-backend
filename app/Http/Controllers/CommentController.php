<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comments = Comment::with('user', 'task')->get();
        $respond = [
            'status' => 200,
            'message' => 'These are all the comments',
            'data' => $comments
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
    $validator = Validator::make($request->all(), [
        'content' => 'required',
        'task_id' => 'required|exists:tasks,id',
    ]);

    if ($validator->fails()) {
        return response([
            'status' => 400,
            'message' => $validator->errors()->first(),
            'data' => null,
        ], 400);
    }

    $user = Auth::user();
    $task = Task::findOrFail($request->task_id);
    $project = $task->project; // Get the project of the task

    // Check if the user is assigned to this project
    if (!$project->users()->where('user_id', $user->id)->exists()) {
        return response([
            'status' => 403,
            'message' => 'You are not assigned to this project, so you cannot add comments.',
            'data' => null,
        ], 403);
    }

    // Create the comment
    $comment = Comment::create([
        'content' => $request->content,
        'task_id' => $request->task_id,
        'user_id' => $user->id,
    ]);

    return response([
        'status' => 200,
        'message' => 'Comment added successfully!',
        'data' => $comment,
    ], 200);
}

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $comment = Comment::where("task_id", $id )->with('user')->get();
        if (isset($comment)) {
            $respond = [
                'status' => 200,
                'message' => "Comment with task id $id",
                'data' => $comment
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
        $comment = Comment::find($id);

        if (isset($comment)) {
            $comment->content = $request->content;
            $comment->task_id = $request->task_id;
            $comment->user_id = $request->user_id;
            $comment->save();

            $respond = [
                'status' => 200,
                'message' => "Comment with id $id updated successfully!",
                'data' => $comment
            ];
            return response($respond, 200);
        }
        $respond = [
            'status' => 401,
            'message' => "This comment id $id does not exist",
            'data' => null
        ];
        return response($respond, 401);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $comment = Comment::find($id);
    
        if (!$comment) {
            return response([
                'status' => 404,
                'message' => "Comment not found",
                'data' => null,
            ], 404);
        }
    
        $task = $comment->task;
        $project = $task->project;
    
        // Check if the user is the comment owner or an admin of the project
        $isAdmin = $project->users()->where('user_id', $user->id)->wherePivot('is_admin', true)->exists();
        if ($comment->user_id !== $user->id && !$isAdmin) {
            return response([
                'status' => 403,
                'message' => 'You do not have permission to delete this comment.',
                'data' => null,
            ], 403);
        }
    
        $comment->delete();
    
        return response([
            'status' => 200,
            'message' => "Comment deleted successfully!",
            'data' => null,
        ], 200);
    }    
}
