<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
        @livewire(\App\Filament\Widgets\ResidentStatsOverview::class)
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
        <div class="col-span-1">
            @livewire(\App\Filament\Widgets\ResidentTypeChart::class)
        </div>
        <div class="col-span-1">
            @livewire(\App\Filament\Widgets\AgeDistributionChart::class)
        </div>
        <div class="col-span-1">
            @livewire(\App\Filament\Widgets\GenderDistributionChart::class)
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="col-span-1">
            @livewire(\App\Filament\Widgets\TopNationalitiesChart::class)
        </div>
        <div class="col-span-1">
            @livewire(\App\Filament\Widgets\OccupancyTrendChart::class)
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6">
        @livewire(\App\Filament\Widgets\BirthdaysWidget::class)
    </div>
    <div class="mt-6 grid grid-cols-1 gap-6">
        @livewire(\App\Filament\Widgets\AbsenceTrackerWidget::class)
    </div>
</x-filament-panels::page>