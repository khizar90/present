<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use stdClass;

class TicketController extends Controller
{
    public function addTicket(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:ticket_categories,id',
            'message' => 'required'
        ]);

        $errorMessage = implode(', ', $validator->errors()->all());

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'action' =>  $errorMessage,
            ]);
        } else {
            $ticket = new Ticket();
            $ticket->category_id = $request->category_id;
            $ticket->user_id = $request->user_id;
            $ticket->message = $request->message;
            $ticket->time = strtotime(date('Y-m-d H:i:s'));
            $ticket->save();

            $message = new Message();
            $message->from = $request->user_id;
            $message->type = 'text';
            $message->to = 0;
            $message->ticket_id = $ticket->id;
            $message->message = $request->message;
            $message->time = strtotime(date('Y-m-d H:i:s'));
            $message->save();


            $defaultMessage = new Message();
            $defaultMessage->ticket_id = $ticket->id;
            $defaultMessage->to = $request->user_id;
            $defaultMessage->type = 'text';
            $defaultMessage->from = 0;
            $defaultMessage->message = 'Hi,👋Thanks for your message. We ll get back to you within 24 hours.';
            $defaultMessage->time = strtotime(date('Y-m-d H:i:s'));
            $defaultMessage->save();


            $user = User::find($request->user_id);
            $cat = TicketCategory::find($request->category_id);

            $newticket = Ticket::find($ticket->id);
            $cat = TicketCategory::find($newticket->category_id);
            $newticket->category = $cat->name;
            // $karachiTime = Carbon::parse($Ticket->created_at)->timezone('Asia/Karachi');
            // $mail_details = [
            //     'subject' => 'Express',
            //     'body' => $request->message,
            //     'user' => $user->name,
            //     'category' => $cat->name,
            //     'time' => $karachiTime->format('Y-m-d H:i:s')
            // ];

            // Mail::to('khzrkhan0000@gmail.com')->send(new \App\Mail\TicketCreated());

            // Mail::to('zrzunair10@gmail.com')->send(new TicketCreated($mail_details));

            return response()->json([
                'status' => true,
                'action' => "Ticket Added",
                'data' =>  $newticket
            ]);
        }
    }

    public function list($user_id, $status)
    {
        $tickets = Ticket::where('user_id', $user_id)->where('status', $status)->latest()->paginate(12);
        foreach ($tickets as $ticket) {
            $category = TicketCategory::where('id', $ticket->category_id)->first();
            $ticket->category = $category->name;
        }
        return response()->json([
            'status' => true,
            'action' => "User Ticket",
            'data' => $tickets,
        ]);
    }


    public function closeTicket($Ticket_id)
    {
        $obj = new stdClass();
        $Ticket = Ticket::find($Ticket_id);
        if ($Ticket) {
            $Ticket->status = 0;
            $Ticket->save();
            return response()->json([
                'status' => true,
                'action' => "Ticket Close",
            ]);
        } else {
            return response()->json([
                'status' => false,
                'action' => "No Tickets found",
            ]);
        }
    }



    public function conversation($id)
    {
        $ticket = Ticket::find($id);
        if ($ticket) {
            $messages = Message::where('ticket_id', $id)->latest('id')->paginate(5000);
            $user = User::find($ticket->user_id);
            $category = TicketCategory::find($ticket->category_id);
            foreach ($messages as $message) {
                $message->user_name = $user->name;
                $message->user_image = $user->image;
                $message->category = $category->name;
            }
            return response()->json([
                'status' => true,
                'action' => "Conversation",
                'data' => $messages,
            ]);
        }
        return response()->json([
            'status' => false,
            'action' => "No Ticket found",
        ]);


        // $channelValues = explode('-', $from_to);
        // $user_id = $channelValues[0];
        // $ticket_id = $channelValues[1];

        // foreach ($messages as $message) {
        //     $user =  User::where('id', $user_id)->first();
        //     $message->user_name = $user->name;
        //     $message->user_image = $user->image;
        //     $category = TicketCategory::select('name')->where('id', $ticket_id)->first();
        //     $message->category = $category->name;
        // }


    }

   
}
