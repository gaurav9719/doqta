<?php

use App\Models\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Models\UserRole;
use Illuminate\Support\Str;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Eye\SquareEye;
use BaconQrCode\Renderer\Module\SquareModule;
use BaconQrCode\Renderer\RendererStyle\EyeFill;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\Gradient;
use BaconQrCode\Renderer\RendererStyle\GradientType;
use PHPUnit\Framework\TestCase;
use Spatie\Snapshots\MatchesSnapshots;
use App\Models\Group;
use BaconQrCode\Renderer\RendererStyle\Gradient\HorizontalGradient;
function getErrorAsStringsasa($messagearr)
{
    $message = '';
    if (is_string($messagearr)) {
        return $messagearr;
    }
    $totalmsg = $messagearr->count();
    foreach ($messagearr->all() as $key => $value) {
        $message .= $key < $totalmsg - 1 ? $value . '/' : $value;
    }
    return $message;
}


if (!function_exists('getErrorAsString')) {
    function getErrorAsString($messagearr)
    {
        $message = '';
        if (is_string($messagearr)) {
            return $messagearr;
        }
        $totalmsg = $messagearr->count();
        foreach ($messagearr->all() as $key => $value) {
            $message .= $key < $totalmsg - 1 ? $value . '/' : $value;
        }
        return $message;
    }
}

function validationErrorsToString($errArray)
{
    $valArr = array();
    foreach ($errArray->toArray() as $key => $value) {
        //$errStr = $key;
        $errStr = $value[0];
        array_push($valArr, $errStr);
        break;
    }
    if (!empty($valArr)) {
        $errStrFinal = implode(',', $valArr);
    }
    return $errStrFinal;
}


function random_code()
{

    return rand(1111, 9999);
}


function allUpper($str)
{
    return strtoupper($str);
}

function image_upload($image, $path)
{
    //$destinationPath    =       "./images/";
    $year_folder        =       $path . date("Y");
    $month_folder       =       $year_folder . '/' . date("m");
    !file_exists($year_folder) && mkdir($year_folder, 0777);
    !file_exists($month_folder) && mkdir($month_folder, 0777);
    $destinationPath    =       $month_folder;
    $profileImage       =       date('YmdHis') . "." . $image->getClientOriginalExtension();
    $image->move($destinationPath, $profileImage);
    return $destinationPath . "/" . $profileImage;
}


function uploads($file, $path)
{

    if ($file) {
        $fileName   = time() . $file->getClientOriginalName();
        Storage::disk('public')->put($path . $fileName, File::get($file));
        $file_name  = $file->getClientOriginalName();
        $file_type  = $file->getClientOriginalExtension();
        return       $path . $fileName;
    }
}


function pictureUpload($file, $path)
{
    $fileName   = time() . $file->getClientOriginalName();
    Storage::disk('public')->put($path . $fileName, File::get($file));
    $file_name  = $file->getClientOriginalName();
    $file_type  = $file->getClientOriginalExtension();
    $filePath   = 'storage/' . $path . $fileName;
}

function generateReferenceCode($length = 20)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

## Get random password
function randomCode($length = 9, $add_dashes = false, $available_sets = 'param')
{
    $sets = array();
    if (strpos($available_sets, 'p') !== false)
        $sets[] = 'abcdefghjkmnpqrstuvwxyz';
    if (strpos($available_sets, 'a') !== false)
        $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
    if (strpos($available_sets, 'r') !== false)
        $sets[] = '23456789';

    // if(strpos($available_sets, 's') !== false)
    //     $sets[] = '@#$&*?';

    $all = '';
    $password = '';
    foreach ($sets as $set) {
        $password .= $set[array_rand(str_split($set))];
        $all .= $set;
    }

    $all = str_split($all);
    for ($i = 0; $i < $length - count($sets); $i++)
        $password .= $all[array_rand($all)];

    $password = str_shuffle($password);

    if (!$add_dashes)
        return $password;

    $dash_len = floor(sqrt($length));
    $dash_str = '';
    while (strlen($password) > $dash_len) {
        $dash_str .= substr($password, 0, $dash_len) . '-';
        $password = substr($password, $dash_len);
    }
    $dash_str .= $password;
    return $dash_str;
}




