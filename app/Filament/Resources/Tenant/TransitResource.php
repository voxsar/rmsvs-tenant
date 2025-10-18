<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\TransitResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Transit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TransitResource extends Resource
{
    use HasPermissionBasedAccess;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view transit');
    }

    protected static ?string $model = Transit::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationGroup = 'Scans';

    protected static ?string $navigationLabel = 'Transit Log';

    protected static ?string $modelLabel = 'In/Out Record';

    protected static ?string $pluralModelLabel = 'In/Out Records';

    // Permission control for resource access
    public static function canAccess(): bool
    {
        return Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('view transit');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Forms\Components\DateTimePicker::make('date_of_transit')
                    ->default(now())
                    ->required(),
                Forms\Components\Select::make('transit_type')
                    ->options(Transit::TRANSIT_TYPES)
                    ->default('CHECKIN')
                    ->required(),
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
                        if ($guest && $guest->type === 'RESIDENT' && $guest->assigned_room_id) {
                            $set('room_id', $guest->assigned_room_id);
                        }
                    })
                    ->required(),
                Forms\Components\Select::make('room_id')
                    ->label('Room')
                    ->relationship('room', 'room_no', function ($query) {
                        return $query->orderBy('room_no');
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->room_no} {$record->building}")
                    ->searchable(['room_no'])
                    ->preload()
                    ->required()
                    ->helperText('For residential guests, this will be auto-populated based on their assigned room'),
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
                    ->searchable(['guests.first_name', 'guests.last_name', 'guests.email']),
                Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable()
                    ->searchable(['rooms.room_no', 'rooms.building', 'rooms.floor'])
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
            ->searchable()
            ->filters([
                //
                Tables\Filters\SelectFilter::make('transit_type')
                    ->options(Transit::TRANSIT_TYPES)
                    ->label('Transit Type'),
                Tables\Filters\Filter::make('date_of_transit')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_of_transit', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date_of_transit', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('update transit')),
                /*Tables\Actions\Action::make('process_checkin')
                    ->label('Process Check In')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        Auth::guard('tenant')->check() &&
                        $record->transit_type === 'CHECKIN' &&
                        !$record->processed_at &&
                        Auth::guard('tenant')->user()->can('process check-ins')
                    )
                    ->action(function ($record) {
                        $record->processed_at = now();
                        $record->processed_by = Auth::guard('tenant')->id();
                        $record->save();
                    }),*/
                Tables\Actions\Action::make('process_checkout')
                    ->label('Process Check Out')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->visible(fn ($record) => Auth::guard('tenant')->check() &&
                        $record->transit_type === 'CHECKOUT' &&
                        ! $record->processed_at &&
                        Auth::guard('tenant')->user()->can('process check-outs')
                    )
                    ->action(function ($record) {
                        $record->processed_at = now();
                        $record->processed_by = Auth::guard('tenant')->id();
                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('delete transit')),
                    Tables\Actions\BulkAction::make('process_bulk_checkins')
                        ->label('Process Selected Check Ins')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('process check-ins'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->transit_type === 'CHECKIN' && ! $record->processed_at) {
                                    $record->processed_at = now();
                                    $record->processed_by = Auth::guard('tenant')->id();
                                    $record->save();
                                }
                            }
                        }),
                    Tables\Actions\BulkAction::make('process_bulk_checkouts')
                        ->label('Process Selected Check Outs')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('info')
                        ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('process check-outs'))
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->transit_type === 'CHECKOUT' && ! $record->processed_at) {
                                    $record->processed_at = now();
                                    $record->processed_by = Auth::guard('tenant')->id();
                                    $record->save();
                                }
                            }
                        }),
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

    // Override canCreate method to control create page visibility
    public static function canCreate(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        return Auth::guard('tenant')->user()->can('create transit');
    }

    // Override canEdit method to control edit page visibility
    public static function canEdit(Model $record): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        return Auth::guard('tenant')->user()->can('update transit');
    }

    // Override canDelete method to control delete functionality
    public static function canDelete(Model $record): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        return Auth::guard('tenant')->user()->can('delete transit');
    }
}
