<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ResidentTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Residents by Type';

    protected static ?string $maxHeight = '300px';

    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $data = DB::connection('tenant')->table('guests')
            ->select('type', DB::connection('tenant')->raw('count(*) as count'))
            ->groupBy('type')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Distribution of residents by type',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(255, 99, 132)', // Regular
                        'rgb(54, 162, 235)', // Temporary
                        'rgb(255, 206, 86)', // Special
                        'rgb(75, 192, 192)', // VIP
                    ],
                ],
            ],
            'labels' => $data->pluck('type')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
