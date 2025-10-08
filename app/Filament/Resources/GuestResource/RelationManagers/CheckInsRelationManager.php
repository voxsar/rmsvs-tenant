<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Models\Meal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CheckInsRelationManager extends RelationManager
{
    protected static string $relationship = 'checkIns';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('date_of_arrival')
                    ->required(),
                Forms\Components\DateTimePicker::make('date_of_departure')
                    ->after('date_of_arrival'),
                Forms\Components\Select::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'room_no')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                        if ($state) {
                            $room = \App\Models\Room::find($state);
                            if ($room) {
                                $set('room', $room->room_no);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('room')
                    ->label('Room Number (Legacy)')
                    ->required()
                    ->maxLength(255)
                    ->helperText('This field is maintained for backwards compatibility'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('date_of_arrival')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_departure')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable()
                    ->searchable()
                    ->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),
                Tables\Columns\TextColumn::make('room')
                    ->label('Room Number (Legacy)')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('consumables_count')
                    ->label('Consumables')
                    ->counts('consumables'),
                Tables\Columns\TextColumn::make('meal_count')
                    ->label('Meals')
                    ->counts('mealRecords'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}