<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;

abstract class SyncJob
{
    /*
    |--------------------------------------------------------------------------
    | Synchronized Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your Synchronized jobs.
    |
    */

    use Queueable;
}
