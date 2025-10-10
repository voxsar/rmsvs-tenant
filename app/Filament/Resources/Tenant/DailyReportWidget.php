<?php

namespace App\Filament\Resources\Tenant;

use App\Models\DailyReport;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class DailyReportWidget extends Widget
{
    use InteractsWithForms;
    
    protected static string $view = 'filament.widgets.daily-report-widget';
    
    public ?array $data = [];
    public ?string $selectedDate = null;
    
    // Form data property
    public $today;
    public $yesterday;
    public $selected_date;
    
    protected int | string | array $columnSpan = 'full';
    
    // Define formStatePath property
    protected function getFormStatePath(): string
    {
        return 'data';
    }
    
    public function mount(): void
    {
        $this->form->fill($this->getInitialFormData());
    }
    
    protected function getInitialFormData(): array
    {
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        $this->selectedDate = $yesterday;
        
        $todayReport = DailyReport::firstOrCreate(
            ['date' => $today],
            ['content' => '']
        );
        
        $yesterdayReport = DailyReport::firstOrCreate(
            ['date' => $yesterday],
            ['content' => '']
        );
        
        return [
            'today' => $todayReport->content,
            'yesterday' => $yesterdayReport->content,
            'selected_date' => $yesterday,
        ];
    }
    
    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath($this->getFormStatePath());
    }
    
    // Register forms - this is the key addition
    protected function getForms(): array
    {
        return [
            'form' => $this->makeForm()
                ->schema($this->getFormSchema())
                ->statePath($this->getFormStatePath()),
        ];
    }
    
    protected function getFormSchema(): array
    {
        return [
            Textarea::make('today')
                ->label('Today\'s Report')
                ->helperText('This will be auto-saved at midnight')
                ->rows(5)
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->saveReport(Carbon::today()->format('Y-m-d'), $state);
                })
                ->columnSpan('full'),
            
            DatePicker::make('selected_date')
                ->label('View Report For')
                ->default(Carbon::yesterday())
                ->reactive()
                ->afterStateUpdated(function ($state) {
                    $this->selectedDate = $state;
                    $this->loadPreviousReport();
                }),
                
            Textarea::make('yesterday')
                ->label(function () {
                    return 'Report for ' . Carbon::parse($this->selectedDate)->format('F j, Y');
                })
                ->rows(5)
                ->reactive()
                ->disabled(function () {
                    // Only allow editing yesterday and today, to prevent altering older records
                    return !in_array($this->selectedDate, [
                        Carbon::today()->format('Y-m-d'),
                        Carbon::yesterday()->format('Y-m-d'),
                    ]);
                })
                ->afterStateUpdated(function ($state) {
                    $this->saveReport($this->selectedDate, $state);
                })
                ->columnSpan('full'),
        ];
    }
    
    public function loadPreviousReport(): void
    {
        if (!$this->selectedDate) {
            return;
        }
        
        $report = DailyReport::firstOrCreate(
            ['date' => $this->selectedDate],
            ['content' => '']
        );
        
        $this->form->fill([
            'yesterday' => $report->content,
        ]);
    }
    
    protected function saveReport(string $date, string $content): void
    {
        DailyReport::updateOrCreate(
            ['date' => $date],
            ['content' => $content]
        );
    }
}