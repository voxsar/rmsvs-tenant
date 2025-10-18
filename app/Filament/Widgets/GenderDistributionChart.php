<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class GenderDistributionChart extends ChartWidget
{
    protected static ?string $heading = 'Gender Distribution';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $data = DB::connection('tenant')->table('guests')
            ->select('sex', DB::connection('tenant')->raw('count(*) as count'))
            ->groupBy('sex')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Residents by sex',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(54, 162, 235)', // Male
                        'rgb(255, 99, 132)', // Female
                    ],
                ],
            ],
            'labels' => $data->pluck('sex')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
