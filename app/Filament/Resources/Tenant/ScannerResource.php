<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\ScannerResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Scanner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ScannerResource extends Resource
{
    use HasPermissionBasedAccess;

    // Show in navigation menu only if user has permission to view rooms
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view scanner');
    }

    protected static ?string $model = Scanner::class;

    protected static ?string $navigationIcon = 'heroicon-o-qr-code';

    protected static ?string $navigationLabel = 'Scanners';

    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Scanner Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Scanner Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('location')
                            ->label('Location')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->options([
                                'door' => 'Door',
                                'gate' => 'Gate',
                                'consumable' => 'Consumable',
                                'restaurant' => 'Restaurant',
                            ])
                            ->default('door')
                            ->required(),
                        // Only show scan URL for existing records
                        Forms\Components\View::make('filament.resources.scanner-url-view')
                            ->visible(fn ($record) => $record !== null && $record->exists),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Scanner Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('location')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'door' => 'success',
                        'gate' => 'warning',
                        'restaurant' => 'info',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\ViewColumn::make('scan_url')
                    ->label('Scan URL')
                    ->view('filament.tables.columns.scanner-url-column'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'door' => 'Door',
                        'gate' => 'Gate',
                        'consumable' => 'Consumable',
                        'restaurant' => 'Restaurant',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('qrCode')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn (Scanner $record) => route('scanner.scan', $record))
                    ->openUrlInNewTab()
                    ->color('success'),
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
            'index' => Pages\ListScanners::route('/'),
            'create' => Pages\CreateScanner::route('/create'),
            'edit' => Pages\EditScanner::route('/{record}/edit'),
        ];
    }
}
