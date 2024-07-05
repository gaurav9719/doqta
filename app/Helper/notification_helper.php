<?php

use Pushok\AuthProvider;
use Pushok\Client;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserDevice;
use App\Jobs\Notification\NotificationJob;
function IosPush($devicetoken, $message, $type, $data, $mood_icon = '')
{
    $options = [
        'app_bundle_id' => 'com.Jubilant.Innerworkout', // The bundle ID for app obtained from Apple developer account
        'certificate_path' => './notification_pem/IWPush.pem', // Path to private key
        'certificate_secret' => null // Private key secret
    ];
    // storage_path('app/notification_pem/bossapp.pem');
    // Be aware of thing that Token will stale after one hour, so you should generate it again.
    // Can be useful when trying to send pushes during long-running tasks
    $authProvider = AuthProvider\Certificate::create($options);
    $alert = Alert::create()->setTitle('Doqta');
    $alert = $alert->setBody($message);
    $alert = $alert->setTitleLocKey($type);
    //	$alert = $alert->type($type);
    $payload = Payload::create()->setAlert($alert);
    //set notification sound to default
    $payload->setSound('default');
    //add custom value to your notification, needs to be customized
    //	$payload->setCustomValue('type', $type);
    // $payload->setCustomValue('data_payload', array("test1"=>1,"test2"=>2));
    $payload->setCustomValue('data_payload', $data);

    // $payload->setCustomValue('mood_icon', $mood_icon);s
    $deviceTokens = [$devicetoken];
    $notifications = [];
    //print_r($payload);die;
    foreach ($deviceTokens as $deviceToken) {

        $notifications[] = new Notification($payload, $deviceToken);
    }
    // If you have issues with ssl-verification, you can temporarily disable it. Please see attached note.
    // Disable ssl verification
    //$client = new Client($authProvider, $production = false, [CURLOPT_SSL_VERIFYPEER=>false] );
    $client = new Client($authProvider, $production = TRUE);
    $client->addNotifications($notifications);
    $responses = $client->push(); // returns an array of ApnsResponseInterface (one Response per Notification)
    if ($responses[0]->getStatusCode() == 200) {
        return TRUE;
        //echo "success";die;
    }
    //echo "failed";
    return FALSE;
    /*foreach ($responses as $response) {
        // The device token
        //echo $response->getDeviceToken();
        
        // A canonical UUID that is the unique ID for the notification. E.g. 123e4567-e89b-12d3-a456-4266554400a0
        echo $response->getApnsId();
        
         echo '<br>';
        // Status code. E.g. 200 (Success), 410 (The device token is no longer active for the topic.)
         echo $response->getStatusCode();
         
         echo '<br>';
        // E.g. The device token is no longer active for the topic.
        echo $response->getReasonPhrase();
        echo '<br>';
        // E.g. Unregistered
        echo $response->getErrorReason();
        echo '<br>';
        // E.g. The device token is inactive for the specified topic.
        echo $response->getErrorDescription();
        echo '<br>';
        echo $response->get410Timestamp();
    }
    */
}

//Android push notification with new code
function androidPush($token, $userdetail, $section, $message)
{
    $url        =           "https://fcm.googleapis.com/fcm/send";
    $serverKey  =           'AAAAm8kvcjU:APA91bHGpFtlgquo5U2_gY337JV5mVXeEHno0UYnO60qbBubtIZxGN29X66yICT-5xi1OK8AB1IMY-X4IGNafrcOuob-0mCIwZCHePlaRDUQAE9TZitBEfwE_nYNVLh3UWh91zo3olMl';

    $arr        =           array(
        'registration_ids' => array($token),
        'notification'  => array('sound' => 'default', 'title' => "Tracknic", 'body' => $message),
        'data' => array('sound' => 'default', 'title' => 'Tracknic', 'body' => $message)
    );
    // $json = json_encode($arr);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key=' . $serverKey;
    $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);

    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST,

    // "POST");
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));

    //Send the request
    //$result = curl_exec($ch);
    curl_exec($ch);
    //Close request
    // if ($response === FALSE) {
    // die('FCM Send Error: ' . curl_error($ch));
    // }
    curl_close($ch);
    //return $res = json_decode($result,true);
}

