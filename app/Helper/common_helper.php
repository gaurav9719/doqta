<?php

use Carbon\Carbon;
use App\Models\Post;
use App\Models\User;
use App\Models\Group;
use App\Models\Comment;
use App\Models\Message;
use App\Models\PostLike;
use App\Models\UserQuota;
use App\Models\BlockedUser;
use App\Models\GroupMember;
use App\Models\UserFollower;
use App\Models\GroupMemberRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

if (!function_exists('transformParentPostData')) {

    function transformParentPostData($post, $authId)
    {
        #------------ CHECK IF PARENT POST PRESENT -------------#
        if ($post->parent_post->post_user && $post->parent_post->post_user->profile) {

            $post->parent_post->post_user->profile =    addBaseUrl($post->parent_post->post_user->profile);
        }

        if (isset($post->parent_post->media_url) && !empty($post->parent_post->media_url)) {

            $post->parent_post->media_url        =       addBaseUrl($post->parent_post->media_url);
        }

        if (isset($post->parent_post->thumbnail) && !empty($post->parent_post->thumbnail)) {

            $post->parent_post->thumbnail        =       addBaseUrl($post->parent_post->thumbnail);
        }

        $isExist                                 =       IsPostLikedByUser($post->parent_post->id, $authId, 1);
        $post->parent_post->is_liked             =       $isExist['is_liked'];
        $post->parent_post->reaction             =       $isExist['reaction'];
        $post->parent_post->total_likes_count    =       $isExist['total_likes_count'];
        $post->parent_post->total_comment_count  =       $isExist['total_comment_count'];
        $isRepost                                =       Post::where(['parent_id' => $post->parent_post->id, 'user_id' => $authId, 'is_active' => 1])->exists();
        $post->parent_post->is_reposted          =       ($isRepost) ? 1 : 0;
        $post->parent_post->postedAt             =      time_elapsed_string($post->created_at);
        $post->parent_post->post_category_name   =      post_category($post->parent_post->post_category);
        return $post;
    }
}


if (!function_exists('transformPostData')) {

    function transformPostData($homeScreenPost, $authId)
    {
        if (isset($homeScreenPost->media_url) && !empty($homeScreenPost->media_url)) {

            $homeScreenPost->media_url      =  addBaseUrl($homeScreenPost->media_url);
        }

        if (isset($homeScreenPost->thumbnail) && !empty($homeScreenPost->thumbnail)) {

            $homeScreenPost->thumbnail      =  addBaseUrl($homeScreenPost->thumbnail);
        }

        if ($homeScreenPost->parent_post && $homeScreenPost->parent_post->post_user && $homeScreenPost->parent_post->post_user->profile) {

            $homeScreenPost->parent_post->post_user->profile = addBaseUrl($homeScreenPost->parent_post->post_user->profile);
        }

        if (isset($homeScreenPost->post_user) &&  !empty($homeScreenPost->post_user->profile)) {

            $homeScreenPost->post_user->profile      =  addBaseUrl($homeScreenPost->post_user->profile);
        }
        if ($homeScreenPost->group &&  $homeScreenPost->group->cover_photo) {

            $homeScreenPost->group->cover_photo      =  addBaseUrl($homeScreenPost->group->cover_photo);
        }
        $isExist                            =   IsPostLikedByUser($homeScreenPost->id, $authId, 1);
        $homeScreenPost->is_liked           =   $isExist['is_liked'];
        $homeScreenPost->reaction           =   $isExist['reaction'];

        $homeScreenPost->total_likes_count  =       $isExist['total_likes_count'];
        $homeScreenPost->total_comment_count =      $isExist['total_comment_count'];
        $isRepost                           =       Post::where(['parent_id' => $homeScreenPost->id, 'user_id' => $authId, 'is_active' => 1])->exists();
        $homeScreenPost->is_reposted        =   ($isRepost) ? 1 : 0;
        $homeScreenPost->post_category_name =   post_category($homeScreenPost->post_category);
        $homeScreenPost->postedAt           =   time_elapsed_string($homeScreenPost->created_at);
        #------------ parent post data-----------------#
        return $homeScreenPost;
    }
}

