<?php

namespace App\Filament\Widgets;

use App\Models\CheckIn;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OccupancyTrendChart extends ChartWidget
{
    protected static ?string $heading = 'Occupancy Trend';
    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        // Get daily check-in counts for the last 30 days
        $startDate = Carbon::now()->subDays(30);
        $endDate = Carbon::now();

        $dailyCheckIns = DB::table('check_ins')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Create a range of all dates in the period
        $period = collect(Carbon::parse($startDate)->daysUntil($endDate));
        $dates = $period->map(fn ($date) => $date->format('Y-m-d'));
        
        // Fill in any missing dates with zero counts
        $filledData = collect();
        foreach ($dates as $date) {
            $checkInsForDate = $dailyCheckIns->firstWhere('date', $date);
            $filledData->push([
                'date' => $date,
                'count' => $checkInsForDate ? $checkInsForDate->count : 0,
            ]);
        }
        
        return [
            'datasets' => [
                [
                    'label' => 'Daily check-in counts over time',
                    'data' => $filledData->pluck('count')->toArray(),
                    'fill' => true,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'tension' => 0.1,
                ],
            ],
            'labels' => $filledData->pluck('date')->map(fn ($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}