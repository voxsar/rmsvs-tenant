<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\RoomResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Room;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class RoomResource extends Resource
{
    use HasPermissionBasedAccess;

    // Show in navigation menu only if user has permission to view rooms
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view room');
    }

    protected static ?string $model = Room::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Rooms';

    protected static ?string $navigationGroup = 'Property Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Room Details')
                    ->schema([
                        Forms\Components\TextInput::make('room_no')
                            ->label('Room/Apartment Number')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('building')
                            ->label('Building')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('floor')
                            ->label('Floor')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'occupied' => 'Occupied',
                                'maintenance' => 'Under Maintenance',
                            ])
                            ->default('available')
                            ->required(),
                        Forms\Components\TextInput::make('max_occupants')
                            ->label('Maximum Occupants')
                            ->type('number')
                            ->default(1)
                            ->minValue(1)
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('room_no')
                    ->label('Room/Apartment Number')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('building')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('floor')
                    ->sortable()
                    ->searchable(),
                /*Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'occupied' => 'danger',
                        'maintenance' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),*/
                Tables\Columns\TextColumn::make('occupancy')
                    ->label('Occupancy')
                    ->formatStateUsing(function ($record) {
                        $currentOccupants = $record->getCurrentOccupantsCount();

                        return "{$currentOccupants} / {$record->max_occupants}";
                    })
                    ->badge()
                    ->color(function ($record) {
                        $currentOccupants = $record->getCurrentOccupantsCount();
                        if ($currentOccupants === 0) {
                            return 'gray';
                        }
                        if ($currentOccupants < $record->max_occupants) {
                            return 'success';
                        }

                        return 'warning';
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'occupied' => 'Occupied',
                        'maintenance' => 'Under Maintenance',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('update room'))
                    ->tooltip(fn (Tables\Actions\EditAction $action): string => $action->isDisabled()
                        ? 'You don\'t have permission to edit rooms'
                        : 'Edit this room'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete room'))
                        ->tooltip(fn (Tables\Actions\DeleteBulkAction $action): string => $action->isDisabled()
                            ? 'You don\'t have permission to delete rooms'
                            : 'Delete selected rooms'),
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
            'index' => Pages\ListRooms::route('/'),
            'create' => Pages\CreateRoom::route('/create'),
            'edit' => Pages\EditRoom::route('/{record}/edit'),
        ];
    }

    // Permission-based access controls
    public static function canCreate(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('create room');
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('update room');
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('delete room');
    }
}