if (!function_exists('upload_file')) {
    function upload_file($file, $folder = "")
    {
        $currentYear    = date('Y');
        $currentMonth   = date('m');
        // $d              = date('d');
        // Create the directory path
        $directory = "uploads/{$folder}/{$currentYear}/{$currentMonth}";
        // Check if the directory exists, if not, create it
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory,0755, true); // Recursive create directory
        }
        // return Storage::disk('public')->put($directory, $file);
        $filePath = Storage::disk('public')->putFile($directory, $file);

        Storage::disk('public')->setVisibility($filePath, 'public');
        return $filePath;

    }
}

if (!function_exists('qr_code_generator')) {
    // function qr_code_generator($qr_code)
    // {
    //     $image              =        QrCode::format('png')->generate("https://rosterapp/match?u=" . $qr_code);
    //     $output_file        =       'qr_code-' . time() . '.png';
    //     $url                =       Storage::disk('public')->put($output_file, $image);     //storage/app/public/img/qr-code/img-1557309130.png
    //     $currentYear        =       date('Y');
    //     $currentMonth       =       date('m');
    //     // Create the directory path
    //     $directory = "uploads/qr/{$currentYear}/{$currentMonth}";
    //     // Check if the directory exists, if not, create it
    //     if (!Storage::disk('public')->exists($directory)) {
    //         Storage::disk('public')->makeDirectory($directory); // Recursive create directory
    //     }
    //     return Storage::disk('public')->put($directory, $image);
    //   //  return $output_file;
    // }

    function qr_code_generator($userid)
    {
        // Generate the QR code image
        $random     =   rand(1111,9999);
        $encryptdQr =   Crypt::encrypt($random.$userid);
        // $qr_image   =   QrCode::format('png')->size(300)->generate("https://rosterapp/match?u=" .$encryptdQr);

            // $qr_image =QrCode::format('svg')->size(399)->color(40,40,40)->generate('Make me a QrCode!');

            // $qr_image =QrCode::format('png')->generate("rosterapp/match?u=" ."dsdsdsd");

            // $qr_image   = QrCode::format('png')
            //     ->size(250)
            //     ->backgroundColor(255, 255, 255) // White background
            //     ->color(0, 0, 0) // Black foreground
            //     ->generate("https://rosterapp/match?u=" .$encryptdQr);
            
        $from = [0, 204, 204];
        $to = [0, 230, 230];
        $qr_image=QrCode::size(200)
        ->eye('circle')
        ->style('round')
        ->gradient($from[0], $from[1], $from[2], $to[0], $to[1], $to[2], 'diagonal')
        ->margin(1)
        // ->style('dot')
        // ->eyeColor(0, 19,137,131, 19,137,131)
        // ->eyeColor(1, 19,137,131, 19,137,131)
        // ->eyeColor(2, 19,137,131, 19,137,131)
        
        ->generate("https://rosterapp/match?u=" .$encryptdQr) ;

      



        // Define the directory path
        $currentYear = date('Y');
        $currentMonth = date('m');
        $directory = "uploads/qr/{$currentYear}/{$currentMonth}";
        // Store the QR code image directly into the storage disk
        $filename = $directory . '/' . Str::uuid() . '.svg';
        Storage::disk('public')->put($filename, $qr_image);
        // Return the path to the stored QR code image
        return $filename;
    }
}
if (!function_exists('getUtcToLocalTimeStamp')) {
    function getUtcToLocalTimeStamp($date, $timezone)
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        $date->setTimezone($timezone);
        return $date->toDateTimeString();
    }
}

