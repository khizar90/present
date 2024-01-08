<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\Request;
use stdClass;

class AdminTicketController extends Controller
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
    public function getCategory()
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        $categories = TicketCategory::where('status', 0)->get();
        return view('ticket.category', compact('categories', 'verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket','reportedStory'));
    }

    public function deleteCategory($id)
    {
        $category = TicketCategory::find($id);
        $category->status = 1;
        $category->save();
        return redirect()->back()->with('delete', 'Category  Deleted');
    }

    public function addCategory(Request $request)
    {
        $category = new TicketCategory();
        $category->name = $request->name;
        $category->save();
        return redirect()->back()->with('success', 'Category  Added Successfully');
    }


    public function ticket($status)
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;


        if ($status == 'active') {
            $reports = Ticket::where('status', 1)->get();

            foreach ($reports as $report) {
                $user = User::find($report->user_id);
                $category = TicketCategory::find($report->category_id);
                $report->user = $user;
                $report->category = $category;
            }
        } else {
            $reports = Ticket::where('status', 0)->get();

            foreach ($reports as $report) {
                $user = User::find($report->user_id);
                $category = TicketCategory::find($report->category_id);

                $report->user = $user;
                $report->category = $category;
            }
        }
        return view('ticket.index', compact('reports', 'status', 'verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket','reportedStory'));
    }

    public function messages($ticket_id)
    {
        $verifyCount = $this->verifyCount;
        $reportedUser = $this->reportedUser;
        $reportedReel = $this->reportedReel;
        $reportedPost = $this->reportedPost;
        $activeTicket = $this->activeTicket;
        $reportedStory = $this->reportedStory;

        // $ids = explode('-', $from_to);
        // $from = $ids[0]; 
        // $to = $ids[1];

        $conversation = Message::where('ticket_id', $ticket_id)
            ->orderBy('created_at', 'asc')
            ->get();

        $ticket = Ticket::find($ticket_id);


        $findUser = User::find($ticket->user_id)->first();
        $cat = TicketCategory::find($ticket->category_id);
        // $channelName = $from_to;





        return view('ticket.show', compact('conversation', 'findUser', 'cat', 'ticket', 'verifyCount', 'reportedUser', 'reportedReel', 'reportedPost', 'activeTicket','reportedStory'));
    }

    public function closeTicket($report_id)
    {
        $obj = new stdClass();
        $report = Ticket::find($report_id);
        if ($report) {
            $report->status = 0;
            $report->save();
            return redirect()->route('dashboard-ticket-ticket', 'active');
        }
    }


    public function sendMessage(Request $request)
    {
        $message = new Message();
        $message->ticket_id = $request->ticket_id;
        $message->to = $request->user_id;
        $message->from = 0;
        $message->message = $request->message;
        $message->type = 'text';
        $message->time = strtotime(date('Y-m-d H:i:s'));

        $message->save();
        return response()->json($message);
    }
}
