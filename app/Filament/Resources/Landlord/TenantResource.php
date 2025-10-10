<?php

namespace App\Filament\Resources\Landlord;

use App\Filament\Resources\Landlord\TenantResource\Pages;
use App\Filament\Resources\Landlord\TenantResource\RelationManagers;
use App\Filament\Table\Actions\DeleteBulkAction;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                            $subdomain = Str::snake($state);
                            $set('domain', $subdomain);
                        }
                        if (! $get('is_database_manually_changed')) {
                            $database = Str::snake($state) . '_db';
                            $set('database', $database);
                        }
                    }),
                
                Forms\Components\Hidden::make('is_domain_manually_changed')
                    ->default(false),
                
                Forms\Components\Hidden::make('is_database_manually_changed')
                    ->default(false),
                
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
                    ->dehydrated(true) // Always include in form submission
                    ->helperText(function (Forms\Get $get) {
                        if ($get('domain_type') === 'subdomain') {
                            return 'Auto-generated: ' . Str::snake($get('name')) . '.' . config('app.domain');
                        }
                        return 'Enter your custom domain';
                    })
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Forms\Set $set) {
                        $set('is_domain_manually_changed', true);
                    }),
                
                Forms\Components\TextInput::make('custom_domain')
                    ->required()
                    ->maxLength(255)
                    ->visible(fn (Forms\Get $get) => $get('domain_type') === 'domain'),
                
                Forms\Components\TextInput::make('database')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('tenant_db')
                    ->disabled(true) // Always disabled, auto-generated
                    ->dehydrated(true)
                    ->helperText(function (Forms\Get $get) {
                        return 'Auto-generated: ' . Str::snake($get('name')) . '_db';
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable()
                    ->searchable()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->label('Name'),
                Tables\Columns\TextColumn::make('domain')
                    ->sortable()
                    ->searchable()
                    ->label('Domain')
                    ->formatStateUsing(function ($state, $record = null) {
                        if (! $record || blank($state)) {
                            return '';
                        }

                        $state = trim($state);
                        $baseDomain = config('app.domain');

                        if (! $baseDomain) {
                            return $state;
                        }

                        if ($record->domain_type !== 'subdomain') {
                            return $state;
                        }

                        if (Str::endsWith($state, $baseDomain)) {
                            return $state;
                        }

                        if (! Str::contains($state, '.')) {
                            return $state . '.' . $baseDomain;
                        }

                        return $state;
                    }),
                Tables\Columns\TextColumn::make('custom_domain')
                    ->sortable()
                    ->searchable()
                    ->label('Custom Domain')
                    ->visible(function ($record) {
                        return $record && !empty($record->custom_domain);
                    }),
                Tables\Columns\TextColumn::make('database')
                    ->sortable()
                    ->searchable()
                    ->label('Database'),
                Tables\Columns\TextColumn::make('created_at')
                    ->sortable()
                    ->searchable()
                    ->dateTime()
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
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
