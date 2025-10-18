<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\MealRecordResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\MealRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MealRecordResource extends Resource
{
    use HasPermissionBasedAccess;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view meal-record');
    }

    protected static ?string $model = MealRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Guest Requests';

    protected static ?string $modelLabel = 'Meal';

    protected static ?string $pluralModelLabel = 'Meals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('guest_id')
                    ->relationship('guest', 'first_name', function ($query) {
                        return $query->orderBy('first_name');
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (! $state) {
                            return;
                        }

                        $guest = \App\Models\Guest::find($state);

                        // First try to get the latest active check-in
                        $latestCheckIn = \App\Models\CheckIn::where('guest_id', $state)
                            ->whereNull('date_of_departure')
                            ->latest('date_of_arrival')
                            ->first();

                        if ($latestCheckIn) {
                            // If found, use the room from the active check-in
                            $set('room_id', $latestCheckIn->room_id);
                        } elseif ($guest && $guest->type === 'RESIDENT' && $guest->assigned_room_id) {
                            // Fallback to assigned room if no active check-in
                            $set('room_id', $guest->assigned_room_id);
                        }
                    })
                    ->required(),
                Forms\Components\Select::make('meal_id')
                    ->relationship('meal', 'meal_type', function ($query) {
                        return $query->orderBy('id');
                    })
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        $week_days = implode(', ', $record->week_day);

                        return "{$record->meal_type} - {$week_days}";
                    })
                    ->searchable(['meal_type', 'week_day'])
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('room_id')
                    ->relationship('room', 'room_no', function ($query) {
                        return $query->orderBy('room_no');
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->room_no} {$record->building}")
                    ->searchable(['room_no'])
                    ->preload()
                    ->required()
                    ->helperText('For residential guests, this will be auto-populated based on their assigned room'),
                Forms\Components\DateTimePicker::make('date_of_transit')
                    ->label('Meal Date/Time')
                    ->default(now())
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('date_of_transit')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meal.meal_type')
                    ->sortable()
                    ->badge()
                    ->label('Meal'),
                Tables\Columns\TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->sortable(['guest.first_name'])
                    ->searchable(['guests.first_name', 'guests.last_name', 'guests.email'])
                    ->description(fn ($record) => $record->guest ? "Guest: {$record->guest->phone}, TRN: {$record->room->trn}" : null),
                Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable(['room.room_no'])
                    ->searchable(['rooms.room_no', 'rooms.building', 'rooms.floor'])
                    ->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),

            ])
            ->searchable()
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('update meal-record'))
                    ->tooltip(fn (Tables\Actions\EditAction $action): string => $action->isDisabled()
                        ? 'You don\'t have permission to edit meals'
                        : 'Edit this meal'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete meal-record'))
                        ->tooltip(fn (Tables\Actions\DeleteBulkAction $action): string => $action->isDisabled()
                            ? 'You don\'t have permission to delete meals'
                            : 'Delete selected meals'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMealRecords::route('/'),
            'create' => Pages\CreateMealRecord::route('/create'),
            'edit' => Pages\EditMealRecord::route('/{record}/edit'),
        ];
    }
}