if (!function_exists('addBaseUrl')) {

    function addBaseUrl($cover_photo)
    {

        if (isset($cover_photo) && !empty($cover_photo)) {

            return (filter_var($cover_photo, FILTER_VALIDATE_URL)) ? $cover_photo : asset('storage/' . $cover_photo);
        } else {

            return null;
        }
    }
}

if (!function_exists('IsPostLikedByUser')) {
    function IsPostLikedByUser($postId, $authId, $type = "")
    {

        $isExist                    =   PostLike::where(['user_id' => $authId, 'post_id' => $postId])->first();
        $data['is_liked']           =   (isset($isExist) && !empty($isExist)) ? 1 : 0;
        $data['reaction']           =   (isset($isExist->reaction) && !empty($isExist->reaction)) ? $isExist->reaction : 0;
        $data['total_likes_count']  =   PostLike::where(['post_id' => $postId])->count();
        if (!empty($type)) {

            $data['total_comment_count']  =   Comment::where(['post_id' => $postId])->count();
        }
        $isRepost                  =   Post::where(['parent_id' => $postId, 'user_id' => $authId, 'is_active' => 1])->exists();
        $data['is_reposted']       =    ($isRepost) ? 1 : 0;
        return $data;
    }
}


if (!function_exists('IsPostAvailable')) {

    function IsPostAvailable($postId, $authId)
    {


        return Post::where(function ($query) use ($authId) {

            $query->whereDoesntHave('post_user.blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('post_user.blockedUsers', function ($query) use ($authId) {
                    $query->where('blocked_user_id', $authId);
                });


            $query->whereDoesntHave('group.groupOwner.blockedBy', function ($query) use ($authId) {
                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('group.groupOwner.blockedUsers', function ($query) use ($authId) {
                    $query->where('blocked_user_id', $authId);
                });
        })->where(['id' => $postId, 'is_active' => 1])->first();
    }
}

if (!function_exists('IsUserBlocked')) {

    function IsUserBlocked($user_id, $authId, $type = "")
    {


        $is_blocked =  User::where(function ($query) use ($authId) {

            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })
                ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                    $query->where('blocked_user_id', $authId);
                });
        });

        if (empty($type)) {

            $is_blocked     = $is_blocked->where(['id' => $user_id, 'is_active' => 1])->first();
        } else {

            $is_blocked     =   $is_blocked->where(['id' => $user_id, 'is_active' => 1])->exists();
        }

        return $is_blocked;
    }
}



if (!function_exists('IsCommunityOwnerBlocked')) {

    function IsCommunityOwnerBlocked($communityId, $authId)
    {
        return  Group::where(['id' => $communityId, 'is_active' => 1])

            ->whereHas('groupOwner', function ($query) use ($authId) {

                $query->where('is_active', 1) // Check if group owner is active

                    ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                        $query->where('user_id', $authId);
                    })
                    ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                        $query->where('blocked_user_id', $authId);
                    });
            })->exists();
    }
}


if (!function_exists('checkUserNameAvailable')) {
    function checkUserNameAvailable($username, $authId)
    {
        return User::where('id', '<>', $authId)->where('user_name', $username)->exists();
    }
}



if (!function_exists('removePictureFromFolder')) {
    function removePictureFromFolder($image)
    {
        if (isset($image) && !empty($image)) {

            if (Storage::disk('public')->exists($image)) {

                Storage::disk('public')->delete($image); // delete file from specific disk e.g; s3, local etc

            }
        }
    }
}

if (!function_exists('isBlockedUser')) {
    function isBlockedUser($user1, $user2)
    {
        $isBlock    = BlockedUser::where(['user_id' => $user1, 'blocked_user_id' => $user2])->exists();
        return ($isBlock) ? 1 : 0;
    }
}