if (!function_exists('saveNotificationToDb')) {
    function saveNotificationToDb($msg, $userId, $byUserId, $taskId, $activityId, $issueId, $appId, $type)
    {
        $notification = new Notification();

        $notification->user_id  =  $userId;
        $notification->by_user_id  = $byUserId;
        $notification->task_id  =  $taskId;
        $notification->issue_id  = $issueId;
        $notification->activity_id  = $activityId;
        $notification->appointment_id  = $appId;
        $notification->notification_message = $msg;
        $notification->type = $type;
        $notification->save();
    }
}

if (!function_exists('utcToLocalTimeDiff')) {
    function utcToLocalTimeDiff($from, $to, $targetFormat = "Y-m-d H:i:s")
    {
        // $from='UTC',$to='Asia/Hong_Kong',$targetFormat="Y-m-d H:i:s"
        $utc_tz       =       new DateTimeZone('utc');
        $utc          =       new DateTime('now', $utc_tz);
        $utc_dateTime =       $utc->format('Y-m-d H:i:s');


        $LocalDT        =       new DateTime($utc_dateTime, new DateTimeZone($from));
        $newLocalDateTime        =       $LocalDT->setTimeZone(new DateTimeZone($to));
        $LdateDTSring          =       $newLocalDateTime->format('Y-m-d H:i:s');
        // $interval = $local->diff($newTime);
        $datetime1      =       new DateTime($utc_dateTime);
        $datetime2      =       new DateTime($LdateDTSring);
        $interval       =       $datetime1->diff($datetime2);
        if ($LdateDTSring > $utc_dateTime) {
            return "+" . $interval->format('%H:%I');
            die;
        } else {
            return "-" . $interval->format('%H:%I');
            die;
        }
        // return  $interval->format('%H:%I');
    }
}



if (!function_exists('convertDateToAnotherTimeZone')) {

    function convertDateToAnotherTimeZone($date, $tz_from, $to_time_zone)
    {
        $newDateTime            =       new DateTime($date, new DateTimeZone($tz_from));
        $newDateTime->setTimezone(new DateTimeZone($to_time_zone));
        return  $newDateTime->format("Y-m-d");
    }
}

if (!function_exists('localToUtc')) {

    function localToUtc($datetime)
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $datetime);
        $date->setTimezone('UTC');
        return $date->format('Y-m-d H:i:s');
    }
}



if (!function_exists('localToUtcTime')) {

    function localToUtcTime($date, $tz_from, $tz_to, $format)
    {
        // $tz_from        =   'Asia/Kolkata';
        // $tz_to          =   'UTC';
        // $format         =   'Y-m-d H:i:s';
        $dt             =   new DateTime($date, new DateTimeZone($tz_from));
        $dt->setTimeZone(new DateTimeZone($tz_to));
        return  $dt->format($format);
    }
}



if (!function_exists('makeImageFromName')) {
    function makeImageFromName($name)
    {
        $userImage          =       "";
        $shortname          =       "";
        $names              =       explode(" ", $name);

        // foreach($names as $pic){
        //     $shortname.= $pic[0];
        // }

        $shortname = substr($name, 0, 1);
        $userImage          =   '<div class="name-image bg-warning" >' . $shortname . '</div>';
        return $userImage;
    }
}

if (!function_exists('getCreatedAtAttribute')) {

    function getCreatedAtAttribute($date, $timezone)
    {
        $date = Carbon::createFromFormat('Y-m-d H:i:s', $date, 'UTC');
        $date->setTimezone($timezone);
        return $date->toDateTimeString();
    }
}



