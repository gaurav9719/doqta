<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Group;
use Illuminate\Support\Facades\Auth;
use App\Models\GroupMember;
use Illuminate\Support\Facades\DB;


class CommunityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //communities
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (empty($request->id)) {
            return $this->add_community($request);
        }else{
           return $this->edit_community($request);
        }
        
    }

//add community
    public function add_community($request){
  try{
        $authId  =  Auth::id();

        
    if (empty($request->name)) {
        echo json_encode(400);
        return false;
    }

    $name = $request->name;
 

    $groupExists = Group::where('name', $request->name)->first();
    if (!empty($groupExists)) {
        echo json_encode(403);
        return false;
    }
        

    $group_new = new Group;
    $group_new->name = $request->name;
    $group_new->created_by = $authId;
    $group_new->description = $request->description;

    if ($request->cover_pic_input) {
        $cover_photo = $request->file('cover_pic_input');
        $Uploaded = upload_file($cover_photo, 'cover_photo');
        $group_new->cover_photo = $Uploaded;
    }

    if($group_new->save()){

    $groupMember = new GroupMember();
    $groupMember->group_id = $group_new->id;
    $groupMember->user_id = $authId;
    $groupMember->role = 'owner';
    $groupMember->save();
    }

    echo json_encode(200);
} catch (\Exception $e) {
    return response()->json(['error' => 'An error occurred while creating the group.'], 500);
}

}

//edit community
public function edit_community($request){
    try{
        if (empty($request->id)) {
         echo json_encode(405);
            return ;
        }


    if (empty($request->edit_name)) {
        echo json_encode(400);
        return;
    }
    $name = $request->edit_name;
    $group_exist = Group::where(['name' => $name])
    ->where('id','!=',$request->id)
    ->first();
    if (!empty($group_exist)) {
        echo json_encode(403);
        return;
    }
 
    $group_new =Group::find($request->id);
    $group_new->name =  $name;
    $group_new->description = $request->edit_description;


    if ($request->edit_cover_pic_input) {
        $cover_photo = $request->file('edit_cover_pic_input');
        $Uploaded = upload_file($cover_photo, 'cover_photo');
        $group_new->cover_photo = $Uploaded;
    }

    $group_new->save();

    echo json_encode(Group::find($request->id));
} catch (\Exception $e) {
    return response()->json(['error' => 'An error occurred while creating the group.'], 500);
}

}


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $group=Group::find($id);
        echo json_encode($group);
        return;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
