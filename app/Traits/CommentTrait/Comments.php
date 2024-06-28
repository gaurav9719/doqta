<?php

namespace App\Traits\CommentTrait;

use Exception;
use App\Models\Post;
use App\Models\Group;
use App\Models\Comment;
use Illuminate\Support\Facades\Log;

trait Comments
{
    public function getComments($request, $authId)
    {
        try {

            $groupId        =   $this->getGroupIdByPostId($request->post_id);

            if ($groupId) {

                $group      =   $this->getGroupWithMemberCount($groupId->group_id);
            }

            $limit          =   $request->input('limit', 10);

            $comments       =   $this->getCommentsWithRelations($request->post_id, $authId, $limit);

            if(isset($comments[0]) && !empty($comments[0])){

                $comments->getCollection()->transform(function ($comment) use ($authId) {
    
                    return $this->transformComment($comment, $authId);
    
                });

            }

            $post           =   $this->getPostWithRelations($request->post_id, $authId);

            $responseData   =   $this->prepareResponseData($comments, $post);

            return response()->json(['status' => 200, 'message' => "comments", 'data' => $responseData, 'post' => $post]);
            
        } catch (Exception $e) {
            
            Log::error('Error caught: "getComments" ' . $e->getMessage());
            
            return $this->sendError($e->getMessage(), [], 500);
        }
    }

    private function getGroupIdByPostId($postId)
    {
        return Post::select('group_id')->find($postId);
    }

    private function getGroupWithMemberCount($groupId)
    {
        return Group::withCount('groupMember')->find($groupId);
    }

    private function getCommentsWithRelations($postId, $authId, $limit)
    {
        return Comment::with([
            'commentUser' => function ($query) use ($authId) {

                $query->select('id', 'name', 'user_name', 'profile');

            },

            'commentUser.user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
            },

            'commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                $q->select('id', 'name');
            },

            'replies.commentUser' => function ($query) use ($authId) {
                
                $query->select('id', 'name', 'user_name', 'profile');
            },

            'replies.commentUser.user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
            },

            'replies.commentUser.user_medical_certificate.medical_certificate' => function ($q) {

                $q->select('id', 'name');
            },

            'replies.replied_to' => function ($query) use ($authId) {

                $query->select('id', 'name', 'user_name', 'profile');
            },

            'replies.replied_to.user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');
            },

            'replies.replied_to.user_medical_certificate.medical_certificate' => function ($q) {

                $q->select('id', 'name');
            },

        ])
            ->withCount(['totalLikes', 'total_comment'])

            ->where('post_id', $postId)

            ->whereNull('parent_id')

            ->whereDoesntHave('commentUser.blockedUsers', function ($query) use ($authId) {

                $query->where('blocked_user_id', $authId);
            })

            ->whereDoesntHave('commentUser.blockedBy', function ($query) use ($authId) {
                $query->where('user_id', $authId);
            })

            ->whereDoesntHave('replies.commentUser.blockedUsers', function ($query) use ($authId) {

                $query->where('blocked_user_id', $authId);
            })

            ->whereDoesntHave('replies.commentUser.blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })

            ->whereDoesntHave('replies.replied_to.blockedUsers', function ($query) use ($authId) {

                $query->where('blocked_user_id', $authId);

            })

            ->whereDoesntHave('replies.replied_to.blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })

            ->orderByDesc('id')
            ->paginate($limit);
    }

    private function transformComment($comment, $authId)
    {
        $isExist                =       $this->IsCommentLiked($comment->post_id, $comment->id, $authId);

        $comment->is_liked      =       $isExist['is_liked'];

        $comment->reaction      =       $isExist['reaction'];

        $comment->total_likes_count = $isExist['total_likes_count'];

        if (isset($comment->commentUser) && !empty($comment->commentUser->profile)) {

            $comment->commentUser->profile = $this->addBaseInImage($comment->commentUser->profile);
        }

        if (isset($comment->replies[0]) && ($comment->replies[0])) {

            $comment->replies->each(function ($replies) use ($authId) {

                $isExist                =   $this->IsCommentLiked($replies->post_id, $replies->id, $authId);

                $replies->is_liked      =   $isExist['is_liked'];

                $replies->reaction      =   $isExist['reaction'];

                $replies->total_likes_count = $isExist['total_likes_count'];

                if (isset($replies->commentUser) && !empty($replies->commentUser->profile)) {

                    $replies->commentUser->profile = $this->addBaseInImage($replies->commentUser->profile);
                }

                if (isset($replies->replied_to) && !empty($replies->replied_to->profile)) {

                    $replies->replied_to->profile = $this->addBaseInImage($replies->replied_to->profile);
                }

                $replies->postedAt  = time_elapsed_string($replies->created_at);
            });
        }

        $comment->postedAt          = time_elapsed_string($comment->created_at);

        return $comment;
    }

    private function getPostWithRelations($postId, $authId)
    {
        $post = Post::withCount(['comment'])->with([

            'post_user' => function ($q) {

                $q->select('id', 'name', 'user_name', 'profile');
            },

            'post_user.user_medical_certificate' => function ($q) {

                $q->select('id', 'medicial_degree_type', 'user_id');

            },

            'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                $q->select('id', 'name');

            },

            'group' => function ($query) {

                $query->select('id', 'name', 'description', 'cover_photo', 'member_count', 'post_count', 'created_by');
            }
        ])->find($postId);

        if (isset($post) && !empty($post)) {

            if (isset($post->media_url) && !empty($post->media_url)) {

                $post->media_url = $this->addBaseInImage($post->media_url);

            }

            if (isset($post->thumbnail) && !empty($post->thumbnail)) {

                $post->thumbnail = $this->addBaseInImage($post->thumbnail);
            }

            if (!empty($post->post_user)) {

                $post->post_user->profile = (isset($post->post_user->profile)) ? $this->addBaseInImage($post->post_user->profile) : null;
            }

            if ($post->group &&  $post->group->cover_photo) {

                $post->group->cover_photo = addBaseUrl($post->group->cover_photo);
            }

            $post->is_joined                = $this->checkCommunityJoined($post->group_id);

            $isExist                        = $this->IsPostLiked($post->id, $authId);

            $post->is_liked                 = $isExist['is_liked'];

            $post->reaction                 = $isExist['reaction'];

            $post->total_likes_count        = $isExist['total_likes_count'];

        }

        return $post;
    }

    private function prepareResponseData($comments, $post)
    {
        $data           = $comments->items();

        $paginationInfo = [

            'current_page' => $comments->currentPage(),

            'last_page' => $comments->lastPage(),

            'total' => $comments->total(),

            'left' => ($comments->total() - ($comments->perPage() * $comments->currentPage()) < 0 ? 0 : $comments->total() - ($comments->perPage() * $comments->currentPage()))
        ];

        return array_merge(['data' => $data], $paginationInfo);
    }
}
