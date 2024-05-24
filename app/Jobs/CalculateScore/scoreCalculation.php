<?php

namespace App\Jobs\CalculateScore;

use Exception;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use App\Traits\CalculateScore;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class scoreCalculation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, CalculateScore;

    protected $postId;
    /**
     * Create a new job instance.
     */
    public function __construct($postId)
    {
        // 
        $this->postId       =   $postId;
    }

    // public $tries = 3;
    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $post = Post::find($this->postId);
        
        if ($post && !empty($post->content)) {
            try {
                $score = $this->calculateScoreByAi($this->postId);
                log::info("Score".$score);
                $post->ai_score = $score;
                $post->save();
               
            } catch (Exception $e) {
                Log::error('Job failed to calculate AI score: ' . $e->getMessage());

                $this->failed($e);
            }
        }



        
    }

    public function failed(\Throwable $exception)
    {
        // Call the helper function on failure
        Log::error('Job failed to calculate AI score: ' . $exception->getMessage());

    }
}
