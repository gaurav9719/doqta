<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\DataTables;
use App\Models\Post;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $auth = Auth::user();
        return view('admin.posts.posts', compact('auth'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function posts_data(Request $request)
    {
        $baseUrl = asset('storage') . '/';
        $dummy_img = asset('assets/img/user/community2.jpg');
        $posts = Post::with('post_user')->select(['id', 'user_id', 'title', 'content', 'created_at', 'media_url', 'link', 'summarize'])
        ->orderByDesc('id')
            ->get();
        return DataTables::of($posts)
            ->addIndexColumn() // This will add DT_RowIndex
            ->editColumn('created_at', function ($post) {
                if (!empty($post->created_at)) {
                    return $post->created_at->format('d-m-Y'); // Format the date as desired
                } else {
                    return 'Not Available';
                }
            })
            ->addColumn('media_url', function ($post) use ($baseUrl, $dummy_img) {
                if (!empty($post->media_url)) {
                    return   '<object data="' . $baseUrl . $post->media_url . '" width="50" height="50"></object>';
                } else {
                    return   '<object data="' . $dummy_img . '" width="50" height="50"></object>';
                }
            })
            ->addColumn('post_user', function ($post) {
                if (!empty($post->post_user)) {
                    return  $post->post_user->user_name;
                } else {
                    return   'Not Available';
                }
            })
            ->addColumn('id', function ($post) {
                return '<button class="btn btn-lg btn-icon" onclick="showPost(' . $post->id . ')" title="Make Active"><i class="fa-solid fa-eye"></i></button>';
            })
            ->rawColumns(['media_url', 'id'])
            ->make(true);
    }

    public function posts_data_user(Request $request)
    {

        $group_id = $request->group_id;

        $user_id = $request->user_id;

        $baseUrl = asset('storage') . '/';

        $dummy_img = asset('assets/img/user/community2.jpg');

        $posts = Post::with('post_user')->select(['id', 'user_id', 'comment_count' , 'is_active', 'title', 'content', 'created_at', 'media_url', 'link', 'summarize'])
            ->where(['user_id' => $user_id, 'group_id' => $group_id])
            ->orderByDesc('id')
            ->get();
        return DataTables::of($posts)
            ->addIndexColumn() // This will add DT_RowIndex
            ->editColumn('created_at', function ($post) {
                if (!empty($post->created_at)) {
                    return $post->created_at->format('d-m-Y'); // Format the date as desired
                } else {
                    return 'Not Available';
                }
            })
            ->addColumn('media_url', function ($post) use ($baseUrl, $dummy_img) {
                if (!empty($post->media_url)) {
                    return   '<object data="' . $baseUrl . $post->media_url . '" width="50" height="50"></object>';
                } else {
                    return   '<object data="' . $dummy_img . '" width="50" height="50"></object>';
                }
            })
            ->addColumn('post_user', function ($post) {
                if (!empty($post->post_user)) {
                    return  $post->post_user->user_name;
                } else {
                    return   'Not Available';
                }
            })
            ->addColumn('id', function ($post) {
                return '<button class="btn btn-lg btn-icon" onclick="showPost(' . $post->id . ')" title="Make Active"><i class="fa-solid fa-eye"></i></button>';
            })
            ->addColumn('status', function ($post) {
                $statusBadge = $post->is_active == 1
                    ? '<div class="status_' . $post->id . '"><span class="badge badge-success" style="color:white;background-color:green;">Active</span></div>'
                    : '<div class="status_' . $post->id . '"><span class="badge badge-danger" style="color:white;background-color:red;">Inactive</span></div>';
                return $statusBadge;
            })
            ->addColumn('comment', function ($post) {
                return '<button class="btn btn-lg btn-icon" onclick="showComment(' . $post->id . ')"  title="Make Active"><i class="fa-solid fa-comment"></i></button>';
            })
            ->addColumn('action', function ($post) {
                $actionButton = $post->is_active
                    ? '<div class="status_btn' . $post->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $post->id . ',' . $post->is_active . ')" title="Make Inactive"><i class="material-icons">block</i></button></div>'
                    : '<div class="status_btn' . $post->id . '"><button class="btn btn-lg btn-icon" onclick="changeStatus(' . $post->id . ',' . $post->is_active . ')" title="Make Active"><i class="material-icons">check_circle</i></button></div>';
                return $actionButton;
            })
            ->rawColumns(['media_url', 'id', 'status', 'action','comment'])
            ->make(true);
    }
    //=== posts_status_change ===//
    public function posts_status_change(Request $request)
    {
        $post_id = $request->post_id;

        $post = Post::find($post_id);

        if ($post->is_active == 1) {
            $post->is_active = 0;
        } else {
            $post->is_active = 1;
        }
        $post->save();
        echo json_encode(Post::find($post_id));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $post = Post::find($id);

        echo json_encode($post);
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
}