if (!function_exists('getPost')) {

    function getPost($authId, $postData)
    {

        return $postData->whereHas('post_user', function ($query) {

            $query->where('is_active', 1);
        })->whereHas('group')

            // commented on july 28
            // ->whereHas('group', function ($query) use ($authId) {

            //     // $query->where('is_active', 1)
            //     $query->whereDoesntHave('groupOwner.blockedBy', function ($query) use ($authId) {

            //         $query->where('user_id', $authId);

            //     })->whereDoesntHave('groupOwner.blockedUsers', function ($query) use ($authId) {

            //         $query->where('blocked_user_id', $authId);
            //     });
            // })

            // commented on july 28

            ->whereDoesntHave('reportPosts', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })

            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                $query->where('blocked_user_id', $authId);
            })

            ->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })

            ->whereDoesntHave('hiddenPosts', function ($query) use ($authId) {

                $query->where('user_id', $authId);
            })
            #- jun 10 
            ->where(function ($query) use ($authId) {

                $query->whereDoesntHave('parent_post', function ($query) use ($authId) {

                    $query->where('is_active', 1)

                        ->whereHas('post_user', function ($query) use ($authId) {
                            // Check if authenticated user is not blocked by the post user
                            $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                                $query->where('user_id', $authId);
                            });
                        });
                })
                    ->orWhereHas('parent_post', function ($query) use ($authId) {

                        $query->where('is_active', 1)

                            ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
                                // Check if post user is not blocked by the authenticated user
                                $query->where('blocked_user_id', $authId);
                            });
                    });
            })
            ->with([

                'post_user:id,name,user_name,profile',

                'post_user.user_medical_certificate' => function ($q) {

                    $q->select('id', 'medicial_degree_type', 'user_id');
                },
                'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                    $q->select('id', 'name');
                },
                'group:id,name,description,cover_photo,member_count,post_count,created_by',
                'parent_post' => function ($query) {

                    $query->select('*')
                        ->where('is_active', 1)
                        ->with([
                            'post_user:id,name,user_name,profile,is_active',
                            'post_user.user_medical_certificate' => function ($q) {
                                $q->select('id', 'medicial_degree_type', 'user_id');
                            },
                            'post_user.user_medical_certificate.medical_certificate' => function ($q) {

                                $q->select('id', 'name');
                            },
                            'group:id,name,description,created_by,is_active'
                        ]);
                }
            ]);
    }
}


if (!function_exists('GroupData')) {

    function GroupData($authId, $community_id)
    {
        $group      =     Group::where('id', $community_id)->where('is_active', 1)

            ->whereHas('groupOwner', function ($query) use ($authId) {

                $query->whereDoesntHave('blockedBy', function ($query) use ($authId) {

                    $query->where('user_id', $authId);
                })
                    ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

                        $query->where('blocked_user_id', $authId);
                    });
            })->withCount('groupMember')->first();

        if (empty($group)) {

            return 400;
        }
        //check group created user not block me or neither blocked by me 

        if (isset($group) && !empty($group)) {

            if ((isset($group->cover_photo) && !empty($group->cover_photo))) {

                $group->cover_photo     =       addBaseUrl($group->cover_photo);
            }
            $isGroupMember              =       GroupMember::where(['group_id' => $group->id, 'user_id' => $authId, 'is_active' => 1])->first();

            if (isset($isGroupMember) && !empty($isGroupMember)) {

                $group->is_joined       =       1; // not join the group
                $group->role            =       $isGroupMember->role;
            } else {

                $request                =       GroupMemberRequest::where(['group_id' => $group->id, 'is_active' => 1, 'user_id' => $authId])->first();

                if (isset($request) && !empty($request)) {

                    if ($request->status == "pending") {

                        $group->is_joined = 2; // pending request

                    } elseif ($request->status == "rejected") {

                        $group->is_joined = 3; // rejected
                    }
                } else {
                    $group->is_joined = 0; // not join the group
                }
                $group->role = null;
            }
        }

        return $group;
    }
}



