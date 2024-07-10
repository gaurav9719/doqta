<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupMember;

class CommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $baseUrl = asset('storage') . '/';
        $dummy_img = asset('assets/img/user/community2.jpg');
         $comments = Comment::where(['post_id'=> $id])
         ->select('comments.id','comments.is_active','users.user_name','users.profile','users.email','comments.comment')
         ->join('users','comments.user_id','=','users.id')
         ->orderByDesc('comments.id')
         ->get();
        return DataTables::of($comments)
            ->addIndexColumn() 
            ->addColumn('profile', function ($comment) use ($baseUrl, $dummy_img) {
                if (!empty($comment->profile)) {
                    return   '<object data="' . $baseUrl . $comment->profile . '" width="50" height="50"></object>';
                } else {
                    return   '<object data="' . $dummy_img . '" width="50" height="50"></object>';
                }
            })
            ->addColumn('status', function ($comment) {
                $statusBadge = $comment->is_active == 1
                    ? '<div class="status_comment' . $comment->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Active</span></div>'
                    : '<div class="status_comment' . $comment->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Inactive</span></div>';
                return $statusBadge;
            })
            ->addColumn('action', function ($comment) {
                $actionButton = $comment->is_active
                    ? '<div class="status_btn_comment' . $comment->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusComment(' . $comment->id . ',' . $comment->is_active . ')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                    : '<div class="status_btn_comment' . $comment->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatusComment(' . $comment->id . ',' . $comment->is_active . ')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                return $actionButton;
            })
            ->rawColumns(['profile','action','status'])
            ->make(true);
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
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function comment_status_change(Request $request){
        $comment_id = $request->comment_id;

        $post = Comment::find($comment_id);

        if ($post->is_active == 1) {
            $post->is_active = 0;
        } else {
            $post->is_active = 1;
        }
        $post->save();
        echo json_encode(Comment::find($comment_id));
    }
}
