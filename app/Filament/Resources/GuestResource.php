<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GuestResource\Pages;
use App\Filament\Resources\GuestResource\RelationManagers;
use App\Models\Guest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class GuestResource extends Resource
{
    protected static ?string $model = Guest::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Population';
    protected static ?string $modelLabel = 'Guest';
    protected static ?string $pluralModelLabel = 'Population';
    protected static ?string $navigationGroup = 'Population Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('TRN')
                            ->label('TRN')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->required(),
                        Forms\Components\TextInput::make('middle_name')
                            ->label('Middle Name'),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->required(),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->required(),
                        Forms\Components\TextInput::make('nationality')
                            ->required(),
                        Forms\Components\Select::make('marital_status')
                            ->options([
                                'SINGLE' => 'Single',
                                'MARRIED' => 'Married',
                            ])
                            ->required(),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label('Date of Birth')
                            ->required(),
                        Forms\Components\Select::make('sex')
                            ->options([
                                'MALE' => 'Male',
                                'FEMALE' => 'Female',
                            ])
                            ->required(),
                        Forms\Components\Select::make('age_type')
                            ->options([
                                'CHILD' => 'Child',
                                'ADULT' => 'Adult',
                                'SENIOR' => 'Senior',
                            ])
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\FileUpload::make('photo')
                            ->image()
                            ->directory('resident-photos'),
                        Forms\Components\Textarea::make('medical_history')
                            ->rows(3),
                        Forms\Components\Select::make('type')
                            ->options([
                                'RESIDENT' => 'Resident',
                                'Staff' => 'Staff',
                                'VISITORS' => 'Visitor',
                            ])
                            ->default('RESIDENT')
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('TRN')
                    ->searchable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RESIDENT' => 'success',
                        'STAFF' => 'warning',
                        'VISITORS' => 'danger',
                    }),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'RESIDENT' => 'Resident',
                        'Staff' => 'Staff',
                        'VISITORS' => 'Visitor',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CheckInsRelationManager::class,
            RelationManagers\CustomRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuests::route('/'),
            'create' => Pages\CreateGuest::route('/create'),
            'view' => Pages\ViewGuest::route('/{record}'),
            'edit' => Pages\EditGuest::route('/{record}/edit'),
        ];
    }
}