if (!function_exists('reactiveChat')) {

    function reactiveChat($message, $myId, $reciever)
    {

        if (($message->is_user1_trash == $myId) || ($message->is_user2_trash == $myId)) {

            if ($message->is_user1_trash == $myId) {

                $message->is_user1_trash    =   null;
            } else {

                $message->is_user2_trash    =   null;
            }
        }

        if (($message->is_user1_trash == $reciever) || ($message->is_user2_trash == $reciever)) {

            if ($message->is_user1_trash == $reciever) {

                $message->is_user1_trash    =   null;
            } else {

                $message->is_user2_trash    =   null;
            }
        }

        if ($message->is_active == 0) {

            $message->is_active         =   1;
        }

        return $message;
    }
}


if (!function_exists('supportUserS')) {


    // function supportUserS($request, $authId, $limit, $type = "")
    // {
    //     try {

    //         $user       =   User::where('is_active',1)->whereNotIn('role',[2,3])->whereDoesntHave('blockedBy', function ($query) use ($authId) {

    //                 $query->where('user_id', $authId);

    //         })
    //         ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {

    //             $query->where('blocked_user_id', $authId);

    //         })

    //         ->whereDoesntHave('supporter', function ($query) use ($authId) {

    //                 $query->where('follower_user_id', $authId);

    //         })->where('id', '<>', $authId);

    //         if (isset($request->search) && !empty($request->search)) {

    //             $searchTerm = $request->search;
    //             // Search for users by name
    //             $user->where(function ($query) use ($searchTerm) {

    //                 $query->where('name', 'like', "%$searchTerm%")
    //                       ->where('is_active', 1);
    //             });
    //             // Find group IDs where group name matches the search term
    //             $groupIdsQuery  = Group::where('name', 'like', "%$searchTerm%")
    //                                   ->where('is_active', 1)

    //                                   ->whereHas('groupOwner', function ($query) use ($authId) {

    //                                       $query->where('is_active', 1)

    //                                             ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
    //                                                 $query->where('user_id', $authId);
    //                                             })

    //                                             ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
    //                                                 $query->where('blocked_user_id', $authId);
    //                                             });
    //                                   });
    //                 // Get group IDs
    //             $groupIds = $groupIdsQuery->pluck('id');
    //         }

    //         if(isset($request->search) && !empty($request->search)){

    //             $user->orWhereHas('user_group', function ($query) use ($groupIds) {

    //                 $query->whereIn('group_id', $groupIds);

    //             })->with(['user_single_group' => function($query) use ($authId,$groupIds) {

    //                 $query->orWhereHas('groupUser', function ($query) use ($authId,$groupIds) {

    //                     $query->where('is_active', 1)->whereIn('group_id',$groupIds)

    //                           ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
    //                               $query->where('user_id', $authId);
    //                           })
    //                           ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
    //                               $query->where('blocked_user_id', $authId);
    //                           });
    //                 })->select('id', 'group_id', 'user_id');

    //             }, 'user_single_group.group']);
    //         }else{

    //             $user->with(['user_single_group' => function($query) use ($authId) {

    //                 $query->whereHas('groupUser', function ($query) use ($authId) {
    //                     $query->where('is_active', 1)
    //                           ->whereDoesntHave('blockedBy', function ($query) use ($authId) {
    //                               $query->where('user_id', $authId);
    //                           })
    //                           ->whereDoesntHave('blockedUsers', function ($query) use ($authId) {
    //                               $query->where('blocked_user_id', $authId);
    //                           });
    //                 })->select('id', 'group_id', 'user_id');

    //             }, 'user_single_group.group']);
    //         }

    //         $user->groupBy('id');

    //         if (isset($type) && !empty($type)) {

    //             $supportUser  =   $user->get()->take($limit);

    //         } else {

    //             $supportUser  =   $user->simplePaginate($limit);
    //         }


    //         if (isset($supportUser[0]) && !empty($supportUser[0])) {

    //             $supportUser->each(function ($suggestMember) use ($authId) {

    //                 if (isset($suggestMember->groupUser) && !empty($suggestMember->groupUser)) {
    //                     if (isset($suggestMember->groupUser->profile) && !empty($suggestMember->groupUser->profile)) {
    //                         $suggestMember->groupUser->profile =   $this->addBaseInImage($suggestMember->groupUser->profile);
    //                     }
    //                 }

    //                 if (isset($suggestMember->communities) && !empty($suggestMember->communities)) {
    //                     if (isset($suggestMember->communities->cover_photo) && !empty($suggestMember->communities->cover_photo)) {

    //                         $suggestMember->communities->cover_photo =   $this->addBaseInImage($suggestMember->communities->cover_photo);
    //                     }
    //                 }

    //                 $suggestMember->is_supporting                =   (UserFollower::where(['user_id' => $suggestMember->user_id, 'follower_user_id' => $authId, 'status' => 2])->exists()) ? 1 : 0;
    //             });
    //         }
    //         if (isset($type) && !empty($type)) {

    //             return  $supportUser;
    //             // return $data;
    //         } else {
    //             $notification_count     =   notification_count();
    //             $response = [
    //                 'status' => 200,
    //                 'message' => trans('message.dicover_people'),
    //                 'data'    => $supportUser,
    //                 'notification'=>$notification_count,

    //             ];
    //             return response()->json($response, $response['code']);


    //         }
    //     } catch (Exception $e) {

    //         Log::error('Error caught: "supportUsers-service"' . $e->getMessage());

    //         return $e;
    //         // return 400;
    //     }
    // }


    function supportUserS($request, $authId, $limit, $type = "", $category = "")
    {
        try {
            // Initial query to get active users not blocked by or blocking the auth user
            $groupIds = [];
            $userQuery = User::select('id', 'name', 'user_name', 'profile', 'is_active')
                ->where('is_active', 1)
                ->whereNotNull('user_name')
                ->whereNotIn('role', [2, 3])
                ->whereDoesntHave('blockedBy', fn ($query) => $query->where('user_id', $authId))
                ->whereDoesntHave('blockedUsers', fn ($query) => $query->where('blocked_user_id', $authId))
                ->whereDoesntHave('supporter', fn ($query) => $query->where('follower_user_id', $authId))
                ->where('id', '<>', $authId);
            // Handle search term if provided
            if (isset($category) && !empty($category)) {
                // Filter based on user participant
                $userQuery->whereHas('userParticipant', function ($query) {
                    $query->whereIn('participant_id', [2]);
                });
            }
            if (!empty($request->search)) {

                $searchTerm = $request->search;
                $userQuery->where(function ($query) use ($searchTerm) {
                    $query->where('user_name', 'like', "%$searchTerm%")
                        ->where('is_active', 1)
                        ->whereNotNull('user_name');
                });

                // Get group IDs matching the search term
                $groupIds = Group::where('name', 'like', "%$searchTerm%")

                    ->where('is_active', 1)

                    ->whereHas('groupOwner', function ($query) use ($authId) {
                        $query->where('is_active', 1)
                            ->whereDoesntHave('blockedBy', fn ($q) => $q->where('user_id', $authId))
                            ->whereDoesntHave('blockedUsers', fn ($q) => $q->where('blocked_user_id', $authId));
                    })
                    ->pluck('id');

                // Add users belonging to the groups matching the search term
                if (!empty($groupIds)) {

                    $userQuery->orWhereHas('user_group', function ($query) use ($groupIds) {

                        $query->whereIn('group_id', $groupIds)

                            ->whereHas('user', function ($query) {

                                $query->whereNotNull('user_name'); // Ensure user_name is not null
                            });
                    });
                }
            }

            // Load related groups and users
            $userQuery->with(
                [
                    'user_single_group' => function ($query) use ($authId, $groupIds) {
                        $query->whereHas('groupUser', function ($query) use ($authId, $groupIds) {

                            $query->where('is_active', 1)

                                ->when(isset($groupIds[0]) && !empty($groupIds), fn ($q) => $q->whereIn('group_id', $groupIds))
                                ->whereDoesntHave('blockedBy', fn ($q) => $q->where('user_id', $authId))
                                ->whereDoesntHave('blockedUsers', fn ($q) => $q->where('blocked_user_id', $authId));
                        })
                            ->select('id', 'group_id', 'user_id');
                    },
                    'user_single_group.group' => function ($query) {

                        $query->select('id', 'name');
                    },

                    'user_medical_certificate' => function ($q) {

                        $q->select('id', 'medicial_degree_type', 'user_id');
                    },
                    'user_medical_certificate.medical_certificate' => function ($q) {

                        $q->select('id', 'name');
                    }
                ]

            );
            $userQuery->groupBy('id');

            // Retrieve users with pagination or as a collection based on type
            $supportUsers = $type ? $userQuery->take($limit)->get() : $userQuery->simplePaginate($limit);


            // Enhance user data with additional info
            $supportUsers->each(function ($user) use ($authId) {

                if (isset($user->groupUser->profile)) {

                    $user->groupUser->profile       = addBaseUrl($user->groupUser->profile);
                }
                if (isset($user->communities->cover_photo)) {

                    $user->communities->cover_photo = addBaseUrl($user->communities->cover_photo);
                }

                if (isset($user['profile']) && !empty($user['profile'])) {

                    $user->profile                  = addBaseUrl($user->profile);
                }
                $user->is_supporting = UserFollower::where(['user_id' => $user->id, 'follower_user_id' => $authId, 'status' => 2])->exists() ? 1 : 0;

                // $user->user_medical_certificate     =   (isset($user->user_medical_certificate) && !empty($user->user_medical_certificate))?$user->user_medical_certificate->pluck('medical_certificate'):[];
            });

            if ($type) {

                return $supportUsers;
            } else {

                return response()->json([
                    'status' => 200,
                    'message' => trans('message.dicover_people'),
                    'data' => $supportUsers,
                    'notification' => notification_count(),
                ], 200);
            }
        } catch (Exception $e) {
            Log::error('Error in supportUserS service: ' . $e->getMessage());
            return 400;
        }
    }
}


