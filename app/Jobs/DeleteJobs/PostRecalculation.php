<?php

namespace App\Jobs\DeleteJobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Group;
use App\Models\Post;

class PostRecalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $groupId;
    /**
     * Create a new job instance.
     */
    public function __construct($groupId)
    {
        //
        $this->groupId          =   $groupId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $totalGroupPost          =   Post::where('group_id',$this->groupId)->where('is_active',1)->count();
        Group::where('id',$this->groupId)->update(['post_count'=>$totalGroupPost]);
    }
}
