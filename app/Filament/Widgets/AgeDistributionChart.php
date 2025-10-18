<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AgeDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Age Distribution';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        // Using the age_type field from the guests table
        $data = DB::connection('tenant')->table('guests')
            ->select('age_type', DB::connection('tenant')->raw('count(*) as count'))
            ->groupBy('age_type')
            ->get();

        // Alternative approach using date_of_birth if needed:
        /*
        $data = DB::connection("tenant")->table('guests')
            ->select(
                DB::connection("tenant")->raw('CASE
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN "Child"
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 65 THEN "Adult"
                    ELSE "Senior" END as age_type'),
                DB::connection("tenant")->raw('count(*) as count')
            )
            ->groupBy('age_type')
            ->get();
        */

        return [
            'datasets' => [
                [
                    'label' => 'Residents by age type',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(255, 159, 64)', // Child
                        'rgb(54, 162, 235)', // Adult
                        'rgb(153, 102, 255)', // Senior
                    ],
                ],
            ],
            'labels' => $data->pluck('age_type')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
