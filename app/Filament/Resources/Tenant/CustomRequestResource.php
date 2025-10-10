<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\CustomRequestResource\Pages;
use App\Filament\Resources\Tenant\CustomRequestResource\RelationManagers;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\CustomRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CustomRequestResource extends Resource
{
    use HasPermissionBasedAccess;
	
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() && 
               Auth::guard('tenant')->user()->can('view guest-request');
    }
    protected static ?string $model = CustomRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Guest Requests';
    protected static ?string $navigationLabel = 'Guest Requests';
    protected static ?string $modelLabel = 'Guest Request';
    protected static ?string $pluralModelLabel = 'Guest Requests';

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
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        if (!$state) return;
                        
                        $guest = \App\Models\Guest::find($state);
                        
                        // First try to get the latest active check-in
                        $latestCheckIn = \App\Models\CheckIn::where('guest_id', $state)
                            ->whereNull('date_of_departure')
                            ->latest('date_of_arrival')
                            ->first();
                            
                        if ($latestCheckIn) {
                            // If found, use the room from the active check-in
                            $set('room_id', $latestCheckIn->room_id);
                        } elseif ($guest && $guest->type === 'RESIDENT' && $guest->assigned_room_id) {
                            // Fallback to assigned room if no active check-in
                            $set('room_id', $guest->assigned_room_id);
                        }
                    })
                    ->required(),
				Forms\Components\Select::make('room_id')
					->relationship('room', 'room_no', function ($query) {
						return $query->orderBy('room_no');
					})
					->getOptionLabelFromRecordUsing(fn ($record) => "{$record->room_no} {$record->building}")
					->searchable(['room_no'])
					->preload()
					->required()
                    ->helperText('For residential guests, this will be auto-populated based on their assigned room'),
                Forms\Components\Select::make('request_type')
                    ->options(CustomRequest::REQUEST_TYPES)
                    ->live()
                    ->required(),
                Forms\Components\Select::make('consumable_id')
                    ->relationship('consumable', 'name', function ($query) {
                        return $query->orderBy('name')->where('name', '!=', 'Late Dinner');
                    })
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name}")
                    ->searchable(['name'])
                    ->preload()
                    ->required()
                    ->visible(fn (callable $get) => $get('request_type') === 'CONSUMABLE'),
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
                Forms\Components\DateTimePicker::make('responded_at')
                    ->label('Responded At')
                    ->hidden(fn ($livewire) => $livewire instanceof Pages\CreateCustomRequest),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('guest.first_name')
                    ->label('Guest')
                    ->formatStateUsing(fn ($record) => "{$record->guest->first_name} {$record->guest->last_name}")
                    ->sortable()
                    ->searchable(['guests.first_name', 'guests.last_name']),
				Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room Number')
                    ->sortable(['room.room_no'])
                    ->searchable(['rooms.room_no'])
                    ->description(fn ($record) => $record->room ? "Building: {$record->room->building}, Floor: {$record->room->floor}" : null),
                Tables\Columns\TextColumn::make('request_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => CustomRequest::REQUEST_TYPES[$state] ?? $state),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->searchable(),
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
            ->searchable()
            ->filters([
                Tables\Filters\SelectFilter::make('request_type')
                    ->options(CustomRequest::REQUEST_TYPES),
                Tables\Filters\SelectFilter::make('status')
                    ->options(CustomRequest::REQUEST_STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('view guest-request'))
                    ->tooltip(fn (Tables\Actions\ViewAction $action): string => $action->isDisabled() 
                        ? 'You don\'t have permission to view guest requests' 
                        : 'View this request'),
                Tables\Actions\EditAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('update guest-request'))
                    ->tooltip(fn (Tables\Actions\EditAction $action): string => $action->isDisabled() 
                        ? 'You don\'t have permission to edit guest requests' 
                        : 'Edit this request'),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete guest-request'))
                    ->tooltip(fn (Tables\Actions\DeleteAction $action): string => $action->isDisabled() 
                        ? 'You don\'t have permission to delete guest requests' 
                        : 'Delete this request'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete guest-request'))
                        ->tooltip(fn (Tables\Actions\DeleteBulkAction $action): string => $action->isDisabled() 
                            ? 'You don\'t have permission to delete guest requests' 
                            : 'Delete selected requests'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListCustomRequests::route('/'),
            'create' => Pages\CreateCustomRequest::route('/create'),
            'view' => Pages\ViewCustomRequest::route('/{record}'),
            'edit' => Pages\EditCustomRequest::route('/{record}/edit'),
        ];
    }
}
