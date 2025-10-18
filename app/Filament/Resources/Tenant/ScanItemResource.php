<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\ScanItemResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\ScanItem;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;

class ScanItemResource extends Resource
{
    use HasPermissionBasedAccess;

    protected static ?string $model = ScanItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Scan Items';

    protected static ?string $navigationGroup = 'Scans';

    protected static ?string $modelLabel = 'Scan Item';

    protected static ?string $pluralModelLabel = 'Scan Items';

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Scan Item Details')
                    ->schema([
                        TextInput::make('name')
                            ->label('Item Name')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Item Type')
                            ->options(ScanItem::types())
                            ->required()
                            ->live(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive items are hidden from scanners and dashboard alerts.'),
                        Toggle::make('notify_if_missed')
                            ->label('Notify if Missed')
                            ->visible(fn (callable $get) => $get('type') === ScanItem::TYPE_CONSUMABLE)
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('type') === ScanItem::TYPE_CONSUMABLE ? (bool) $state : false)
                            ->helperText('Enable alerts when a consumable item is not scanned during its active window.'),
                    ])
                    ->columns(2),
                Section::make('Active Period')
                    ->schema([
                        Select::make('active_period_type')
                            ->label('Schedule Type')
                            ->options(ScanItem::periodTypes())
                            ->default(ScanItem::PERIOD_ALWAYS)
                            ->required()
                            ->live(),
                        TimePicker::make('active_start_time')
                            ->label('Start Time')
                            ->seconds(false)
                            ->visible(fn (callable $get) => in_array($get('active_period_type'), [ScanItem::PERIOD_WEEKDAYS], true))
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('active_period_type') === ScanItem::PERIOD_WEEKDAYS ? $state : null),
                        TimePicker::make('active_end_time')
                            ->label('End Time')
                            ->seconds(false)
                            ->visible(fn (callable $get) => in_array($get('active_period_type'), [ScanItem::PERIOD_WEEKDAYS], true))
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('active_period_type') === ScanItem::PERIOD_WEEKDAYS ? $state : null),
                        CheckboxList::make('active_days')
                            ->label('Active Days')
                            ->options([
                                'MONDAY' => 'Monday',
                                'TUESDAY' => 'Tuesday',
                                'WEDNESDAY' => 'Wednesday',
                                'THURSDAY' => 'Thursday',
                                'FRIDAY' => 'Friday',
                                'SATURDAY' => 'Saturday',
                                'SUNDAY' => 'Sunday',
                            ])
                            ->columns(2)
                            ->visible(fn (callable $get) => $get('active_period_type') === ScanItem::PERIOD_WEEKDAYS)
                            ->default(fn () => ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'])
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('active_period_type') === ScanItem::PERIOD_WEEKDAYS
                                ? array_values(array_filter((array) $state))
                                : null),
                        Repeater::make('custom_windows')
                            ->label('Custom Windows')
                            ->schema([
                                CheckboxList::make('days')
                                    ->label('Days')
                                    ->options([
                                        'MONDAY' => 'Monday',
                                        'TUESDAY' => 'Tuesday',
                                        'WEDNESDAY' => 'Wednesday',
                                        'THURSDAY' => 'Thursday',
                                        'FRIDAY' => 'Friday',
                                        'SATURDAY' => 'Saturday',
                                        'SUNDAY' => 'Sunday',
                                    ])
                                    ->columns(2),
                                TimePicker::make('start')
                                    ->label('Start Time')
                                    ->required()
                                    ->seconds(false),
                                TimePicker::make('end')
                                    ->label('End Time')
                                    ->required()
                                    ->seconds(false),
                            ])
                            ->default([])
                            ->collapsed()
                            ->visible(fn (callable $get) => $get('active_period_type') === ScanItem::PERIOD_CUSTOM)
                            ->dehydrateStateUsing(fn ($state, callable $get) => $get('active_period_type') === ScanItem::PERIOD_CUSTOM
                                ? collect($state ?? [])->map(function (array $window) {
                                    $days = array_values(array_filter($window['days'] ?? []));

                                    return [
                                        'days' => $days,
                                        'start' => $window['start'] ?? null,
                                        'end' => $window['end'] ?? null,
                                    ];
                                })->filter(function (array $window) {
                                    return ($window['start'] ?? null) && ($window['end'] ?? null);
                                })->values()->all()
                                : null)
                            ->grid(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ScanItem::TYPE_ACCESS => 'success',
                        ScanItem::TYPE_MEAL => 'info',
                        ScanItem::TYPE_CONSUMABLE => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => Arr::get(ScanItem::types(), $state, ucfirst($state)))
                    ->sortable(),
                TextColumn::make('active_period_summary')
                    ->label('Active Period')
                    ->wrap()
                    ->sortable(),
                IconColumn::make('notify_if_missed')
                    ->label('Notify')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Item Type')
                    ->options(ScanItem::types()),
                SelectFilter::make('active_period_type')
                    ->label('Schedule Type')
                    ->options(ScanItem::periodTypes()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScanItems::route('/'),
            'create' => Pages\CreateScanItem::route('/create'),
            'edit' => Pages\EditScanItem::route('/{record}/edit'),
        ];
    }
}