function checkLastMessage($message_id, $myId)
{

    try {

        $result         =       Message::with([

            'sender' => function ($query) {

                $query->select('id', 'name', 'user_name', 'profile');
            },
            'reply_to.sender' => function ($query) {

                $query->select('id', 'name', 'profile');
            }
        ])
            ->where('id', $message_id)
            ->where(function ($query) use ($myId) {
                $query->where(function ($query) use ($myId) {

                    $query->whereNull('is_user1_trash')
                        ->orWhere('is_user1_trash', '!=', $myId);
                })
                    ->where(function ($query) use ($myId) {

                        $query->whereNull('is_user2_trash')
                            ->orWhere('is_user2_trash', '!=', $myId);
                    });
            })->first();

        if (isset($result) && !empty($result)) {

            // $result->time_ago         =              $result->created_at->diffForHumans();
            $result->time_ago         =       time_elapsed_string($result->created_at);
            $result->is_blocked       =       isBlockedUser($myId, $result->sender_id);
            $result->blocked_by       =       isBlockedUser($result->sender_id, $myId);

            if (isset($result->sender) && !empty($result->sender)) {

                if (isset($result->sender->profile) && !empty($result->sender->profile)) {

                    $result->sender->profile        =   addBaseUrl($result->sender->profile);
                }
            }

            if (isset($result->media) && !empty($result->media)) {

                $result->media                      =   addBaseUrl($result->media);
            }

            if (isset($result->media_thumbnail) && !empty($result->media_thumbnail)) {

                $result->media_thumbnail            =  addBaseUrl($result->media_thumbnail);
            }

            if (isset($result->reply_to) && !empty($result->reply_to)) {

                if (isset($result->reply_to->media) && !empty($result->reply_to->media)) {

                    $result->reply_to->media        =    addBaseUrl($result->reply_to->media);
                }

                if (isset($result->reply_to->media_thumbnail) && !empty($result->reply_to->media_thumbnail)) {

                    $result->reply_to->media_thumbnail        =   addBaseUrl($result->reply_to->media_thumbnail);
                }

                if (isset($result->reply_to->sender) && !empty($result->reply_to->sender)) {

                    if (isset($result->reply_to->sender->profile) && !empty($result->reply_to->sender->profile)) {

                        $result->reply_to->sender->profile        =   addBaseUrl($result->reply_to->sender->profile);
                    }
                }
            }
        }
        return $result;
    } catch (Exception $e) {
        Log::error('Error caught: "destory" ' . $e->getMessage());
        // return $this->sendError($e->getMessage(), [], 400);
    }
}



