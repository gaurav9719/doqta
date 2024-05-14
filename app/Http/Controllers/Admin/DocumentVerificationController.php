<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\UserDocuments;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\UserMedicalCredentials;
use Illuminate\Support\Facades\Storage;
use App\Models\Notification;
use App\Services\NotificationService;

class DocumentVerificationController extends Controller
{
    protected $notificationService;
    public function __construct(NotificationService $notificationService)
    {

        $this->notificationService = $notificationService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|integer|between:1,2',
            'id' => 'required|integer',
            'reject' => 'nullable|integer',
            'verify' => 'nullable|integer',
        ]);

        if($request->type == 1){ // User identity documents type=1
            $document=UserDocuments::find($request->id);

            if(isset($document)){
                if(isset($request->reject)){
                    $type       =   trans('notification_message.document_not_verified_type'); // 2
                    $document->verified_status = $type;
                    $document->save();
                    #send notification

                    $check=Notification::where('receiver_id', $document->user_id)->where('notification_type', $type)->first();
                    if(isset($check)){
                        $check->delete();
                    }

                    $receiver= User::find($document->user_id);
                    $sender= User::where('role', 3)->first();
                    $sender = isset($sender) ? $sender : $receiver;
                    $data=["message"=> "Your document not verified"];

                    $this->notificationService->sendNotificationNew($sender, $receiver, $type, $data);

                    return redirect()->back()->with('success', 'Document rejected successfully');

                }
                elseif(isset($request->verify)){

                    $document->verified_status = 1;
                    $document->save();
                    #send notification
                    $check=Notification::where('receiver_id', $document->user_id)->where('notification_type', trans('notification_message.document_not_verified'))->first();

                    if(isset($check)){

                        $check->delete();
                    }
                    
                    $this->check($document->user_id);

                    return redirect()->back()->with('success', 'Document verified successfully');
                }
            }
            else{
                return redirect()->back()->with('fail', 'Incorrect document');
            }
        }
        elseif($request->type == 2){        // medicial credential documents type=2

            $document=UserMedicalCredentials::find($request->id);
            if(isset($document)){
                if(isset($request->reject)){
                    $document->verified_status = 2;
                    $document->save();

                    #send notification

                    $check=Notification::where('receiver_id', $document->user_id)->where('notification_type', 2)->first();
                    if(isset($check)){
                        $check->delete();
                    }
                    
                    $receiver= User::find($document->user_id);
                    $sender= User::where('role', 3)->first();
                    $sender = isset($sender) ? $sender : $receiver;
                    $data=["message"=> trans('notification_message.document_not_verified')];
                    $this->notificationService->sendNotificationNew($sender, $receiver, trans('notification_message.document_not_verified_type'), $data);


                    return redirect()->back()->with('success', 'Document rejected successfully');
                }
                elseif(isset($request->verify)){
                    $document->verified_status = 1;
                    $document->save();
                    $this->check($document->user_id);
                    return redirect()->back()->with('success', 'Document verified successfully');
                }
            }
            else{
                
                return redirect()->back()->with('fail', 'Incorrect document');
            }
        }
        
        return redirect()->back();
    }

    function check($user_id){
        $doc1 = UserDocuments::where('user_id', $user_id)->get();
        $docVerified1 = UserDocuments::where('user_id', $user_id)->where('verified_status', 1)->get();
        $doc2 = UserMedicalCredentials::where('user_id', $user_id)->get();
        $docVerified2 = UserMedicalCredentials::where('user_id', $user_id)->where('verified_status', 1)->get();
        if(count($doc1) > 0 && count($doc2) > 0 && count($doc1) == count($docVerified1) &&  count($doc2) == count($docVerified2)){
            $user= User::find($user_id);
            $user-> is_document_verified = 1;
            $user->save();

            #send notification
            $check=Notification::where('receiver_id', $user->id)->where('notification_type', 2)->first();
                    if(isset($check)){
                        $check->delete();
                    }
            $receiver= User::find($user->id);
            $sender= User::where('role', 3)->first();
            $sender = isset($sender) ? $sender : $receiver;
            $data=["message"=>trans('notification_message.document_verified')];
            $this->notificationService->sendNotificationNew($sender, $receiver, trans('notification_message.document_verified_type'), $data);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        if ((int)$id) {
            $auth = Auth::user();
            $user = User::find($id);
            if (isset($user)) {
                $document = array();
                $identity =  UserDocuments::where('user_id', $user->id)->get();
                if (count($identity) > 0) {
                    foreach ($identity as $iden) {
                        if (Storage::disk('public')->exists($iden->document)) {
                            $document['identity'][] = $iden;
                        }
                    }
                }
                $certificate = UserMedicalCredentials::where('user_id', $user->id)->get();
                if (count($certificate) > 0) {
                    foreach ($certificate as $cer) {
                        if (Storage::disk('public')->exists($cer->medicial_document)) {
                            $document['certificate'][] = $cer;
                        }
                    }
                }
                return view('admin/verify-document', compact('auth', 'user', 'document'));
            }
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
