<?php

namespace App\Jobs\Summarize;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\SummarizePost as summarizeTarit;
class SummarizePost implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,summarizeTarit;

    /**
     * Create a new job instance.
     */
    protected $postId;
    public function __construct($postId)
    {
        $this->postId =     $postId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->summerize($this->postId);
    }
}