#-------------------------- C H A T         H I S T O R Y --------------_____________#
// 

if (!function_exists('userQuota')) {

    function userQuota($userId)
    {

        $date           =   date('Y-m-d');
        $quota          =   UserQuota::firstOrCreate(

            ['user_id' => $userId, 'date' => $date],
            [
                'community_posts' => 0,
                'chatbot_messages' => 0,
                'journal_entries' => 0,
                'rewrite_with_ai' => 0,
                'friend_requests' => 0,
                'post_comments' => 0,
                'community_join_requests' => 0
            ]
        );
        return $quota;
    }
}

#-------------------------- C H A T         H I S T O R Y ---------------------------#


#-----------------  G E T       C H A T         I N S I D E   D A T A 

if (!function_exists('processProfile')) {

    function processProfile($profile)
    {
        if (isset($profile) && !empty($profile)) {
            if (isset($profile->profile) && !empty($profile->profile)) {
                $profile->profile = addBaseUrl($profile->profile);
            }
        }
    }
}

if (!function_exists('processMedia')) {

    function processMedia(&$result)
    {
        if (isset($result->media) && !empty($result->media)) {
            $result->media = addBaseUrl($result->media);
        }
        if (isset($result->media_thumbnail) && !empty($result->media_thumbnail)) {
            $result->media_thumbnail = addBaseUrl($result->media_thumbnail);
        }
    }
}

