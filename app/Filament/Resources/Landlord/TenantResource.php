<?php

namespace App\Filament\Resources\Landlord;

use App\Filament\Resources\Landlord\TenantResource\Pages;
use App\Filament\Table\Actions\DeleteBulkAction;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                        if (! $get('is_domain_manually_changed')) {
                            $set('domain', Str::slug($state));
                        }
                        if (! $get('is_database_manually_changed')) {
                            $set('database', Str::snake($state) . '_db');
                        }
                    }),

                Forms\Components\Hidden::make('is_domain_manually_changed')->default(false),
                Forms\Components\Hidden::make('is_database_manually_changed')->default(false),

                Forms\Components\Select::make('domain_type')
                    ->options([
                        'subdomain' => 'Subdomain (under ' . config('app.domain') . ')',
                        'domain' => 'Custom Domain'
                    ])
                    ->default('subdomain')
                    ->reactive()
                    ->required(),

                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('subdomain')
                    ->disabled(fn (Forms\Get $get) => $get('domain_type') === 'subdomain')
                    ->dehydrated(true)
                    ->helperText(function (Forms\Get $get) {
                        return $get('domain_type') === 'subdomain'
                            ? 'Auto-generated: ' . Str::slug($get('name')) . '.' . config('app.domain')
                            : 'Enter your custom domain';
                    })
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('is_domain_manually_changed', true)),

                Forms\Components\TextInput::make('custom_domain')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('domain_type') === 'domain'),

                Forms\Components\TextInput::make('database')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('tenant_db')
                    ->disabled(true)
                    ->dehydrated(true)
                    ->helperText(fn (Forms\Get $get) => 'Auto-generated: ' . Str::snake($get('name')) . '_db'),

                // 🏠 Initial Rooms Section
                Forms\Components\Repeater::make('initial_rooms')
                    ->label('Initial Rooms')
                    ->schema([
                        Forms\Components\TextInput::make('room_no')
                            ->label('Room Number')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('building')->maxLength(255),
                        Forms\Components\TextInput::make('floor')->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'occupied' => 'Occupied',
                                'maintenance' => 'Maintenance',
                            ])
                            ->default('available'),
                        Forms\Components\TextInput::make('max_occupants')
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\Textarea::make('description')->rows(2),
                    ])
                    ->collapsed()
                    ->dehydrated(false)
                    ->createItemButtonLabel('Add Room'),

                // 👤 Administrator Section
                Forms\Components\Section::make('Administrator')
                    ->description('Set up the initial administrator for this tenant.')
                    ->schema([
                        Forms\Components\TextInput::make('admin_name')
                            ->label('Admin Name')
                            ->required(fn (string $context) => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_email')
                            ->label('Admin Email')
                            ->email()
                            ->required(fn (string $context) => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Admin Password')
                            ->password()
                            ->required(fn (string $context) => $context === 'create')
                            ->confirmed()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('admin_password_confirmation')
                            ->label('Confirm Password')
                            ->password()
                            ->required(fn (string $context) => $context === 'create')
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable()->label('ID'),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable()->label('Name'),
                Tables\Columns\TextColumn::make('domain')
                    ->sortable()
                    ->searchable()
                    ->label('Domain')
                    ->formatStateUsing(function ($state, $record = null) {
                        if (! $record || blank($state)) return '';
                        $baseDomain = config('app.domain');
                        if ($record->domain_type !== 'subdomain') return $state;
                        if (! Str::contains($state, '.')) return $state . '.' . $baseDomain;
                        return $state;
                    }),
                Tables\Columns\TextColumn::make('custom_domain')
                    ->sortable()
                    ->searchable()
                    ->label('Custom Domain')
                    ->visible(fn ($record) => $record && !empty($record->custom_domain)),
                Tables\Columns\TextColumn::make('database')->sortable()->searchable()->label('Database'),
                Tables\Columns\TextColumn::make('created_at')->sortable()->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
