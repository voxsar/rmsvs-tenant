<?php

namespace App\Filament\Resources;

use Log;
use App\Filament\Resources\MealRecordResource\Pages;
use App\Filament\Resources\MealRecordResource\RelationManagers;
use App\Models\Transit;
use App\Models\MealRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MealRecordResource extends Resource
{
    protected static ?string $model = MealRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Apartment Management';
    protected static ?string $modelLabel = 'Meal Record';
    protected static ?string $pluralModelLabel = 'Meal Records';

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
                    ->required(),
				Forms\Components\Select::make('meal_id')
					->relationship('meal', 'meal_type', function ($query) {
						return $query->orderBy('id');
					})
					->getOptionLabelFromRecordUsing(function ($record){
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
					->required(),
                Forms\Components\DateTimePicker::make('date_of_transit')
                    ->required(),
                Forms\Components\Select::make('transit_type')
                    ->options(Transit::TRANSIT_TYPES)
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
                Tables\Columns\TextColumn::make('transit_type')
					->sortable()
                    ->badge()
                    ->toggleable(),
				Tables\Columns\TextColumn::make('meal.meal_type')
					->sortable()
					->badge()
					->label('Meal'),
				Tables\Columns\TextColumn::make("guest.first_name")
                    ->label('Guest')
                    ->sortable(['guest.first_name'])
                    ->searchable(['guest.first_name'])
                    ->description(fn ($record) => $record->guest ? "Guest: {$record->guest->phone}, TRN: {$record->room->trn}" : null),
				Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable(['room.room_no'])
                    ->searchable(['room.room_no'])
                    ->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),
                
                
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
