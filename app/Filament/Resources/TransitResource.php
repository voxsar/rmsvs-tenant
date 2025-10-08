<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransitResource\Pages;
use App\Filament\Resources\TransitResource\RelationManagers;
use App\Models\Transit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransitResource extends Resource
{
    protected static ?string $model = Transit::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'Apartment Management';
    protected static ?string $modelLabel = 'In/Out Record';
    protected static ?string $pluralModelLabel = 'In/Out Records';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
				Forms\Components\DateTimePicker::make('date_of_transit')
				->required(),
				Forms\Components\Select::make('transit_type')
					->options(Transit::TRANSIT_TYPES)
					->default('CHECKIN')
					->required(),
				Forms\Components\Select::make('room_id')
					->label('Room')
					->relationship('room', 'id')
					->preload()
					->searchable()
					->required(),
				Forms\Components\Select::make('guest_id')
					->relationship('guest', 'first_name', function ($query) {
						return $query->orderBy('first_name');
					})
					->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
					->searchable(['first_name', 'last_name', 'email'])
					->preload()
					->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
                Tables\Columns\TextColumn::make('guest.first_name')
				->label('Guest')
				->formatStateUsing(fn ($record) => "{$record->guest->first_name} {$record->guest->last_name}")
				->sortable()
				->searchable(['guest.first_name', 'guest.last_name']),
			Tables\Columns\TextColumn::make('room.room_no')
				->label('Room Number')
				->sortable()
				->searchable()
				->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),
			Tables\Columns\TextColumn::make('date_of_transit')
				->dateTime()
				->sortable(),
			Tables\Columns\TextColumn::make('transit_type')
				->badge()
				->color(fn (string $state): string => match ($state) {
					'CHECKIN' => 'success',
					'CHECKINOUT' => 'warning',
					'CHECKOUT' => 'info',
					default => 'gray',
				})
				->searchable(),
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
            'index' => Pages\ListTransits::route('/'),
            'create' => Pages\CreateTransit::route('/create'),
            'edit' => Pages\EditTransit::route('/{record}/edit'),
        ];
    }
}
