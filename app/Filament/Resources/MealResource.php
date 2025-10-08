<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MealResource\Pages;
use App\Filament\Resources\MealResource\RelationManagers;
use App\Models\Meal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MealResource extends Resource
{
    protected static ?string $model = Meal::class;

    protected static ?string $navigationIcon = 'heroicon-o-cake';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $modelLabel = 'Meals';
    protected static ?string $pluralModelLabel = 'Meals';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
					Forms\Components\TimePicker::make('range_start')
						->required(),
					Forms\Components\TimePicker::make('range_end')
						->required(),
				Forms\Components\Select::make('week_day')
					->options(Meal::WEEK_DAYS)
					->multiple()
					->required(),
                Forms\Components\Select::make('meal_type')
                    ->options(Meal::MEAL_TYPES)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('range_start')
                    ->dateTime()
                    ->sortable(),
				Tables\Columns\TextColumn::make('range_end')
					->dateTime()
					->sortable(),
                Tables\Columns\TextColumn::make('week_day')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Meal::MEAL_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('meal_type')
                    ->sortable()
                    ->badge()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('meal_type')
                    ->options(Meal::MEAL_TYPES),
				Tables\Filters\Filter::make('range_start')
					->form([
						Forms\Components\DatePicker::make('range start from'),
						Forms\Components\DatePicker::make('range start until'),
					])
					->query(function (Builder $query, array $data): Builder {
						return $query
							->when(
								$data['range start from'],
								fn (Builder $query, $date): Builder => $query->whereDate('range_start', '>=', $date),
							)
							->when(
								$data['range start until'],
								fn (Builder $query, $date): Builder => $query->whereDate('range_start', '<=', $date),
							);
					}),
				Tables\Filters\Filter::make('range_end')
					->form([
						Forms\Components\DatePicker::make('range end from'),
						Forms\Components\DatePicker::make('range end until'),
					])
					->query(function (Builder $query, array $data): Builder {
						return $query
							->when(
								$data['range end from'],
								fn (Builder $query, $date): Builder => $query->whereDate('range_end', '>=', $date),
							)
							->when(
								$data['range end until'],
								fn (Builder $query, $date): Builder => $query->whereDate('range_end', '<=', $date),
							);
					}),
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
            ])
            ->defaultSort('range_start', 'desc');
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
            'index' => Pages\ListMeals::route('/'),
            'create' => Pages\CreateMeal::route('/create'),
            'view' => Pages\ViewMeal::route('/{record}'),
            'edit' => Pages\EditMeal::route('/{record}/edit'),
        ];
    }
}
