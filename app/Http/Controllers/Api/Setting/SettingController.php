<?php

namespace App\Http\Controllers\Api\Setting;

use App\Models\GroupMember;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\BaseController;
use App\Models\User;

class SettingController extends BaseController
{
    //
    function setting(Request $request){
        $validator=Validator::make($request->all(), [
            'mute_all'              => 'nullable|integer|between:0,1',
            'mute_communities'      => 'nullable|array',
            'mute_communities.*'    => 'nullable|integer|exists:groups,id',
            'mute_acounts'          => 'nullable|array',
            'mute_acounts.*'        => 'nullable|integer|exists:users,id',
            'action'                => 'nullable|integer|between:0,1',

        ]);

        if($validator->fails()){
            return $this->sendResponsewithoutData($validator->errors()->first(), 422);
        }

        if(isset($request->mute_all) || isset($request->mute_communities) || isset($request->mute_acounts)){
            
            $user               =   User::find(Auth::id());

            if(isset($request->mute_all) ){

                $user->is_muted = $user->is_muted == 1 ? 0 : 1;

                $user->save();

                return $this->sendResponsewithoutData('Profile notification setting updated successfully', 200);
            }
            elseif(isset($request->mute_communities) && count($request->mute_communities)){

                $userGroupIds   = GroupMember::where('user_id', $user->id)->pluck('group_id')->toArray();

                $check          = array_diff($request->mute_communities,$userGroupIds);

                if(count($check) > 0){

                    return $this->sendResponsewithoutData("Provided communities contains invalid ids", 422);

                }
                $member=GroupMember::where('user_id', $user->id)->whereIn('group_id', $request->mute_communities)->update([

                    'is_muted' => $request->action
                ]);
                # ------------ need to send user data again ----------_____#
                return $this->sendResponsewithoutData('Community notification setting updated successfully', 200);
            }
            // elseif(isset($request->mute_acounts) && count($request->mute_acounts) > 0){
                // Inbox::where('user_id', $user->id);
            // }
            else{

                return $this->sendResponsewithoutData("Something went wrong.", 422);
            }
            
        }
        else{
            return $this->sendResponsewithoutData("At least one parameter is required.", 422);
        }
    }
}
