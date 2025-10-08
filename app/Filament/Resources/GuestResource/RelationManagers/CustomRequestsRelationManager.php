<?php

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Models\CustomRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CustomRequestsRelationManager extends RelationManager
{
    protected static string $relationship = 'customRequests';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('request_type')
                    ->options(CustomRequest::REQUEST_TYPES)
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options(CustomRequest::REQUEST_STATUSES)
                    ->default('PENDING')
                    ->required(),
                Forms\Components\Textarea::make('response_msg')
                    ->label('Response Message')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('request_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CustomRequest::REQUEST_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'DENIED' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => CustomRequest::REQUEST_STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('responded_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('request_type')
                    ->options(CustomRequest::REQUEST_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->options(CustomRequest::REQUEST_STATUSES),
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