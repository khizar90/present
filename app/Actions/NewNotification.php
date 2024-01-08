<?php

namespace App\Actions;

use App\Models\Notification;

class NewNotification
{
    public static function handle($user,$other,$post, $body, $type, $notificationType){

        $notification = new Notification();

        $notification->user_id = $user->id;
        $notification->person_id = $other;
        $notification->body = $body;
        $notification->type = $type;
        $notification->data_id = $post;
        $notification->notification_type = $notificationType;
        $notification->date = date('Y-m-d');
        $notification->time = strtotime(date('Y-m-d H:i:s'));
        $notification->save();
        return true;

    }
}