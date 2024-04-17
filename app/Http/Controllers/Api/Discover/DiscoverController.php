<?php

namespace App\Http\Controllers\Api\Discover;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AddCommunity;
use App\Http\Requests\EditCommunity;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\GroupMemberRequest;
use Carbon\Carbon;
use App\Services\Discover\DicoverService;
use Exception;
use App\Models\User;

class DiscoverController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    protected $discover;

    public function __construct(DicoverService $discover)
    {
        $this->discover     =       $discover;
      
    }
    //
    public function index(Request $request)
    {
        $authId         =   Auth::id();

        return $this->discover->discover($request,$authId);
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
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