if (!function_exists('processReplyTo')) {

    function processReplyTo($replyTo)
    {
        if (isset($replyTo) && !empty($replyTo)) {

            processMedia($replyTo);
            processProfile($replyTo->sender);
        }
    }
}

if (!function_exists('processPost')) {

    function processPost($post)
    {
        if (isset($post) && !empty($post)) {
            if (isset($post->media_url) && !empty($post->media_url)) {
                $post->media_url = addBaseUrl($post->media_url);
            }
            if (isset($post->thumbnail) && !empty($post->thumbnail)) {
                $post->thumbnail = addBaseUrl($post->thumbnail);
            }
            if (isset($post->post_user) && !empty($post->post_user->profile)) {
                $post->post_user->profile = addBaseUrl($post->post_user->profile);
            }
            $post->postedAt = time_elapsed_string($post->created_at);
        }
    }
}

if (!function_exists('addTimestampsAndBlockStatus')) {
    function addTimestampsAndBlockStatus(&$result, $userId)
    {
        $result->time_ago = time_elapsed_string($result->created_at);
        $result->is_blocked = isBlockedUser($userId, $result->sender_id);
        $result->blocked_by = isBlockedUser($result->sender_id, $userId);
    }
}


if (!function_exists('likeTypes')) {
    function likeTypes($like)
    {
        // Define an associative array to map the like values to their respective reactions
        $reactions = [
            1 => 'Support',
            2 => 'Helpful',
            3 => 'Unhelpful',
        ];

        // Return the corresponding reaction or default to 'Support'
        return $reactions[$like] ?? 'Support';
    }
}
