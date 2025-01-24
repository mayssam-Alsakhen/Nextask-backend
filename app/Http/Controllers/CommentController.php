<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        ]);
        if ($validator->fails()) {
            $respond = [
                'status' => 401,
                'message' => $validator->errors()->first(),
                'data' => null,
            ];
            return response($respond, 401);
        }

        if (Auth::check()) {
            $comment = new Comment;
            $comment->content = $request->content;
            $comment->task_id = $request->task_id;
            $comment->user_id = Auth::user()->id;
            $comment->save();

            $respond = [
                'status' => 200,
                'message' => 'Comment added successfully!',
                'data' => $comment
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
    public function show($id)
    {
        $comment = Comment::where("task_id", $id )->with('user')->get();
        if (isset($comment)) {
            $respond = [
                'status' => 200,
                'message' => "Comment with id $id",
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
        $comment = Comment::find($id);

        if (isset($comment)) {
            $comment->delete();
            $respond = [
                'status' => 200,
                'message' => "Comment with id $id is deleted successfully!",
                'data' => Comment::all()
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
}
