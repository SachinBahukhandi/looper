<?php

namespace App\Jobs;

use App\Helpers\ImportHelperFacade;
use App\Models\User;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessImport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $contents;
    protected string $type;
    protected array $headers;
    /**
     * Create a new job instance.
     */
    public function __construct(array $contents, string $type, array $headers)
    {
        //
        $this->contents = $contents;
        $this->type = $type;
        $this->headers= $headers;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //

        $lastId = 0;
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            ImportHelperFacade::log(['error'=> true, 'message'=> 'Batch cancelled']);
            return;
        }

        if ($this->type == 'user') {
            $users = ImportHelperFacade::prepareChunk($this->contents, $this->headers, $this->type);
            $lastId = ImportHelperFacade::addData($users, $this->type);
        }

        if ($this->batch()->finished()) {
            ImportHelperFacade::log(['success'=> true, 'message'=> 'Batch completed with id: ' . $lastId]);
        }

    }

    /**
     * The job failed to process.
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        \Log::error(json_encode($exception, JSON_PRETTY_PRINT));
        // Send user notification of failure, etc...
    }
}
