<?php

namespace App\Console\Commands;


use App\Helpers\ImportHelperFacade;
use App\Jobs\ProcessImport;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;


class ImportCommand extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import {--type=user} {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        $err = false;
        $errMessage = "";
        $path = $this->option('path');
        $type = $this->option('type');
        if (is_null($path)) {
            $path = $this->ask('What is your path?');
        }

        if (is_null($path)) {
            $errMessage = 'path is empty!';
        }


        if ($path) {
            try {
                $contents = ImportHelperFacade::importUsers($path);
                $this->info("Import started. Please check the logs for the updates!");
            } catch (Exception $e) {
                $err = true;
                $errMessage = "Error reading file from the provided path!";
            }
        } else {
            $err = true;
            $errMessage = "File does not exist!";
        }

        if ($err) {
            $this->error($errMessage);
        }
    }
}
