<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class TimezoneServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Ensure Carbon uses the application timezone for display
        Carbon::setLocale(config('app.locale'));
        // Force all timestamps to be stored in UTC when converted to string
        Carbon::setToStringFormat('Y-m-d H:i:s');
    }
}
?>
