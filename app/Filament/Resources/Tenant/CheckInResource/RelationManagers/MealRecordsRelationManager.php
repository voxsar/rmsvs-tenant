<?php

namespace App\Filament\Resources\Tenant\CheckInResource\RelationManagers;

use App\Models\MealRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MealRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'mealRecords';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('date_of_transit')
                    ->required(),
                Forms\Components\Select::make('transit_type')
                    ->options(MealRecord::MEAL_TYPES)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transit_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => MealRecord::MEAL_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('transit_type')
                    ->options(MealRecord::MEAL_TYPES),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }
}