if (!function_exists('getDistanceBetweenPoints')) {
    function getDistanceBetweenPoints($lat1, $lon1, $lat2, $lon2)
    {
        $theta = $lon1 - $lon2;
        $miles = (sin(deg2rad($lat1)) * sin(deg2rad($lat2))) + (cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)));
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;
        $feet = $miles * 5280;
        $yards = $feet / 3;
        $kilometers = $miles * 1.609344;
        if (round($kilometers, 2) < 1) {

            return  $meters = round($kilometers * 1000, 2) . " m";
        } else {
            return round($kilometers, 3) . " km";
        }
        // return compact('miles','feet','yards','kilometers','meters'); 
    }
}


if (!function_exists('add_time_to_another_time')) {
    function add_time_to_another_time($time, $time2)
    {
        $time2 = date("H:i:s", strtotime($time2));
        $secs = strtotime($time2) - strtotime("00:00:00");
        $result = date("H:i:s", strtotime($time) + $secs);
        return $result;
    }
}


if (!function_exists('point_to_hours')) {
    function point_to_hours($decimalHours,)
    {
        $hours = floor($decimalHours);
        $mins = round(($decimalHours - $hours) * 60);
        $timeInMinutes = ($hours * 60) + $mins;
        $hours = intdiv($timeInMinutes, 60) . ':' . ($timeInMinutes % 60);

        return $hours;
    }
}
// GET THE DIFFERENCE BETWEEN TWO TIMES
if (!function_exists('getTimeDiff')) {
    function getTimeDiff($t1, $t2)
    {
        $time1 = new DateTime($t1);
        $time2 = new DateTime($t2);
        $interval = $time1->diff($time2);
        return $interval->format('%H:%I');
    }
}

if (!function_exists('timeToPoint')) {
    function timeToPoint($time)
    {
        $hms = explode(":", $time);
        // return ($hms[0] + ($hms[1]/60) + ($hms[2]/3600));
        return ($hms[0] + ($hms[1] / 60));
    }
}

if (!function_exists('displayStars')) {
    function displayStars($rating)
    {
        $roundedRating = round($rating * 2) / 2; // Round to the nearest 0.5
        $fullStars = floor($roundedRating);
        $halfStar = ($roundedRating - $fullStars) >= 0.5 ? true : false;
        $emptyStars = 5 - ($fullStars + ($halfStar ? 1 : 0));

        $output = '';

        for ($i = 0; $i < $fullStars; $i++) {
            $output .= '<i class="fas fa-star"></i>';
        }

        if ($halfStar) {
            $output .= '<i class="fas fa-star-half-alt"></i>';
        }

        for ($i = 0; $i < $emptyStars; $i++) {
            $output .= '<i class="far fa-star"></i>';
        }

        return $output;
    }
}

// SHOWING THE BOOKING STATUS WITH BADGE
if (!function_exists('currentStatus')) {
    function currentStatus($status)
    {
        if ($status == 0) {         //pending
            return '<span class="badge badge-pill badge-warning">Pending</span>';
        } elseif ($status == 1) {  //  in accepted
            return '<span class="badge badge-pill badge-success">Accepted</span>';
        } elseif ($status == 2) {  // rejected
            return  '<span class="badge badge-pill badge-danger">Rejected</span>';
        } elseif ($status == 4) {
            return '<span class="badge badge-pill badge-secondary" style="background-color: #80ffaa">Completed</span>';
        }
    }
}
//SHOW THE BOOKING STATUS WITH BADGE


