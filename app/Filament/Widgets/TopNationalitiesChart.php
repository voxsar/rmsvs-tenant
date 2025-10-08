<?php

namespace App\Filament\Widgets;

use App\Models\Guest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopNationalitiesChart extends ChartWidget
{
    protected static ?string $heading = 'Top Nationalities';
    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $data = DB::connection("tenant")->table('guests')
            ->select('nationality', DB::connection("tenant")->raw('count(*) as count'))
            ->groupBy('nationality')
            ->orderBy('count', 'desc')
            ->limit(6) // Top 5 plus "Others"
            ->get();
        
        // If there are more than 5 nationalities, combine the rest as "Others"
        $total = DB::connection("tenant")->table('guests')->count();
        $topNationalities = $data->take(5);
        $topTotal = $topNationalities->sum('count');
        
        if ($topTotal < $total) {
            $others = $total - $topTotal;
            $topNationalities = $topNationalities->toArray();
            $topNationalities[] = (object) [
                'nationality' => 'Others',
                'count' => $others
            ];
            $data = collect($topNationalities);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Residents by country of origin',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => [
                        'rgb(54, 162, 235)',
                        'rgb(255, 99, 132)',
                        'rgb(255, 206, 86)',
                        'rgb(75, 192, 192)',
                        'rgb(153, 102, 255)',
                        'rgb(201, 203, 207)',
                    ],
                ],
            ],
            'labels' => $data->pluck('nationality')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}