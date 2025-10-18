<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        @livewire(\App\Filament\Widgets\PersonsOnSiteWidget::class)
        @livewire(\App\Filament\Widgets\LastShiftReportWidget::class)
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6">
        @livewire(\App\Filament\Widgets\EmergencyRollCallWidget::class)
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6">
        @livewire(\App\Filament\Widgets\AbsenceTrackerWidget::class)
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2">
        @livewire(\App\Filament\Widgets\MissedMealsWidget::class)
        @livewire(\App\Filament\Widgets\MissedConsumablesWidget::class)
    </div>
</x-filament-panels::page>
