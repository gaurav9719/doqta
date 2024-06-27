<?php

namespace App\Jobs\Summarize;

use App\Traits\SummarizePost;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CommentThreadSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SummarizePost;

    /**
     * Create a new job instance.
     */
    protected $postId, $commentId;
    public function __construct($postId, $commentId)
    {
        //
        $this->postId           =     $postId;
        $this->commentId        =     $commentId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->generateCommentThreadSummary($this->postId,$this->commentId);
        Log::info("summarize comment thread working");
        Log::info("summarize queue is working result");
    }
}
