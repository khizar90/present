<?php

namespace App\Http\Controllers\Api;

use App\Actions\FirebaseNotification;
use App\Actions\NewNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\ReportRequest;
use App\Models\BlockList;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\SavedPost;
use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryView;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use stdClass;

class UserController extends Controller
{
    public function profile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'to_id' => 'required|exists:users,id',
        ]);


        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        } else {

            $obj = new stdClass();

            if ($request->user_id == $request->to_id) {

                $blocked = BlockList::where('user_id', $request->user_id)->pluck('block_id');
                $blocked1 = BlockList::where('block_id', $request->user_id)->pluck('user_id');
                $blocked = $blocked->merge($blocked1);

                $user = User::where('id', $request->user_id)->first();

                $followingIds = Follow::where('from_id', $request->user_id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
                $followerIds = Follow::where('to_id', $request->user_id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();
                $friends = array_intersect($followerIds, $followingIds);

                

                $user->follower = Follow::whereNotIn('from_id', $blocked)->whereNotIn('from_id', $friends)->where('to_id', $request->user_id)->count();
                $user->following = Follow::whereNotIn('to_id', $blocked)->whereNotIn('to_id', $friends)->where('from_id', $request->user_id)->count();

                

                $user->friends = count($friends);
                // $post_count = Post::where('user_id', $request->user_id)->count();
                // $user->post_count = $post_count;

                $posts = Post::where('user_id', $request->user_id)->where('type', 'post')->latest()->paginate(12);

                $reels = Post::where('user_id', $request->user_id)->where('type', 'reel')->latest()->paginate(12);



                $saved_user_ids = SavedPost::where('user_id', $request->user_id)->pluck('post_id');
                $saved_posts  = Post::whereIn('id', $saved_user_ids)->latest()->get();


                foreach ($posts as $post) {
                    $postby = User::where('id', $post->user_id)->select('id', 'name', 'image', 'username', 'location', 'verify')->first();
                    $comment = Comment::where('post_id', $post->id)->count();
                    $like = Like::where('post_id', $post->id)->count();
                    $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $data_media = explode(",", $post->media);
                    $post->media = $data_media;

                    $likeList = Like::where('post_id', $post->id)->limit(3)->whereNotIn('id', $blocked)->pluck('user_id');
                    $likeUsers = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likeList)->get();

                    if ($likestatus) {
                        $post->is_liked = true;
                    } else {
                        $post->is_liked = false;
                    }

                    if ($saved) {
                        $post->is_saved = true;
                    } else {
                        $post->is_saved = false;
                    }

                    if ($post->media_type == 'image') {
                        $imagePath = public_path($post->media[0]);
    
                        list($width, $height) = getimagesize($imagePath);
    
                        $post->size = $width / $height;
                    } else {
                        $post->size = 0.80;
                    }
                    $post->comments = $comment;
                    $post->likes = $like;
                    $post->like_user = $likeUsers;
                    $post->user = $postby;
                }

                foreach ($saved_posts as $post) {
                    $postby = User::where('id', $post->user_id)->select('id', 'name', 'username', 'image', 'location', 'verify')->first();
                    $comment = Comment::where('post_id', $post->id)->count();
                    $like = Like::where('post_id', $post->id)->count();
                    $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $likeList = Like::where('post_id', $post->id)->limit(3)->whereNotIn('id', $blocked)->pluck('user_id');
                    $likeUsers = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likeList)->get();

                    if ($post->type == 'post') {
                        $data_media = explode(",", $post->media);
                        $post->media = $data_media;
                    }
                    if ($likestatus) {
                        $post->is_liked = true;
                    } else {
                        $post->is_liked = false;
                    }

                    if ($saved) {
                        $post->is_saved = true;
                    } else {
                        $post->is_saved = false;
                    }
                    if ($post->media_type == 'image') {
                        $imagePath = public_path($post->media[0]);
    
                        list($width, $height) = getimagesize($imagePath);
    
                        $post->size = $width / $height;
                    } else {
                        $post->size = 0.80;
                    }
                    $post->comments = $comment;
                    $post->likes = $like;
                    $post->like_user = $likeUsers;

                    $post->user = $postby;
                }


                foreach ($reels as $reel) {
                    $postby = User::where('id', $reel->user_id)->select('id', 'name', 'image', 'username', 'location', 'verify')->first();
                    $comment = Comment::where('post_id', $reel->id)->count();
                    $like = Like::where('post_id', $reel->id)->count();
                    $likestatus = Like::where('post_id', $reel->id)->where('user_id', $request->user_id)->first();

                    $saved = SavedPost::where('post_id', $reel->id)->where('user_id', $request->user_id)->first();

                    if ($likestatus) {
                        $reel->is_liked = true;
                    } else {
                        $reel->is_liked = false;
                    }

                    if ($saved) {
                        $reel->is_saved = true;
                    } else {
                        $reel->is_saved = false;
                    }
                    $reel->comments = $comment;
                    $reel->likes = $like;
                    $reel->user = $postby;
                }

                $userPosts = Post::where('user_id', $request->to_id)->pluck('id');

                $likes = Like::whereIn('post_id', $userPosts)->count();

                $user->likes = $likes;
                $user->notification = 3;
                $user->follow = false;
                $user->is_block = false;
                $user->all_posts = $posts;
                $user->saved_posts = $saved_posts;
                $user->all_reals = $reels;
                $user->is_friend = false;
            } else {

                $blocked = BlockList::where('user_id', $request->to_id)->pluck('block_id');
                $blocked1 = BlockList::where('block_id', $request->to_id)->pluck('user_id');
                $blocked = $blocked->merge($blocked1);

                $user = User::where('id', $request->to_id)->first();


                $followingIds = Follow::where('from_id', $request->user_id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
                $followerIds = Follow::where('to_id', $request->user_id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();
                $friends = array_intersect($followerIds, $followingIds);


                $follow = Follow::where('from_id', $request->user_id)->where('to_id', $request->to_id)->first();

                $user->follower = Follow::whereNotIn('from_id', $blocked)->whereNotIn('from_id', $friends)->where('to_id', $request->to_id)->count();
                $user->following = Follow::whereNotIn('to_id', $blocked)->whereNotIn('to_id', $friends)->where('from_id', $request->to_id)->count();
              
                $user->friends = 0;


                // $post_count = Post::where('user_id', $request->to_id)->count();
                // $user->post_count = $post_count;

                $posts = Post::where('user_id', $request->to_id)->where('type', 'post')->latest()->paginate(12);

                $reels = Post::where('user_id', $request->to_id)->where('type', 'reel')->latest()->paginate(12);


                foreach ($posts as $post) {
                    $postby = User::where('id', $post->user_id)->select('id', 'name', 'username', 'image', 'location', 'verify')->first();
                    $comment = Comment::where('post_id', $post->id)->count();
                    $like = Like::where('post_id', $post->id)->count();
                    $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                    $likeList = Like::where('post_id', $post->id)->limit(3)->whereNotIn('id', $blocked)->pluck('user_id');
                    $likeUsers = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likeList)->get();

                    if ($likestatus) {
                        $post->is_liked = true;
                    } else {
                        $post->is_liked = false;
                    }

                    if ($saved) {
                        $post->is_saved = true;
                    } else {
                        $post->is_saved = false;
                    }
                    $post->comments = $comment;
                    $post->likes = $like;
                    $post->like_user = $likeUsers;

                    $post->user = $postby;
                    $data_media = explode(",", $post->media);
                    $post->media = $data_media;
                }

                foreach ($reels as $reel) {
                    $postby = User::where('id', $reel->user_id)->select('id', 'name', 'image', 'username', 'location', 'verify')->first();
                    $comment = Comment::where('post_id', $reel->id)->count();
                    $like = Like::where('post_id', $reel->id)->count();
                    $likestatus = Like::where('post_id', $reel->id)->where('user_id', $request->user_id)->first();

                    $saved = SavedPost::where('post_id', $reel->id)->where('user_id', $request->user_id)->first();

                    if ($likestatus) {
                        $reel->is_liked = true;
                    } else {
                        $reel->is_liked = false;
                    }

                    if ($saved) {
                        $reel->is_saved = true;
                    } else {
                        $reel->is_saved = false;
                    }
                    $reel->comments = $comment;
                    $reel->likes = $like;
                    $reel->user = $postby;
                }


                $block = Blocklist::where('user_id', $request->user_id)->orWhere('block_id', $request->to_id)->first();
                if ($block) {
                    $user->is_block = true;
                } else {
                    $user->is_block = false;
                }

                $friend1 = Follow::where('from_id', $request->user_id)->where('to_id', $request->to_id)->first();
                $friend2 = Follow::where('from_id', $request->to_id)->where('to_id', $request->user_id)->first();
                if ($friend1 && $friend2) {
                    $user->is_friend = true;
                } else {
                    $user->is_friend = false;
                }

                $userPosts = Post::where('user_id', $request->to_id)->pluck('id');

                $likes = Like::whereIn('post_id', $userPosts)->count();


                $user->likes = $likes;
                $user->notification = 3;
                $user->all_posts = $posts;
                $user->saved_post = [];
                $user->all_reals = $reels;

                if ($follow) {
                    $user->follow = true;
                } else {
                    $user->follow = false;
                }
            }
            return response()->json([
                'status' => true,
                'action' =>  'User profle',
                'data' => $user
            ]);
        }
    }

    public function follow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_id' => 'required|exists:users,id',
            'to_id' => 'required|exists:users,id',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $check = Follow::where('from_id', $request->from_id)->where('to_id', $request->to_id)->first();
        if ($check) {
            $check->delete();

            Notification::where('person_id', $request->from_id)->where('notification_type', 'social')->where('type', 'follow')->where('person_id', $request->from_id)->delete();

            return response()->json([
                'status' => true,
                'action' =>  'User Un Follow',
            ]);
        }
        $follow = new Follow();
        $follow->from_id = $request->from_id;
        $follow->to_id = $request->to_id;
        $follow->save();

        $from = User::find($request->from_id);
        $to = User::find($request->to_id);
        $post = 0;

        NewNotification::handle($to, $from->id, $post, 'Started Following you.', 'follow', 'social');
        $user = User::find($request->from_id);
        $tokens = UserDevice::where('user_id', $request->to_id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
        FirebaseNotification::handle($tokens, $user->name . ' has started following you', 'New Follower', ['data_id' => $request->from_id, 'type' => 'follow']);

        return response()->json([
            'status' => true,
            'action' =>  'User Follow',
        ]);
    }

    public function following($id)
    {
        $user = User::find($id);
        if ($user) {

            $blocked = BlockList::where('user_id', $id)->pluck('block_id');
            $blocked1 = BlockList::where('block_id', $id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $followingIds = Follow::where('from_id', $id)->pluck('to_id');

            $followingIds = Follow::where('from_id', $id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
            $followerIds = Follow::where('to_id', $id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();
            $friends = array_intersect($followerIds, $followingIds);



            $followings = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $followingIds)->whereNotIn('id', $friends)->whereNotIn('id', $blocked)->paginate(12);

            foreach ($followings as $item) {
                $item->is_follow = true;
            }
            return response()->json([
                'status' => true,
                'action' =>  'Following',
                'data' => $followings
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'User not found',
        ]);
    }

    public function followers($id)
    {
        $user = User::find($id);
        if ($user) {

            $blocked = BlockList::where('user_id', $id)->pluck('block_id');
            $blocked1 = BlockList::where('block_id', $id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $followerIds = Follow::where('to_id', $id)->pluck('from_id');

            $followingIds = Follow::where('from_id', $id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
            $followerIds = Follow::where('to_id', $id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();
            $friends = array_intersect($followerIds, $followingIds);


            $followers = User::select('id', 'name', 'image', 'username', 'location', 'verify')->whereIn('id', $followerIds)
                ->whereNotIn('id', $blocked)->whereNotIn('id', $friends)
                ->paginate(12);


            foreach ($followers as $item) {
                $follow  = Follow::where('from_id', $id)->where('to_id', $item->id)->first();
                if ($follow) {
                    $item->is_follow = true;
                } else {
                    $item->is_follow = false;
                }
            }


            return response()->json([
                'status' => true,
                'action' =>  'Followers',
                'data' => $followers
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'User not found',
        ]);
    }

    public function block(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'block_id' => 'required|exists:users,id',

        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }


        $check = Blocklist::where('block_id', $request->block_id)->where('user_id',  $request->user_id)->first();
        if ($check) {
            $check->delete();
            return response()->json([
                'status' => true,
                'action' => 'User unblocked'
            ]);
        } else {
            $block = new Blocklist;
            $block->block_id = $request->block_id;
            $block->user_id = $request->user_id;
            $block->save();
            return response()->json([
                'status' => true,
                'action' => 'User blocked'
            ]);
        }
    }

    public function friends($id)
    {
        $user = User::find($id);
        if ($user) {

            $blocked = BlockList::where('user_id', $id)->pluck('block_id');
            $blocked1 = BlockList::where('block_id', $id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $followingIds = Follow::where('from_id', $id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
            $followerIds = Follow::where('to_id', $id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();
            $friends = array_intersect($followerIds, $followingIds);

            $followings = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $friends)->whereNotIn('id', $blocked)->paginate(12);


            foreach ($followings as $item) {
                $item->is_friend = true;
            }

            return response()->json([
                'status' => true,
                'action' =>  'Following',
                'data' => $followings
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'User not found',
        ]);
    }

    public function removeFollwer($user_id, $follwerId)
    {
        $find = Follow::where('from_id', $follwerId)->where('to_id', $user_id)->first();
        if ($find) {
            $find->delete();
            return response()->json([
                'status' => true,
                'action' =>  'Follwer Remove',
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'Follow not found',
        ]);
    }

    public function reels(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user) {
            $blocked = Blocklist::where('user_id', $request->user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $request->user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            if ($request->type == 'public') {
                $users = User::select('id')->whereNotIn('id', $blocked)->pluck('id');

                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'reel')
                    ->latest()
                    ->paginate(12);
            }
            if ($request->type == 'friend') {
                $followingIds = Follow::where('from_id', $request->user_id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
                $followerIds = Follow::where('to_id', $request->user_id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();

                $friends = array_intersect($followerIds, $followingIds);
                $users = User::select('id')->whereIn('id', $friends)->whereNotIn('id', $blocked)->pluck('id');
                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'reel')
                    ->latest()
                    ->paginate(12);
            }
            if ($request->type == 'following') {

                $followingIds = Follow::where('from_id', $request->id)->pluck('to_id');
                $users = User::select('id')->whereIn('id', $followingIds)->whereNotIn('id', $blocked)->pluck('id');
                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'reel')
                    ->latest()
                    ->paginate(12);
            }

            $users = User::select('id')->whereNotIn('id', $blocked)->pluck('id');
            $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'reel')
                ->latest()
                ->paginate(12);
            foreach ($posts as $post) {
                $comment = Comment::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
                $like = Like::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
                $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                if ($likestatus) {
                    $post->is_liked = true;
                } else {
                    $post->is_liked = false;
                }

                if ($saved) {
                    $post->is_saved = true;
                } else {
                    $post->is_saved = false;
                }


                // if ($post->type == 'image') {
                //     $imagePath = public_path($post->media);

                //     // Get image size
                //     list($width, $height) = getimagesize($imagePath);

                //     // Output the dimensions
                //     $post->size = $width / $height;
                // } else {
                //     $post->size = 0.80;
                // }

                $post->comments = $comment;
                $post->likes = $like;
            }

            return response()->json([
                'status' => true,
                'action' =>  "Reels",
                'data' => $posts,

            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' =>  "No User found",

            ]);
        }
    }

    public function notification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $notifications = Notification::where('user_id', $request->user_id)->where('person_id', '!=', $request->user_id)->latest()->paginate(12);
        foreach ($notifications as $index => $notif) {

            $person = User::find($notif->person_id);
            if ($person) {
                $notif->person_name = $person->name;
                $notif->person_image = $person->image;
            } else {
                $notif->person_name = '';
                $notif->person_image = '';
            }
            $checkDate = $notif->date;
            if ($index == 0 && !$request->page || $request->page == 1 && $index == 0) {
                $notif->first = true;
            } elseif ($index == 0 && $request->page && $request->page != 1) {
                $notisOld = Notification::select('date')->where('date', '!=', '')->where('user_id',  $request->user_id)->limit(12)->skip(($request->page - 1) * 12)->orderBy('date', 'DESC')->get();
                $current = date_format(date_create($checkDate), 'Y-m-d');
                $previousDate = $notisOld[0]->date;
                $next = date_format(date_create($previousDate), 'Y-m-d');
                if ($current == $next)
                    $notif->first = false;
                else
                    $notif->first = true;
            } else {
                if ($index - 1 >= 0) {
                    $current = date_format(date_create($checkDate), 'Y-m-d');
                    $previousDate = $notifications[$index - 1]->date;
                    $next = date_format(date_create($previousDate), 'Y-m-d');
                    if ($current == $next)
                        $notif->first = false;
                    else
                        $notif->first = true;
                }
            }
            $dbCheck = date_format(date_create($checkDate), 'Y-m-d');
            $date = date_format(date_create($checkDate), 'D, d F');
            $tomorrow = date("Y-m-d", strtotime("-1 days"));
            $todayDate = date('Y-m-d');
            if ($dbCheck == $tomorrow)
                $notif->date = 'Yesterday';
            elseif ($dbCheck == $todayDate)
                $notif->date = 'Today';
            else
                $notif->date = $date;

            $post = Post::find($notif->data_id);
            if ($post) {
                $data_media = explode(",", $post->media);
                $notif->data_media = $data_media;
                $notif->data_type = $post->type;
            } else {
                $notif->data_media = [];
                $notif->data_type = '';
            }

            if ($notif->type == 'follow') {
                $follow = Follow::where('from_id', $request->user_id)->where('to_id', $notif->person_id)->first();
                if ($follow) {
                    $notif->follow = true;
                } else {
                    $notif->follow = false;
                }
            } else {
                $notif->follow = false;
            }
        }
        return response()->json([
            'status' => true,
            'action' =>  'Notifications',
            'data' => $notifications,
        ]);
    }


    public function notificationRead($user_id)
    {
        $user = User::find($user_id);
        if($user){
            Notification::where('user_id', $user_id)->where('is_read', 0)->update(['is_read' => 1]);
            return response()->json([
                'status' => true,
                'action' =>  'Notification Read',
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No User Found',
        ]);
       
    }
    public function home(Request $request)
    {
        $user = User::select('id', 'name', 'username', 'image', 'location', 'verify')->where('id', $request->user_id)->first();
        if ($user) {
            $blocked = Blocklist::where('user_id', $request->user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $request->user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $myStory = Story::where('user_id', $request->user_id)
                ->with(['user:id,name,username,image,location,verify'])
                ->latest()
                ->get();
            foreach ($myStory as $item) {
                $check = StoryLike::wherE('story_id', $item->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $item->is_like = true;
                } else {
                    $item->is_like = false;
                }
                $view_count  = StoryView::where('story_id', $item->id)->count();
                $like_count  = StoryLike::where('story_id', $item->id)->count();
                $item->view_count = $view_count;
                $item->like_count = $like_count;
            }

            $groupedMyStories = [
                [
                    'user' => $myStory->isNotEmpty() ? $myStory[0]->user : $user,
                    'stories' => $myStory,
                ],
            ];
            $followingIds = Follow::where('from_id', $request->user_id)
                ->whereNotIn('to_id', $blocked)
                ->pluck('to_id');

            $stories = Story::with(['user:id,name,username,image,location,verify'])
                ->whereIn('user_id', $followingIds)
                ->latest()
                ->get();

            foreach ($stories as $item) {
                $check = StoryLike::wherE('story_id', $item->id)->where('user_id', $request->user_id)->first();
                if ($check) {
                    $item->is_like = true;
                } else {
                    $item->is_like = false;
                }
                $view_count  = StoryView::where('story_id', $item->id)->count();
                $like_count  = StoryLike::where('story_id', $item->id)->count();
                $item->view_count = $view_count;
                $item->like_count = $like_count;
            }
            $groupedStories = $stories->groupBy('user_id')->map(function ($userStories) {
                return [
                    'user' => $userStories->first()->user,
                    'stories' => $userStories,
                ];
            })->values();


            if ($request->type == 'public') {
                $users = User::select('id')->whereNotIn('id', $blocked)->pluck('id');

                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'post')
                    ->latest()
                    ->paginate(12);
            }
            if ($request->type == 'friend') {
                $followingIds = Follow::where('from_id', $request->user_id)->whereNotIn('to_id', $blocked)->pluck('to_id')->toArray();
                $followerIds = Follow::where('to_id', $request->user_id)->whereNotIn('from_id', $blocked)->pluck('from_id')->toArray();

                $friends = array_intersect($followerIds, $followingIds);
                $users = User::select('id')->whereIn('id', $friends)->whereNotIn('id', $blocked)->pluck('id');
                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'post')
                    ->latest()
                    ->paginate(12);
            }
            if ($request->type == 'following') {

                $followingIds = Follow::where('from_id', $request->id)->pluck('to_id');
                $users = User::select('id')->whereIn('id', $followingIds)->whereNotIn('id', $blocked)->pluck('id');
                $posts = Post::with(['user:id,name,username,image,location,verify'])->whereIn('user_id', $users)->where('type', 'post')
                    ->latest()
                    ->paginate(12);
            }



            foreach ($posts as $post) {
                $comment = Comment::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
                $like = Like::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
                $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

                $data_media = explode(",", $post->media);
                $post->media = $data_media;

                $likeList = Like::where('post_id', $post->id)->limit(3)->whereNotIn('id', $blocked)->pluck('user_id');
                $likeUsers = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likeList)->get();


                if ($request->type == 'following' || $request->type == 'friend') {
                    $post->user->is_follow = true;
                } else {
                    $follow = Follow::where('from_id', $request->user_id)->where('to_id', $post->user->id)->first();
                    if ($follow) {
                        $post->user->is_follow = true;
                    } else {
                        $post->user->is_follow = false;
                    }
                }
                if ($likestatus) {
                    $post->is_liked = true;
                } else {
                    $post->is_liked = false;
                }

                if ($saved) {
                    $post->is_saved = true;
                } else {
                    $post->is_saved = false;
                }


                if ($post->media_type == 'image') {
                    $imagePath = public_path($post->media[0]);

                    list($width, $height) = getimagesize($imagePath);

                    $post->size = $width / $height;
                } else {
                    $post->size = 0.80;
                }

                $post->comments = $comment;
                $post->likes = $like;
                $post->like_user = $likeUsers;
            }

            return response()->json([
                'status' => true,
                'action' =>  "Home",
                'data' => array(
                    'my_stories' => $groupedMyStories,
                    'stories' => $groupedStories,
                    'posts' => $posts
                )
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' =>  "No User found",

            ]);
        }
    }

    public function serachUser(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user) {

            $value = $request->value;

            $blocked = Blocklist::where('user_id', $request->user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $request->user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            if ($value != '') {
                $users = User::select('id', 'name', 'username', 'image', 'location', 'verify')
                    ->where('name', 'like', "%$value%")->where('id', '!=', $request->user_id)->whereNotIn('id', $blocked)->paginate(12);
            } else {
                $users = [];
            }


            return response()->json([
                'status' => true,
                'action' =>  "Search Users",
                'data' => $users
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No User found",

        ]);
    }

    public function counter($id)
    {
        $user = User::find($id);
        $obj = new stdClass();
        if ($user) {
            $message = Message::where('to', $user->id)->where('ticket_id', 0)->where('is_read', 0)->count();
            $notification = Notification::where('user_id', $user->id)->where('person_id', '!=', $user->id)->where('is_read', 0)->count();


            return response()->json([
                'status' => true,
                'action' =>  'Counter',
                'data' => array(
                    'message_count' => $message,
                    'notification_count' => $notification
                )
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'User not found',
        ]);
    }


    public function report(ReportRequest $request)
    {
        $create = new Report();
        $create->user_id = $request->user_id;
        $create->type = $request->type;
        $create->reported_id = $request->reported_id;
        $create->message = $request->message;
        $create->save();

        return response()->json([
            'status' => true,
            'action' =>  'Report Added',
        ]);
    }
}
