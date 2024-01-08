<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\TicketCategory;
use App\Models\User;
use Illuminate\Http\Request;
use stdClass;

class SettingController extends Controller
{
    public function splash()
    {
        $obj = new stdClass();
        $ticket = TicketCategory::select('id', 'name')->where('status', 0)->get();
        // $donation = DonationCategory::select('id','name')->get();
        // $consultation = ConsultationCategory::select('id','name')->get();
        // $dua = DuaCategory::pluck('name');
        $obj->ticket_category = $ticket;
        // $obj->donation_category = $donation;
        // $obj->consultation = $consultation;
        // $obj->dua_category = $dua;
        return response()->json([
            'status' => true,
            'action' => "Splash",
            'data' => $obj,
        ]);
    }

    public function faqs()
    {
        $faqs  = Faq::all();
        return response()->json([
            'status' => true,
            'action' =>  "Faqs",
            'data' =>  $faqs,
        ]);
    }
    public function user($id)
    {
        $user =  User::find($id);
        if ($user) {
            return response()->json([
                'status' => true,
                'action' =>  "User",
                'data' =>  $user,
            ]);

        }
        else{
            return response()->json([
                'status' => false,
                'action' =>  "User not found",
            ]);
        }
    }
}
