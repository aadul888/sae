<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Carbon\Carbon;

class TimezoneServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Ensure Carbon uses the application timezone and Indonesian locale for display
        $locale = config('app.locale') ?: 'id';
        Carbon::setLocale($locale);
        \Illuminate\Support\Carbon::setLocale($locale);
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id', 'Indonesian');

        // Force all timestamps to be stored in UTC when converted to string
        Carbon::setToStringFormat('Y-m-d H:i:s');
    }
}
?>
