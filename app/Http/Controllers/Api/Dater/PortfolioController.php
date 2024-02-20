<?php
namespace App\Http\Controllers\Api\Dater;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Validator;
use App\Models\UserPortfolio;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GetUserService;
use App\Services\UserProfileUpdate;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends BaseController
{
     protected $user, $userProfile, $authId, $getUser;
    // protected $userProfile;
    public function __construct(UserProfileUpdate $userProfile, GetUserService $getUser)
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $this->authId = Auth::id();
            return $next($request);
        });

        $this->userProfile = $userProfile;
        $this->getUser = $getUser;
    }
    //
    #----------- U P L O A D    P O R T F O L I O S --------------#
    public function uploadPortfolio(Request $request,$id=""){
        
       
        try {
            
            if ($request->method() === 'DELETE') {
                 // Perform actions specific to DELETE requests
                return $this->deletePortfolioImage($request);
                
            }if ($request->method() === 'POST') {
                // Perform actions specific to POST requests
               return $this->uploadPortfolioImages($request);
            }
        } catch (Exception $e) {
            Log::error('Error caught: "uploadportfolio" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #----------- U P L O A D    P O R T F O L I O S --------------#
    public function uploadPortfolioImages($request){
        try{
            
            $validator     =    Validator::make($request->all(), [ 'portfolio' => 'required|array|min:2|max:4',
                        'portfolio.*' => 'required|image|mimes:jpeg,jpg,png,bmp,gif,svg|max:2048','position'=>'required']);// Assuming each item in the array is an image]);
            if ($validator->fails()) {  
    
                return response()->json([
                    'success'   => 422,
                    'message'   => $validator->errors()->first(),
                ],422);
    
            }else{

                $authUser       =   Auth::user();
                
                if($request->hasFile('portfolio')){
                    
                    $position       =   explode(",",$request->position);
                    $files      =   $request->file('portfolio');
                    if(count($files)== count($position)){
                        $images     =   [];
                        
                        foreach($files as $key=>$file){
                            $image      =       upload_file($file,'portfolio');
                            $portfolio  =       new UserPortfolio();
                            $portfolio->image  = $image;
                            if($position){
                                $portfolio->position=  $position[$key];
                            }
                            $portfolio->user_id=$authUser->id;
                            $portfolio->save();
                            DB::commit();
                        }
                        $userData = $this->getUser->getUser($authUser->id);
                        return $this->sendResponse($userData, trans("message.switch_user"), 200);
    
                    }else{
                        
                        return $this->sendResponsewithoutData("Invalid profilio or position", 400);
                    }
                }
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "uploadportfolio" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
    #---------------- U P L O A D       P O R T F O L I O S--------------#
    
    
    
    
    
    
    
    
    
    #----------  D E L E T E    P O R T F O L I O       I M A G E ------------#
    public function deletePortfolioImage($request){
        
       DB::beginTransaction();
       //dd($request->segment(3)); // Replace 0 with the desired segment index)
        try {
            $validator     =    Validator::make( ['id' => $request->segment(3)], // Wrap the segment value in an array with the 'id' key
            ['id' => 'required|integer|exists:user_portfolios']); // Remove ',id' as it's not required her);// Assuming each item in the array is an image]);
            if ($validator->fails()) {  
    
                return response()->json([
                    'success'   => 422,
                    'message'   => $validator->errors()->first(),
                ],422);
    
            }else{
                $authUser       =   Auth::user();
                $id             =   $request->segment(3);
                $imagePath      = UserPortfolio::select('image')->where('id', $id)->first();
                if(isset($imagePath) && !empty($imagePath)){
                    
                    $filePath       = $imagePath->image;
                    Storage::disk('public')->delete($filePath);
                }
                UserPortfolio::where('id', $id)->delete();
                DB::commit();
                $userData = $this->getUser->getUser($authUser->id);
                return $this->sendResponse($userData, trans("message.switch_user"), 200);
            }
        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error caught: "uploadportfolio" ' . $e->getMessage());
            return $this->sendError($e->getMessage(), [], 400);
        } 
    }
}
