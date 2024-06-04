<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Traits\CalculateScore;

class AiScoreCalculatedJob implements ShouldQueue
{
    
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,CalculateScore;
    protected $postId;
    /**
     * Create a new job instance.
     */
    public function __construct($postId)
    {
        
        $this->postId       =   $postId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $score = $this->calculateScoreByAi($this->postId);
    }
}
