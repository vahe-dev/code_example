<?php

namespace App\Console\Commands;

use App\Models\Reservoir;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendMetricsToApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:metrics-to-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reservoirs metrics to API';

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
     */
    public function handle()
    {
        try {
            Log::info('----Start sending metrics to API-----');

            $data = [];
            $idsToUpdate = [];
            $reservoirsIDs = config('constant.reservoir_ids');

            $reservoirsWithMetrics = Reservoir::with('metrics')
                ->select('id', 'file_name', 'api_last_date')
                ->whereIn('file_name', $reservoirsIDs)
                ->get();

            foreach ($reservoirsWithMetrics as $reservoir) {
                foreach ($reservoir->metrics as $metric) {

                    if (is_null($reservoir->api_last_date) || $metric->date->greaterThan(Carbon::parse($reservoir->api_last_date))) {

                        $parseDate = Carbon::parse($metric->date)->tz('Asia/Yerevan');

                        $data[] = [
                            'hwid' => $reservoir->file_name,
                            'date' => $parseDate->timestamp,
                            'level' => $metric->h / 100,
                            'flowrate' => $metric->q_second,
                            'indication' => $metric->q_cube
                        ];
                        if (!in_array($reservoir->file_name, $idsToUpdate)) {
                            $idsToUpdate[] = $reservoir->file_name;
                        }
                    }
                }
            }

            $jsonData = [
                "data" => $data
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . env('API_TOKEN'),
                'Content-Type' => 'application/json',
            ])->asForm()->post(env('API_URL'), ['data' => json_encode($jsonData)]);

            $responseData = json_decode($response);

            if (isset($responseData->success) && $responseData->success === 1) {
                if ($idsToUpdate){
                    Log::info('Reservoirs metrics were successfully sent to the API, success=' . $responseData->success);
                    Reservoir::whereIn('file_name', $idsToUpdate)->update(['api_last_date' => Carbon::now()]);
                    Log::info('ReservoirsIDs - , ' . json_encode($idsToUpdate));

                } else {
                    Log::info('No new data to send, success=' . $responseData->success);
                }
                Log::info('Inserted data=' . $responseData->inserted_data_count);
                Log::info('Response message-' . $responseData->message);

            } elseif (isset($responseData->success) && $responseData->success === 0) {
                Log::info('Failed to send data , success=' . $responseData->success);
                Log::info($responseData->message);
            } else {
                Log::info('Other response from API ' . json_encode($responseData));
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
