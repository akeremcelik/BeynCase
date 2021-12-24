<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Models\CarModel;
use DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class FetchCarModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:car-models';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $url = "https://static.novassets.com/automobile.json";
        $response = file_get_contents($url);

        $columns = Schema::getColumnListing('car_models');
        $columns = array_diff($columns, ['created_at', 'updated_at']);
        $carModels = json_decode($response, true)["RECORDS"];

        DB::transaction(function() use($carModels, $columns) {
            try {
                if(CarModel::count() > 0) CarModel::query()->truncate();
                foreach($carModels as $carModel) {
                    $newCarModel = new CarModel();
                    foreach($columns as $column) {
                        $newCarModel->$column = $carModel[$column];
                    }
                    $newCarModel->save();
                }

                Cache::put('carModels', $carModels, 600);
            } catch (\Exception $e) {
                Log::debug($e->getMessage());
            }
        });
    }
}
