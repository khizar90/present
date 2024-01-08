<?php

namespace App\Http\Controllers\Api;

use App\Actions\FirebaseNotification;
use App\Actions\NewNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Post\AddPostRequest;
use App\Models\BlockList;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Follow;
use App\Models\Like;
use App\Models\Notification;
use App\Models\Post;
use App\Models\SavedPost;
use App\Models\User;
use App\Models\UserDevice;
use FFMpeg\Coordinate\TimeCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use FFMpeg\FFMpeg;

class PostController extends Controller
{


    public function add(AddPostRequest $request)
    {

        $thumbnailPath = '';

        $images = $request->file('media');

        $imagePaths = [];

        foreach ($images as $file) {
            $extension = $file->getClientOriginalExtension();
            $mime = explode('/', $file->getClientMimeType());
            $filename = time() . '-' . uniqid() . '.' . $extension;
            if ($file->move('uploads/user/' . $request->user_id . '/post/', $filename)) {
                $imagePaths[] = '/uploads/user/' . $request->user_id . '/post/' . $filename;
            }
            // if ($request->media_type == 'video') {
            //     $thumbnailPath = $this->getVideoThumbnail($filename, $request->user_id);
            // }
        }



        $imageString = implode(',', $imagePaths);




        $create = new Post();

        if ($request->hasFile('music_media')) {
            $file = $request->file('music_media');
            $extension = $file->getClientOriginalExtension();
            $mime = explode('/', $file->getClientMimeType());
            $filename = time() . '-' . uniqid() . '.' . $extension;
            if ($file->move('uploads/user/' . $request->user_id . '/post/music/', $filename))
                $music = '/uploads/user/' . $request->user_id . '/post/music/' . $filename;
            $create->music_media = $music;
        }

        $create->user_id = $request->user_id;
        $create->type = $request->type;
        $create->caption = $request->caption ?: '';
        $create->media_type = $request->media_type;
        $create->media = $imageString;
        $create->thumbnail = $thumbnailPath;
        $create->location = $request->location;
        $create->lat = $request->lat;
        $create->lng = $request->lng;
        $create->music_name = $request->music_name ?: '';
        $create->time = strtotime(date('Y-m-d H:i:s'));
        $create->save();

        return response()->json([
            'status' => true,
            'action' => "Post Added"
        ]);
    }

    public function getVideoThumbnail($videoFile, $user_id)
    {

        $ffmpeg = FFMpeg::create(
            array(
                'ffmpeg.binaries'  => "/usr/bin//ffmpeg",
                'ffprobe.binaries' => "/userbin/ffprobe",
            )
        );


        $video = $ffmpeg->open(public_path('uploads/user/' . $user_id . '/post/' . $videoFile));

        $filename = time() . '-' . uniqid() . '.' .  '_thumbnail.jpg';
        $thumbnailPath = '/uploads/thumbnails/' . $filename;

        $video->frame(TimeCode::fromSeconds(2))
            ->save(public_path($thumbnailPath));

        return $thumbnailPath;
    }

