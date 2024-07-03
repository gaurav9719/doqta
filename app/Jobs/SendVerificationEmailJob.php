<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Illuminate\Support\Facades\Log;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3; // Specify the number of times the job should be retried

    /**
     * Create a new job instance.
     */
    protected $details;

    public function __construct($details)
    {
        //
        $this->details = $details;

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        Mail::to($this->details['email'])->send(new VerifyEmail($this->details));

        Log::error('Error caught: "signUpUser"');



    }

    public function backoff(): array
    {
        return [1, 5, 10];
    }
}
