<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Console\Commands\CalcularDiferenciasIDM;
use App\Console\Commands\SortearMesas;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\asociarJuegoCasino::class,
        Commands\packJuego::class,
        Commands\GenerarRelevamientosApuestas::class,
        SortearMesas::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {

      $schedule->command('RAM:sortear')
               //->everyMinute() para pruebas
               ->dailyAt('00:30')
               ->runInBackground();

      // $schedule->command('RAM:sortear')
      //         ->dailyAt('12:00')
      //         ->runInBackground();
      $schedule->command('RAM:sortear')
               ->dailyAt('16:30')
               ->runInBackground();

     $schedule->command('RelevamientoApuestas:generar')
             ->dailyAt('00:45')
             ->runInBackground();

     $schedule->command('RelevamientoApuestas:generar')
             ->dailyAt('17:10')
             ->runInBackground();

     $schedule->call(function () {

         $comando = DB::table('comando_a_ejecutar')
             ->where(['fecha_a_ejecutar','>',Carbon::now()->format('Y:m:d H:i:s')])
             ->get();
            foreach ($comando as $c) {
              switch ($c->nombre_comando) {
                case 'IDM:calcularDiff':
                  $impController->calcularDiffIDM();
                  break;
              
                default:

                  break;
              }
            }
     })->everyThirtyMinutes();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        //$this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