    public function edit(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'post_id' => "required|exists:posts,id",
        ]);

        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $create = Post::find($request->post_id);

        if ($request->has('caption')) {
            if ($request->caption != null) {
                $create->caption = $request->caption;
            }
        }

        $create->save();

        return response()->json([
            'status' => true,
            'action' => "Post Edit"
        ]);
    }

    public function delete($id)
    {
        $find = Post::find($id);
        if ($find) {

            $mediaUrls = explode(',', $find->media);

            // Delete each file corresponding to the extracted paths
            foreach ($mediaUrls as $url) {
                // Assuming $url contains the absolute path of each file
                $filePath = public_path($url);

                // Check if the file exists before attempting deletion
                if (file_exists($filePath)) {
                    unlink($filePath); // Delete the file
                }
            }

            if ($find->music_media != '') {
                $audiofilePath = public_path($find->music_media);
                if (file_exists($audiofilePath)) {
                    unlink($audiofilePath); // Delete the file
                }
            }

            $find->delete();
            return response()->json([
                'status' => true,
                'action' => "Post Deleted"
            ]);
        }
        return response()->json([
            'status' => false,
            'action' => "No Post found"
        ]);
    }

    public function like(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }
        $post = Post::find($request->post_id);
        $other = User::find($request->user_id);
        $user = User::find($post->user_id);

        $check = Like::where('post_id', $request->post_id)->where('user_id', $request->user_id)->first();
        if ($check) {
            $check->delete();
            Notification::where('data_id', $request->post_id)->where('notification_type', 'social')->where('type', 'like')->where('person_id', $request->user_id)->delete();
            return response()->json([
                'status' => true,
                'action' =>  'Post like remove',
            ]);
        }

        $like  = new Like();
        $like->post_id = $request->post_id;
        $like->user_id = $request->user_id;
        $like->save();

        if ($post->type == 'post') {
            NewNotification::handle($user, $other->id, $post->id, 'has liked your post', 'like', 'social');

            if ($post->user_id != $request->user_id) {
                $tokens = UserDevice::where('user_id', $user->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
                FirebaseNotification::handle($tokens, $other->name . ' has Liked your Post', 'New Like', ['data_id' => $request->post_id, 'type' => 'post']);
            }
        } else {
            NewNotification::handle($user, $other->id, $post->id, 'has liked your reel', 'like', 'social');

            if ($post->user_id != $request->user_id) {
                $tokens = UserDevice::where('user_id', $user->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
                FirebaseNotification::handle($tokens, $other->name . ' has Liked your Reel', 'New Like', ['data_id' => $request->post_id, 'type' => 'reel']);
            }
        }


        return response()->json([
            'status' => true,
            'action' =>  'Post like',
        ]);
    }

    public function likeList($user_id, $post_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $post = Post::find($post_id);
        if ($post) {
            $blocked = Blocklist::where('user_id', $user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $likes = Like::where('post_id', $post_id)->whereNotIn('user_id', $blocked)->pluck('user_id');
            $users = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likes)->Paginate(10);
            return response()->json([
                'status' => true,
                'action' =>  "Users",
                'data' => $users
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No post found",
        ]);
    }

    public function comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'user_id' => 'required|exists:users,id',
            'comment' => 'required',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $post = Post::find($request->post_id);
        $other = User::find($post->user_id);
        $user = User::select('id', 'name', 'username', 'image', 'location', 'verify')->where('id', $request->user_id)->first();
        $comment  = new Comment();

        $comment->post_id = $request->post_id;
        $comment->user_id = $request->user_id;
        $comment->comment = $request->comment;
        $comment->parent_id = $request->parent_id ?: 0;
        $comment->time = strtotime(date('Y-m-d H:i:s'));

        $comment->save();

        if ($post->type == 'post') {
            NewNotification::handle($other, $user->id, $post->id, 'has comment on your post', 'comment', 'social');
            if ($post->user_id != $request->user_id) {
                $tokens = UserDevice::where('user_id', $other->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
                FirebaseNotification::handle($tokens, $user->name . ' commented on your Post', 'New Comment', ['data_id' => $request->post_id, 'type' => 'post']);
            }
        }
        else{
            NewNotification::handle($other, $user->id, $post->id, 'has comment on your reel', 'comment', 'social');
            if ($post->user_id != $request->user_id) {
                $tokens = UserDevice::where('user_id', $other->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
                FirebaseNotification::handle($tokens, $user->name . ' commented on your Reel', 'New Comment', ['data_id' => $request->post_id, 'type' => 'reel']);
            } 
        }




        $total = Comment::where('post_id', $request->post_id)->count();


        $comment->user = $user;
        return response()->json([
            'status' => true,
            'action' =>  'Comment added',
            'total' => $total,
            'data' => $comment
        ]);
    }

    public function commentList($user_id, $post_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $post = Post::find($post_id);
        if ($post) {
            $blocked = Blocklist::where('user_id', $user_id)->pluck('block_id');
            $blocked1 = BlockList::where('block_id', $user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $comments = Comment::where('post_id', $post->id)->where('parent_id', 0)->whereNotIn('user_id', $blocked)->paginate(12);

            foreach ($comments as $comment) {
                $user = User::select('id', 'name', 'username', 'image', 'location', 'verify')->where('id', $comment->user_id)->first();
                $likes = CommentLike::where('comment_id', $comment->id)->whereNotIn('user_id', $blocked)->count();
                $replies = Comment::where('parent_id', $comment->id)->whereNotIn('user_id', $blocked)->count();
                $comment->likes = $likes;
                $comment->replies = $replies;
                $comment->user = $user;

                $likestatus = CommentLike::where('comment_id', $comment->id)->where('user_id', $user_id)->first();

                if ($likestatus) {
                    $comment->is_liked = true;
                } else {
                    $comment->is_liked = false;
                }
            }
            $total = Comment::where('post_id', $post_id)->whereNotIn('user_id', $blocked)->count();
            return response()->json([
                'status' => true,
                'action' =>  "Comments",
                'total' => $total,
                'data' => $comments
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No post found",
        ]);
    }

    public function savePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $check = SavedPost::where('post_id', $request->post_id)->where('user_id', $request->user_id)->first();
        if ($check) {
            $check->delete();
            return response()->json([
                'status' => true,
                'action' =>  'Post unsaved',
            ]);
        }
        $like  = new SavedPost();
        $like->post_id = $request->post_id;
        $like->user_id = $request->user_id;
        $like->save();

        return response()->json([
            'status' => true,
            'action' =>  'Post saved',
        ]);
    }
    public function detailPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }
        $blocked = Blocklist::where('user_id', $request->user_id)->pluck('block_id');
        $blocked1 = Blocklist::where('block_id', $request->user_id)->pluck('user_id');
        $blocked = $blocked->merge($blocked1);

        $post = Post::find($request->post_id);
        if ($post) {
            $post->user =  User::select('id', 'name', 'username', 'image', 'location', 'verify',)->where('id', $post->user_id)->first();

            $comment = Comment::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
            $like = Like::where('post_id', $post->id)->whereNotIn('user_id', $blocked)->count();
            $likestatus = Like::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

            $saved = SavedPost::where('post_id', $post->id)->where('user_id', $request->user_id)->first();

            if ($post->media_type == 'image') {
                $data_media = explode(",", $post->media);
                $post->media = $data_media;
            }

            $likeList = Like::where('post_id', $post->id)->limit(3)->whereNotIn('id', $blocked)->pluck('user_id');
            $likeUsers = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likeList)->get();


            $follow = Follow::where('from_id', $request->user_id)->where('to_id', $post->user->id)->first();
            if ($follow) {
                $post->user->is_follow = true;
            } else {
                $post->user->is_follow = false;
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


            return response()->json([
                'status' => true,
                'action' =>  'Post Detail',
                'data' => $post
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'Post not found',
        ]);
    }

    public function commentLike($user_id, $comment_id)
    {

        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $comment = Comment::find($comment_id);
        if ($comment) {
            $check = CommentLike::where('user_id', $user_id)->where('comment_id', $comment_id)->first();
            if ($check) {
                $check->delete();
                return response()->json([
                    'status' => true,
                    'action' =>  'Comment Unlike',
                ]);
            }

            $create = new CommentLike();
            $create->user_id = $user_id;
            $create->comment_id = $comment_id;
            $create->save();

            $user = User::find($comment->user_id);
            $other = User::find($user_id);
            NewNotification::handle($user, $other->id, $comment_id, 'has like  your comment', 'New Like', 'social');
            if ($comment->user_id != $user_id) {
                $tokens = UserDevice::where('user_id', $user->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
                FirebaseNotification::handle($tokens, $other->name . 'has like  your comment', 'New Like', ['data_id' => $comment_id, 'type' => 'comment']);
            }


            return response()->json([
                'status' => true,
                'action' =>  'Comment Like',
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No Comment Found',
        ]);
    }

    public function commentReplies($user_id, $comment_id)
    {
        $blocked = Blocklist::where('user_id', $user_id)->pluck('block_id');
        $blocked1 = BlockList::where('block_id', $user_id)->pluck('user_id');
        $blocked = $blocked->merge($blocked1);

        $comments = Comment::where('parent_id', $comment_id)->whereNotIn('user_id', $blocked)->get();
        foreach ($comments as $comment) {
            $user = User::select('id', 'name', 'username', 'image', 'location', 'verify')->where('id', $comment->user_id)->first();
            $likes = CommentLike::where('comment_id', $comment->id)->whereNotIn('user_id', $blocked)->count();
            // $replies = Comment::where('parent_id', $comment->id)->count();
            $comment->likes = $likes;
            $comment->replies = 0;
            $comment->user = $user;
            $likestatus = CommentLike::where('comment_id', $comment->id)->where('user_id', $user_id)->first();

            if ($likestatus) {
                $comment->is_liked = true;
            } else {
                $comment->is_liked = false;
            }
        }

        $comment = Comment::find($comment_id);

        $total = Comment::where('post_id', $comment->post_id)->whereNotIn('user_id', $blocked)->count();
        // $total = Comment::where('parent_id', $comment_id)->whereNotIn('user_id', $blocked)->count();


        return response()->json([
            'status' => true,
            'action' =>  "Comments",
            'total' => $total,
            'data' => $comments
        ]);
    }
    public function commentLikeList($user_id, $comment_id)
    {
        $blocked = Blocklist::where('user_id', $user_id)->pluck('block_id');
        $blocked1 = BlockList::where('block_id', $user_id)->pluck('user_id');
        $blocked = $blocked->merge($blocked1);


        $comment = Comment::find($comment_id);
        if ($comment) {
            $likes = CommentLike::where('comment_id', $comment_id)->whereNotIn('user_id', $blocked)->get();
            foreach ($likes as $like) {
                $user = User::select('id', 'name', 'username', 'image', 'location', 'verify')->where('id', $like->user_id)->first();
                $like->user = $user;
            }
            return response()->json([
                'status' => true,
                'action' =>  "Comments",
                'data' => $likes
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No Comment Found",
        ]);
    }

    public function commentEdit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required|exists:comments,id',
            'comment' => 'required',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }

        $comment = Comment::find($request->comment_id);
        if ($comment) {
            $comment->comment = $request->comment;
            $comment->save();
            return response()->json([
                'status' => true,
                'action' =>  'Comment Edit',
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No Commnet Found',
        ]);
    }

    public function deleteComment($comment_id)
    {
        $comment = Comment::find($comment_id);
        if ($comment) {
            $findComment = Comment::find($comment_id);
            $comment->delete();
            $total = Comment::where('post_id', $findComment->post_id)->count();

            return response()->json([
                'status' => true,
                'total' => $total,
                'action' =>  'Comment Delete',
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  'No Commnet Found',
        ]);
    }
}
