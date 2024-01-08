<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Report;
use App\Models\Story;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    protected $verifyCount;
    protected $reportedUser;
    protected $reportedReel;
    protected $reportedPost;
    protected $reportedStory;
    protected $activeTicket;

    public function __construct()
    {
        $this->verifyCount = User::where('verify', 2)->count();
        $this->reportedUser = 0;
        $this->reportedPost = 0;
        $this->reportedReel = 0;
        $this->reportedStory = 0;
        $this->activeTicket = Ticket::where('status', 1)->count();
    }


    public function report($type)
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        if ($type == 'user') {

            $reports = Report::where('type', 'user')->paginate('100');

            foreach ($reports as $item) {
                $user = User::find($item->user_id);
                $reported_user = User::find($item->reported_id);
                $item->user = $user;
                $item->reported_user = $reported_user;
            }

            return view('report.user', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory', 'reports'));
        }
        if ($type == 'reels') {

            $reports = Report::where('type', 'reel')->paginate('100');

            foreach ($reports as $item) {
                $user = User::find($item->user_id);
                $item->user = $user;
                $reel = Post::where('type', 'reel')->where('id', $item->reported_id)->first();
                $item->reported_reel = $reel;
            }
            return view('report.reels', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory', 'reports'));
        }
        if ($type == 'posts') {

            $reports = Report::where('type', 'post')->paginate('100');

            foreach ($reports as $item) {
                $user = User::find($item->user_id);
                $item->user = $user;
                $post = Post::where('type', 'post')->where('id', $item->reported_id)->first();
                $media = explode(',', $post->media);
                $post->media = $media;
                $item->post = $post;
            }

            return view('report.post', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory', 'reports'));
        }
        if ($type == 'stories') {

            $reports = Report::where('type', 'story')->paginate('100');

            foreach ($reports as $item) {
                $user = User::find($item->user_id);
                $item->user = $user;
                $story = Story::find($item->reported_id);
                $item->reported_story = $story;
            }
            return view('report.story', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory', 'reports'));
        }
    }

    public function deleteReport($id)
    {

        $find = Report::find($id);
        $find->delete();
        return redirect()->back();
    }

    public function deleteUser($user_id, $report_id)
    {
        $find = User::find($user_id);
        Report::where('type', 'user')->where('report_id', $user_id)->delete();
        $find->delete();
        return redirect()->back();
    }

    public function deletePost($post_id, $report_id)
    {
        $find = Post::find($post_id);
        Report::where('type', 'post')->where('report_id', $post_id)->delete();
        $find->delete();
        return redirect()->back();
    }
    public function deleteReel($post_id, $report_id)
    {
        $find = Post::find($post_id);
        Report::where('type', 'reel')->where('report_id', $post_id)->delete();
        $find->delete();
        return redirect()->back();
    }

    public function deleteStory($stroy_id, $report_id)
    {
        $find = Story::find($stroy_id);
        Report::where('type', 'story')->where('report_id', $stroy_id)->delete();
        $find->delete();
        return redirect()->back();
    }

    // public function users()
    // {
    //     $verifyCount = $this->verifyCount;
    //     $reportedUser = $this->reportedUser;
    //     $reportedReel = $this->reportedReel;
    //     $reportedPost = $this->reportedPost;
    //     $activeTicket = $this->activeTicket;
    //     $reportedStory = $this->reportedStory;

    //     return view('report.user', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory'));
    // }
    // public function reels()
    // {
    //     $verifyCount = $this->verifyCount;
    //     $reportedUser = $this->reportedUser;
    //     $reportedReel = $this->reportedReel;
    //     $reportedPost = $this->reportedPost;
    //     $reportedStory = $this->reportedStory;

    //     $activeTicket = $this->activeTicket;
    //     return view('report.reels', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory'));
    // }
    // public function posts()
    // {
    //     $verifyCount = $this->verifyCount;
    //     $reportedUser = $this->reportedUser;
    //     $reportedReel = $this->reportedReel;
    //     $reportedPost = $this->reportedPost;
    //     $reportedStory = $this->reportedStory;

    //     $activeTicket = $this->activeTicket;
    //     return view('report.post', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory'));
    // }
    // public function stories()
    // {
    //     $verifyCount = $this->verifyCount;
    //     $reportedUser = $this->reportedUser;
    //     $reportedReel = $this->reportedReel;
    //     $reportedPost = $this->reportedPost;
    //     $activeTicket = $this->activeTicket;
    //     $reportedStory = $this->reportedStory;

    //     return view('report.story', compact('verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket', 'reportedStory'));
    // }
}