// SHOWING THE BOOKING STATUS WITH BADGE
if (!function_exists('bookingStatus')) {
    function bookingStatus($status)
    {
        if ($status == 0) {         //pending

            return ' <div style="    width: max-content;           margin: 10px auto;
            background: #e6ac00;
            position: absolute;
            right: 0;
            top: -10px;
            padding: 5px 10px;
            border-radius: 25px 0px 0px 25px;
            color: #fff;
            font-size: 18px;
         " class="bookingClip">
                    <p>Pending</p>
                </div>';
        } elseif ($status == 1) {  //  in accepted
            return ' <div style="    width: max-content;           margin: 10px auto;
           background: #265522;
           position: absolute;
           right: 0;
           top: -10px;
           padding: 5px 10px;
           border-radius: 25px 0px 0px 25px;
           color: #fff;
           font-size: 18px;
        " class="bookingClip">
                   <p>Accepted</p>
               </div>';
        } elseif ($status == 2) {  // rejected
            return  ' <div style="    width: max-content;           margin: 10px auto;
        background: #cc0000;
        position: absolute;
        right: 0;
        top: -10px;
        padding: 5px 10px;
        border-radius: 25px 0px 0px 25px;
        color: #fff;
        font-size: 18px;
     " class="bookingClip">
                <p>Rejected</p>
            </div>';
        } elseif ($status == 3) {
            return  ' <div style="    width: max-content;           margin: 10px auto;
        background: #66ffb6;
        position: absolute;
        right: 0;
        top: -10px;
        padding: 5px 10px;
        border-radius: 25px 0px 0px 25px;
        color: #fff;
        font-size: 18px;
     " class="bookingClip">
                <p>Completed</p>
            </div>';
        }
    }
}


if (!function_exists('checkFileExist')) {

    function checkFileExist($file)
    {

        if (File::exists(public_path('stroage/' . $file))) {

            return true;
        } else {

            return false;
        }
    }
}


if (!function_exists('getAddressFromGoogle')) {
    function getAddressFromGoogle($latitude, $longitude)
    {
        //Google Map API URL
        $API_KEY = env("GOOGLE_MAP_API_KEY"); // Google Map Free API Key
        $url     = "https://maps.google.com/maps/api/geocode/json?latlng=" . $latitude . "," . $longitude . "&key=" . $API_KEY . "";
        // Send CURL Request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        curl_close($ch);
        $returnBody = json_decode($response);
        // Google MAP
        $status = $returnBody->status;
        if ($status == "REQUEST_DENIED") {
            $result = $returnBody->error_message;
        } else {
            $result = $returnBody->results[0]->formatted_address;
        }
        return $result;
    }

    if (!function_exists('isValidEmail')) {

        function isValidEmail($email)
        {
            return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        }
    }


    if (!function_exists('getUtcDateTime')) {


        function getUtcDateTime($format)
        {
            $date_utc = new \DateTime("now", new \DateTimeZone("UTC"));
            return $date_utc->format($format);
        }
    }

    if (!function_exists('remainingDays')) {

        function remainingDays($date)
        {

            $now        =   getUtcDateTime('Y-m-d');
            $date1      =   new DateTime($now);
            $date2      =   new DateTime($date);
            $diff       =   $date1->diff($date2);
            // return  $diff->format('%R%a')."d left";
            return  $diff->format('%a') . "d left";
        }
    }




    if (!function_exists('message_media')) {
        function message_media($file, $type)
        {
            if ($type == 2) {

                $folder             =           "chat/images";
            } elseif ($type == 3) {                     //audio

                $folder             =           "chat/audios";
            } elseif ($type == 4) {              // video

                $folder             =           "chat/videos";
            } elseif ($type == 10) {              // video

                $folder             =           "chat/thumbnails";
            } else {                                  // meta data 

                $folder             =           "chat/raw";
            }

            return upload_file($file, $folder);
            // return Storage::disk('public')->put($folder, $file);
        }
    }
}


if (!function_exists('priceAfterTax')) {


    function priceAfterTax($totalamount, $tax)
    {
        //change percentage
        $finalamount = $totalamount * ($tax / 100);
        return round($finalamount, 2);
    }
}

if (!function_exists('decryptString')) {
    function decryptString($id)
    {
        // var_dump($id);
        return $id;
        try {
            return Crypt::decryptString($id);
            // Use $decryptedData as needed
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            // Log or handle the exception
            dd($e);
        }
        //  return $id;
        // // echo ($id);die;

    }
}



