<?php

namespace App\Actions;


class FirebaseNotification
{
    public static function handle($tokens,$body,$title,$arr)
    {



        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';

        $notification = [
            'title' => $title,
            'body' => $body,
        ];

        $extraNotificationData = $arr;

        $fcmNotification = [
            'registration_ids'        => $tokens, //single token
            'notification' => $notification,
            'data' => $extraNotificationData
        ];

        $headers = [
            'Authorization: key= AAAAq-fft50:APA91bHUsop6tLqyRy25gFkbQMlM5cfj-3pZWoZpimhm9Uzg4wqpbFr_lRoNXHa2EQ8MQiUFAw-xM2hd3FDhfHg1_TFWiZ4PLOILfXT2Ptva_L8UnJRlRkRiogywRLLdJ15kctdoVm9F',
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fcmUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fcmNotification));
        $result = curl_exec($ch);
        curl_close($ch);
        // dd($result);
        return $result;



    }
}