<?php

namespace App\Filament\Widgets;

use App\Models\DailyReport;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class LastShiftReportWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.last-shift-report-widget';

    protected static ?string $heading = 'Last Shift Report';

    public ?array $data = [];

    public ?string $selectedDate = null;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 2,
    ];

    public function mount(): void
    {
        $this->selectedDate = Carbon::yesterday()->toDateString();
        $this->form->fill($this->getInitialFormState());
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema($this->getFormSchema())
                ->statePath('data'),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('selected_date')
                ->label('Shift Date')
                ->default(fn () => Carbon::parse($this->selectedDate))
                ->maxDate(Carbon::today())
                ->reactive()
                ->afterStateHydrated(function (DatePicker $component, $state): void {
                    if (! $state) {
                        $component->state($this->selectedDate);
                    }
                })
                ->afterStateUpdated(function ($state): void {
                    $this->selectedDate = $state;
                    $this->reloadReport();
                }),
            Textarea::make('report')
                ->label('Report Notes')
                ->rows(8)
                ->placeholder('No report has been logged for this shift.')
                ->reactive()
                ->disabled(fn (): bool => ! $this->canEditSelectedDate())
                ->afterStateHydrated(function (Textarea $component, $state): void {
                    if ($state === null && $this->selectedDate) {
                        $component->state($this->resolveReport($this->selectedDate)?->content ?? '');
                    }
                })
                ->afterStateUpdated(function ($state): void {
                    $this->persistReport($state);
                }),
        ];
    }

    protected function getInitialFormState(): array
    {
        $report = $this->resolveReport($this->selectedDate, createIfMissing: true);

        return [
            'selected_date' => $this->selectedDate,
            'report' => $report?->content,
        ];
    }

    protected function reloadReport(): void
    {
        $report = $this->resolveReport($this->selectedDate);

        $this->form->fill([
            'selected_date' => $this->selectedDate,
            'report' => $report?->content,
        ]);
    }

    protected function persistReport(?string $content): void
    {
        if (! $this->selectedDate || ! $this->canEditSelectedDate()) {
            return;
        }

        DailyReport::updateOrCreate(
            ['date' => $this->selectedDate],
            ['content' => $content ?? '']
        );
    }

    protected function resolveReport(?string $date, bool $createIfMissing = false): ?DailyReport
    {
        if (! $date) {
            return null;
        }

        $report = DailyReport::whereDate('date', $date)->first();

        if ($report) {
            return $report;
        }

        if (! $createIfMissing) {
            return null;
        }

        return DailyReport::create([
            'date' => $date,
            'content' => '',
        ]);
    }

    protected function getViewData(): array
    {
        $reportContent = Arr::get($this->data, 'report');

        if ($reportContent === null && $this->selectedDate) {
            $reportContent = $this->resolveReport($this->selectedDate)?->content;
        }

        return [
            'selectedDate' => $this->selectedDate,
            'reportIsMissing' => blank($reportContent),
            'isEditable' => $this->canEditSelectedDate(),
        ];
    }

    protected function canEditSelectedDate(): bool
    {
        if (! $this->selectedDate) {
            return false;
        }

        $allowed = [
            Carbon::today()->toDateString(),
            Carbon::yesterday()->toDateString(),
        ];

        return in_array($this->selectedDate, $allowed, true);
    }
}