if (!function_exists('transferGroupId')) {

    function transferGroupId()
    {
        $timestamp = now()->format('U');
        $randomNumber = random_int(1000, 9999); // Adjust the range as needed
        $uniqueConnectionId = intval($randomNumber . $timestamp . $randomNumber) % 10000000000000;
        return $uniqueConnectionId;
    }
}


if (!function_exists('generateReferCode')) {

    function generateReferCode()
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $max = strlen($characters) - 1;
        $string = '';
        for ($i = 0; $i < 6; $i++) {

            $string .= $characters[mt_rand(0, $max)];
        }
        $refer = DB::table('users')->where('reference_code', $string)->first();

        if ($refer) {

            return generateReferCode();
        } else {

            return $string;
        }
    }
}

if (!function_exists('incrementByPoint')) {
    function incrementByPoint($userId, $role, $point)
    {
        //    $affectedRows = UserRole::firstOrCreate(['user_id' => $userId, 'role_id' => $role])
        //    ->increment('points', $point);
        //     return $affectedRows;
        return UserRole::updateOrCreate(
            ['user_id' => $userId, 'role_id' => $role],
            ['points' => DB::raw('points + ' . $point)]
        );
    }
}

//  function generateQRCode($data)
//     {
        
//         // Create a QR code writer
//         // $renderer = new png();
//         // $renderer->setHeight(300);
//         // $renderer->setWidth(300);
//         // $writer = new Writer($renderer);

//         // // Generate QR code from the data
//         // $qrCode = $writer->writeString($data);

//         // // Save QR code image to storage
//         // $filename = 'qr_code.png';
//         // Storage::disk('public')->put($filename, $qrCode);

//         // // Return the path to the stored QR code image
//         // return Storage::url($filename);
//         $renderer = new Png();
// $renderer->setHeight(300);
// $renderer->setWidth(300);
// $writer = new Writer($renderer);

// // Generate QR code from the data
// $qrCode = $writer->writeString($data);

// // Save QR code image to storage
// $filename = 'qr_code.png';
// Storage::disk('public')->put($filename, $qrCode);

