<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\ImageVerify;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
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
        $this->reportedReel = 0 ;
        $this->reportedStory = 0;
        $this->activeTicket = Ticket::where('status', 1)->count();
    }
    public function index()
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;
        $verify = User::where('verify', 1)->count();

        $total = User::count();
        $todayActive = 0;

        $todayNew = User::whereDate('created_at', date('Y-m-d'))->count();
        $mainUsers = User::pluck('id');
        $loggedIn = UserDevice::whereIn('user_id', $mainUsers)->where('token', '!=', '')->distinct('user_id')->count();

        $iosTraffic = UserDevice::whereIn('user_id', $mainUsers)->where('device_name', 'ios')->count();
        $androidTraffic = UserDevice::whereIn('user_id', $mainUsers)->where('device_name', 'android')->count();
       

        return view('index', compact('todayActive', 'verify', 'total', 'todayNew', 'mainUsers', 'loggedIn', 'iosTraffic', 'androidTraffic','verifyCount','reportedUser','reportedReel','reportedPost','activeTicket','reportedStory'));
    }


    public function users(Request $request)
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        $users = User::latest()->paginate(20);
        if ($request->ajax()) {
            $query = $request->input('query');

            $users  = User::query();
            if ($query) {

                $users = $users->where('name', 'like', '%' . $query . '%')->orWhere('username', 'like', '%' . $query . '%');
            }
            $users = $users->latest()->Paginate(20);
            return view('user.user-ajax', compact('users','verifyCount','reportedUser','reportedReel','reportedPost','activeTicket','reportedStory'));
        }

        return view('user.index', compact('users','verifyCount','reportedUser','reportedReel','reportedPost','activeTicket','reportedStory'));
    }
    public function exportCSV(Request $request)
    {
       
        $users = User::select('name', 'email', 'username', 'verify')->get();

        $columns = ['name', 'email', 'username', 'verify'];
        $handle = fopen(storage_path('users.csv'), 'w');

        fputcsv($handle, $columns);

        foreach ($users->chunk(2000) as $chunk) {
            foreach ($chunk as $user) {
                fputcsv($handle, $user->toArray());
            }
        }

        fclose($handle);

        return response()->download(storage_path('users.csv'))->deleteFileAfterSend(true);
    }


    public function verifyUsers(Request $request)
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        $users = User::where('verify', 2)->latest()->paginate(20);
        foreach ($users as $user) {
            $image = ImageVerify::where('user_id', $user->id)->latest()->first();
            $user->userimage = $image;
        }

        // if ($request->ajax()) {
        //     $query = $request->input('query');

        //     $users  = User::query();
        //     if ($query) {
        //         $users = $users->where('name', 'like', '%' . $query . '%')->orWhere('username', 'like', '%' . $query . '%');
        //     }

        //     $users = $users->latest()->Paginate(20);
        //     foreach ($users as $user) {
        //         $image = ImageVerify::where('user_id', $user->id)->latest()->first();
        //         $user->userimage = $image;
        //     }
        //     return view('user.verify-ajax', compact('users'));
        // }
        return view('user.verifyuser', compact('users','verifyCount','reportedUser','reportedReel','reportedPost','activeTicket','reportedStory'));
    }

    public function getVerify($user_id)
    {
        
        $user = User::find($user_id);
        if ($user) {
            $user->verify = 1;
            $user->save();
            return redirect()->back()->with('success', 'User Verify Successfully');
        }
        return redirect()->back();
    }

    public function faqs()
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        $faqs = Faq::all();

        return view('faq', compact('faqs','verifyCount','reportedUser','reportedReel','reportedPost','activeTicket','reportedStory'));
    }

    public function deleteFaq($id)
    {
       
        $faq  = Faq::find($id);
        $faq->delete();
        return redirect()->back()->with('delete', 'FAQ Deleted');
    }

    public function addFaq(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'answer' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $faq = new Faq();
        $faq->question = $request->question;
        $faq->answer = $request->answer;
        $faq->save();
        return redirect()->back()->with('success', 'FAQ  Added Successfully');
    }

   
}