function androidPushNotification($token, $message, $section, $data)
{
    $url        =           "https://fcm.googleapis.com/fcm/send";
    $serverKey  =           'AAAAf3Y7gAI:APA91bGfe3zHvYvhs9E77406BH6bjemJ0HUrj-Ak4SzH1tsY1FYWrj1dtsP7GepNgMg_rH1oF6mdodesqYqpi6SQJbHEdE6-U9a2NB1_BpdgiLvn516UwnY0AzDdi2WdzcYJ-VYeM-1q';

    if (isset($data) && !empty($data)) {
        $notification_data      =       array('sound' => 'default', 'title' => 'BossApp', 'body' => $message, 'id' => $data->id, 'user_id' => $data->user_id, 'by_user_id' => $data->by_user_id, 'task_id' => $data->task_id, 'activity_id' => $data->activity_id, 'issue_id' => $data->issue_id, 'appointment_id' => $data->appointment_id, 'notification_message' => $data->notification_message, 'type' => $data->type);
    } else {
        $notification_data      =       array('sound' => 'default', 'title' => 'BossApp', 'body' => $message, 'id' => 0, 'user_id' => 0, 'by_user_id' => 0, 'task_id' => 0, 'activity_id' => 0, 'issue_id' => 0, 'appointment_id' => 0, 'notification_message' => "", 'type' => 0);
    }

    // $arr        = array(
    //    'registration_ids' => array($token),
    //   'notification'  => array('sound' => 'default','title' => 'BossApp','body' =>$message,'id'=>$data->id,'user_id'=>$data->user_id,'by_user_id'=>$data->by_user_id,'task_id'=>$data->task_id,'activity_id'=>$data->activity_id,'issue_id'=>$data->issue_id,'appointment_id'=>$data->appointment_id,'notification_message'=>$data->notification_message,'type'=>$data->type),

    //   'data' => array( 'sound' => 'default','title' => 'BossApp','body' =>$message,'id'=>$data->id,'user_id'=>$data->user_id,'by_user_id'=>$data->by_user_id,'task_id'=>$data->task_id,'activity_id'=>$data->activity_id,'issue_id'=>$data->issue_id,'appointment_id'=>$data->appointment_id,'notification_message'=>$data->notification_message,'type'=>$data->type)
    //  );
    $arr        = array('registration_ids' =>  $notification_data, 'data' =>  $notification_data);
    // $json = json_encode($arr);
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Authorization: key=' . $serverKey;
    $ch = curl_init();
    // curl_setopt($ch, CURLOPT_URL, $url);

    // curl_setopt($ch, CURLOPT_CUSTOMREQUEST,

    // "POST");
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    // curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arr));

    //Send the request
    //$result = curl_exec($ch);
    curl_exec($ch);
    //Close request
    // if ($response === FALSE) {
    // die('FCM Send Error: ' . curl_error($ch));
    // }
    curl_close($ch);
    //return $res = json_decode($result,true);
}



if (!function_exists('sendPushNotification')) {
    function sendPushNotification($userid, $message_type, $message)
    {

    //    / dispatch(new NotificationJob($userid,$message_type,$message));
          



        $userData           =   User::select('id', 'first_name', 'device_type', 'device_token', 'profile_pic')->where(['id' => $userid])->first();
        if (isset($userData) && !empty($userData)) {

            if (isset($userData['device_token']) && !empty($userData['device_token'])) {

                if ($userData['device_type'] == 1) {        // call ios function

                    IosPush($userData['device_token'], $message, $message_type, [], $mood_icon = '');
                } elseif ($userData['device_type'] == 2) {     // call andriod function

                    //androidPushNotification($userData['device_token'],$message,$message_type,[]);
                }
            }
        }
    }
}



if (!function_exists('sendPushNotificationNew')) {
    function sendPushNotificationNew($sender, $userData, $notification)
    {
        //dispatch(new NotificationJob($sender, $userData, $notification));

        $userDevices     =   UserDevice::where(['user_id' => $userData['id']])->get();

        if (isset($userDevices) && !empty($userDevices[0])) {

            foreach ($userDevices as $userDevice) {

                if (isset($userDevice['device_token']) && !empty($userDevice['device_token'])) {

                    if ($userDevice['device_type'] == 1) {        // call ios function

                        IosPush($userData['device_token'], $notification['message'], $notification['notification_type'], $notification, $mood_icon = '');
                    } elseif ($userDevice['device_type'] == 2) {     // call andriod function

                        //  androidPushNotification($userData['device_token'] ,$notification['message'], $notification['notification_type'], $notification);

                    }
                }
            }
        }
    }
}

if (!function_exists('ChatNotification')) {
    function ChatNotification($sender_id,$notification_data)
    {
          //dispatch(new NotificationJob($sender_id,$notification_data));

        // $userDevices     =   UserDevice::where(['user_id' => $userData['id']])->get();

        // if (isset($userDevices) && !empty($userDevices[0])) {

        //     foreach ($userDevices as $userDevice) {

        //         if (isset($userDevice['device_token']) && !empty($userDevice['device_token'])) {

        //             if ($userDevice['device_type'] == 1) {        // call ios function

        //                 IosPush($userData['device_token'], $notification['message'], $notification['notification_type'], $notification, $mood_icon = '');
        //             } elseif ($userDevice['device_type'] == 2) {     // call andriod function

        //                 //  androidPushNotification($userData['device_token'] ,$notification['message'], $notification['notification_type'], $notification);

        //             }
        //         }
        //     }
        // }
    }
}




