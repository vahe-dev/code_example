<?php

namespace App\Console\Commands;

use App\Models\Reservoir;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSpecificCompaniesToApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:specific-companies-to-env-api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send specific companies metrics to a API';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            Log::info('----Start sending specific companies metrics to API-----');

            $data = [];
            $idsToUpdate = [];

            $reservoirsWithMetrics = Reservoir::select([
                'reservoirs.id',
                'reservoirs.name',
                'reservoirs.serial_no',
                'reservoirs.device_code',
                'reservoirs.group_id',
                'reservoirs.file_name',
                'reservoirs.api_last_date',
                'reservoirs.q_sum',
                'parentGroup.id AS company_id',
            ])
                ->join('groups AS subGroup', 'reservoirs.group_id', '=', 'subGroup.id')
                ->leftJoin('groups AS genGroup', function ($join) {
                    $join->on('genGroup.id', '=', 'subGroup.parent_id');
                })
                ->leftJoin('groups AS parentGroup', function ($join) {
                    $join->on(DB::raw('(parentGroup.id = subGroup.parent_id AND subGroup.is_sub_group = 0)'), '=', DB::raw('1'))
                        ->orOn(DB::raw('(parentGroup.id = genGroup.parent_id AND subGroup.is_sub_group = 1)'), '=', DB::raw('1'));
                })
                ->where('env_api_status', 1)
                ->whereIn('parentGroup.id', config('constant.exceptionCompaniesIds'))
                ->with(['metrics' => function ($query) {
                    $query->where('date', '>=', config('constant.CURRENT_YEAR'));
                }])
                ->orderBy('id', 'desc')
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
                            //as the data is transferred every half hour, need to divide the sum by 2, except for specific codes
                            'indication' => in_array($reservoir->device_code, config('constant.exceptionDeviceCodes')) ? $metric->cube_sum : $metric->cube_sum / 2
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
        }  catch (\Exception $e) {
            Log::info($e->getMessage());
        }
    }
}
