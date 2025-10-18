<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\CheckInResource\Pages;
use App\Filament\Resources\Tenant\CheckInResource\RelationManagers;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\CheckIn;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CheckInResource extends Resource
{
    use HasPermissionBasedAccess;

    public static function shouldRegisterNavigation(): bool
    {
        return true; // Auth::guard('tenant')->check() &&
        // Auth::guard('tenant')->user()->can('view check-in');
    }

    protected static ?string $model = CheckIn::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?string $modelLabel = 'Check-In';

    protected static ?string $pluralModelLabel = 'Manual Check-Ins';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Check-In Details')
                    ->schema([
                        Forms\Components\Select::make('guest_id')
                            ->relationship('guest', 'first_name', function ($query) {
                                return $query->orderBy('first_name');
                            })
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name}")
                            ->searchable(['first_name', 'last_name', 'email'])
                            ->preload()
                            ->required(),
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
                            ->helperText('Multiple guests can be checked into the same room'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn ($record) => "{$record->guest->first_name} {$record->guest->last_name}")
                    ->sortable(['guests.first_name', 'guests.last_name'])
                    ->searchable(['guests.first_name', 'guests.last_name']),
                Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable(['room.room_no'])
                    ->searchable(['room.room_no'])
                    ->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),
                Tables\Columns\ImageColumn::make('qr_code')
                    ->label('QR Code')
                    ->action(
                        Tables\Actions\Action::make('viewQrCode')
                            ->label('View QR')
                            ->modalHeading('Room Access QR Code')
                            ->modalDescription(fn ($record) => "QR Code for {$record->guest->first_name} {$record->guest->last_name}, Room {$record->room->room_no}")
                            ->modalContent(fn ($record) => view('filament.resources.qr-code-modal', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                    )
                    ->square()
                    ->visibility('private')
                    ->disk('public')
                    ->size(40),
                Tables\Columns\TextColumn::make('date_of_arrival')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_departure')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Currently Checked In')
                    ->query(fn (Builder $query): Builder => $query->whereNull('date_of_departure')),
                Tables\Filters\Filter::make('checked_out')
                    ->label('Checked Out')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('date_of_departure')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->disabled(fn () => ! Auth::guard('tenant')->user()->can('update check-in')),
                Tables\Actions\Action::make('viewQrCode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading('Room Access QR Code')
                    ->modalDescription(fn ($record) => "QR Code for {$record->guest->first_name} {$record->guest->last_name}, Room {$record->room->room_no}")
                    ->modalContent(fn ($record) => view('filament.resources.qr-code-modal', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->visible(fn ($record) => $record->qr_code !== null),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn () => ! Auth::guard('tenant')->user()->can('delete check-in')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn () => ! Auth::guard('tenant')->user()->can('delete check-in')),
                    // ->tooltip(fn (bool $disabled) => $disabled
                    //   ? 'You don\'t have permission to delete check-ins'
                    //  : 'Delete selected check-ins'),
                ]),
            ])
            ->defaultSort('date_of_arrival', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ConsumablesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCheckIns::route('/'),
            'create' => Pages\CreateCheckIn::route('/create'),
            'multi-guest' => Pages\MultiGuestCheckIn::route('/multi-guest'),
            'view' => Pages\ViewCheckIn::route('/{record}'),
            'edit' => Pages\EditCheckIn::route('/{record}/edit'),
        ];
    }
}