// // Return the path to the stored QR code image
// return Storage::url($filename);
//     }
 function generateQRCode($userid)
{

    $random     =   rand(1111,9999);
    $encryptdQr =   Crypt::encrypt($random.$userid);
    $currentYear = date('Y');
    $currentMonth = date('m');
    $directory = "uploads/qr/{$currentYear}/{$currentMonth}";
    // Create QR code renderer
    $renderer = new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    );

    // Create QR code writer
    $writer = new Writer($renderer);

    $filename = $directory . '/' . Str::uuid() . '.png';
    // Generate QR code image and save it to the storage directory
    $filePath = storage_path('app/public/'.$filename);

    // Storage::disk('public')->put($filename, $qr_image);

    $writer->writeFile("https://rosterapp/match?u=" .$encryptdQr, $filePath);

    // Return the path to the generated QR code image
    return $filename;



  


    // Store the QR code image directly into the storage disk
    
   // Storage::disk('public')->put($filename, $qr_image);
    // Return the path to the stored QR code image
   // return $filename;



   


}

 

    function colorFullQr($userid)
    {
        try{

            $random         =   rand(1111,9999);
            $encryptdQr     =   $random.$userid;
            $currentYear    = date('Y');
            $currentMonth   = date('m');
            $directory      = "uploads/qr/{$currentYear}/{$currentMonth}";
            $eye            = SquareEye::instance();
            $squareModule   = SquareModule::instance();
            $eyeFill        = new EyeFill(new Rgb(43, 22, 11), new Rgb(28, 150, 138));
            // Define the starting and ending colors for the gradient
            // $startColor = new Rgb(82, 190, 169); // RGB(82, 190, 169)
            $startColor     = new Rgb(43, 22, 11); // Pink: RGB(255, 192, 203)
            $endColor       = new Rgb(28, 150, 138); // RGB(28, 150, 138)
            // Create a gradient transitioning between the provided colors
            $gradient       = new Gradient($startColor, $endColor, GradientType::HORIZONTAL());
            $renderer       = new ImageRenderer(
                new RendererStyle(
                    300,
                    2,
                    $squareModule,
                    $eye,
                    Fill::withForegroundGradient(new Rgb(255, 255, 255), $gradient, $eyeFill, $eyeFill, $eyeFill)
                ),
                new ImagickImageBackEnd()
            );
            // Mocking the HTTP request with Laravel's Http facade
            $writer     = new Writer($renderer);
            $filename   = $directory . '/' . Str::uuid() . '.png';
            // Generate QR code image and save it to the storage directory
            $filePath   = storage_path('app/public/'.$filename);
            // Storage::disk('public')->put($filename, $filePath);
            $writer->writeFile("https://rosterapp/match?u=" .$encryptdQr, $filePath);

            return $filename;
        }catch(Exception $e){
            return 400;
        }
    }



    if (!function_exists('removeSpecialCharsAndFormat')) {

        function removeSpecialCharsAndFormat($inputString) {
            // Remove all special characters from the string
            $cleanedString = preg_replace('/[^a-zA-Z0-9 ]/', '', $inputString);
            
            // Capitalize the first letter of each word
            $cleanedString = ucwords($cleanedString);
        
            // Remove trailing spaces from the right side of the string
            $cleanedString = rtrim($cleanedString);
        
            return $cleanedString;
        }
    }


    if (!function_exists('incrementMember')) {
        function incrementMember($userId, $id,$point)
        {
            return Group::updateOrCreate(
                ['created_by' => $userId, 'id' => $id],
                ['member_count' => DB::raw('member_count + ' . $point)]
            );
        }
    }

    if (!function_exists('incrementMember')) {
        function incrementMemberWithAuth($id,$point)
        {
            return Group::updateOrCreate(
                ['id' => $id],
                ['member_count' => DB::raw('member_count + ' . $point)]
            );
        }
    }

    if(!function_exists('filer_text')){
        function filter_text($text){

            $text = rtrim($text);
            // Remove special characters
            $text = preg_replace('/[^A-Za-z0-9\s]/', '', $text);
            return  strip_tags($text);
        }
    }



   
    
    if (!function_exists('increment')) {
        function increment($tableName, $conditions, $field, $incrementBy)
        {
            return DB::table($tableName)
                ->updateOrInsert(
                    $conditions,
                    [$field => DB::raw("$field + $incrementBy")]
                );
        }
    }

    if (!function_exists('decrement')) {
        function decrement($tableName, $conditions, $field, $incrementBy)
        {
            return DB::table($tableName)
                ->updateOrInsert(
                    $conditions,
                    [$field => DB::raw("$field - $incrementBy")]
                );
        }
    }


    if(!function_exists('shortNumber')){

        function shortNumber($num) 
        {
            $units = ['', 'K', 'M', 'B', 'T'];
            for ($i = 0; $num >= 1000; $i++) {
                $num /= 1000;
            }
            return round($num, 1) . $units[$i];
        }
    }

    if (!function_exists('format_number_in_k_notation')) {
        function format_number_in_k_notation(int $number): string
        {
            $suffixByNumber = function () use ($number) {
                
                if ($number < 1000) {
                    return sprintf('%d', $number);
                }
    
                if ($number < 1000000) {
                    return sprintf('%d%s', floor($number / 1000), 'K+');
                }
    
                if ($number >= 1000000 && $number < 1000000000) {
                    return sprintf('%d%s', floor($number / 1000000), 'M+');
                }
    
                if ($number >= 1000000000 && $number < 1000000000000) {
                    return sprintf('%d%s', floor($number / 1000000000), 'B+');
                }
    
                return sprintf('%d%s', floor($number / 1000000000000), 'T+');
            };
    
            return $suffixByNumber();
        }
    }
    
