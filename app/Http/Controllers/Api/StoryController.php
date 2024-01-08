<?php

namespace App\Http\Controllers\Api;

use App\Actions\FirebaseNotification;
use App\Actions\NewNotification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Story\StoryRequest;
use App\Models\BlockList;
use App\Models\Notification;
use App\Models\Story;
use App\Models\StoryLike;
use App\Models\StoryView;
use App\Models\StroyView;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public function add(StoryRequest $request)
    {
        $file = $request->file('media');


        $extension = $file->getClientOriginalExtension();
        $mime = explode('/', $file->getClientMimeType());
        $filename = time() . '-' . uniqid() . '.' . $extension;
        if ($file->move('uploads/user/' . $request->user_id . '/story/', $filename)) {
            $imagePaths = '/uploads/user/' . $request->user_id . '/story/' . $filename;
        }





        $create = new Story();

        if ($request->hasFile('music_media')) {
            $file = $request->file('music_media');
            $extension = $file->getClientOriginalExtension();
            $mime = explode('/', $file->getClientMimeType());
            $filename = time() . '-' . uniqid() . '.' . $extension;
            if ($file->move('uploads/user/' . $request->user_id . '/story/music/', $filename))
                $music = '/uploads/user/' . $request->user_id . '/story/music/' . $filename;
            $create->music_media = $music;
        }

        $create->user_id = $request->user_id;
        $create->media = $imagePaths;
        $create->is_public = $request->is_public;
        $create->time = strtotime(date('Y-m-d H:i:s'));
        $create->save();

        return response()->json([
            'status' => true,
            'action' => "Story Added"
        ]);
    }
    public function delete($id)
    {
        $find = Story::find($id);
        if ($find) {


            // Delete each file corresponding to the extracted paths
            // Assuming $url contains the absolute path of each file
            $filePath = public_path($find->media);

            // Check if the file exists before attempting deletion
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
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
                'action' => "Story Deleted"
            ]);
        }
        return response()->json([
            'status' => false,
            'action' => "No Story found"
        ]);
    }

    public function like(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'user_id' => 'required|exists:users,id',
        ]);
        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        }
        $post = Story::find($request->story_id);
        $other = User::find($request->user_id);
        $user = User::find($post->user_id);

        $check = StoryLike::where('story_id', $request->story_id)->where('user_id', $request->user_id)->first();
        if ($check) {
            $check->delete();
            Notification::where('data_id', $request->story_id)->where('notification_type', 'social')->where('type', 'like')->where('person_id', $request->user_id)->delete();
            return response()->json([
                'status' => true,
                'action' =>  'Story like remove',
            ]);
        }



        $like  = new StoryLike();
        $like->story_id = $request->story_id;
        $like->user_id = $request->user_id;
        $like->save();

        NewNotification::handle($user, $other->id, $post->id, 'has liked your story', 'like', 'social');

        if ($post->user_id != $request->user_id) {
            $tokens = UserDevice::where('user_id', $user->id)->where('token', '!=', '')->groupBy('token')->pluck('token')->toArray();
            FirebaseNotification::handle($tokens, $other->name . ' has Liked your Story', 'New Like', ['data_id' => $request->story_id, 'type' => 'story']);
        }

        return response()->json([
            'status' => true,
            'action' =>  'Story like',
        ]);
    }

    public function likeList($user_id, $story_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $post = Story::find($story_id);
        if ($post) {


            $blocked = BlockList::where('user_id', $user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $likes = StoryLike::where('story_id', $story_id)->whereNotIn('user_id', $blocked)->pluck('user_id');
            $users = User::select('id', 'name','username', 'image', 'location', 'verify')->whereIn('id', $likes)->Paginate(10);

            return response()->json([
                'status' => true,
                'action' =>  "Users",
                'data' => $users
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No Story found",
        ]);
    }

    public function view($user_id, $story_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $story = Story::find($story_id);
        if ($story) {
            $check = StoryView::where('story_id', $story_id)->where('user_id', $user_id)->first();
            if ($check) {
                return response()->json([
                    'status' => true,
                    'action' =>  "Story scene",
                ]);
            }

            $create = new  StoryView();
            $create->user_id = $user_id;
            $create->story_id = $story_id;
            $create->save();
            return response()->json([
                'status' => true,
                'action' =>  "Story scene",
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No Story found",
        ]);
    }

    public function viewList($user_id, $story_id)
    {
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'status' => false,
                'action' =>  'No User Found',
            ]);
        }
        $post = Story::find($story_id);
        if ($post) {
            $blocked = BlockList::where('user_id', $user_id)->pluck('block_id');
            $blocked1 = Blocklist::where('block_id', $user_id)->pluck('user_id');
            $blocked = $blocked->merge($blocked1);

            $likes = StoryView::where('story_id', $story_id)->whereNotIn('user_id', $blocked)->pluck('user_id');
            $users = User::select('id', 'name', 'username', 'image', 'location', 'verify')->whereIn('id', $likes)->Paginate(10);

            return response()->json([
                'status' => true,
                'action' =>  "Users",
                'data' => $users
            ]);
        }
        return response()->json([
            'status' => false,
            'action' =>  "No Story found",
        ]);
    }
}
