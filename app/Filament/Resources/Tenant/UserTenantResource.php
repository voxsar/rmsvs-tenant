<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\UserTenantResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Role;
use App\Models\UserTenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserTenantResource extends Resource
{
    use HasPermissionBasedAccess;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view user');
    }

    protected static ?string $model = UserTenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->rule(Password::defaults()),
                        Forms\Components\Select::make('roles')
                            ->relationship('roles', 'name', function ($query) {
                                // Only show the predefined roles
                                return $query->whereIn('name', ['Manager', 'Senior', 'Junior', 'Scanner']);
                            })
                            ->multiple()
                            ->preload()
                            ->required()
                            ->helperText('Assign predefined roles to the user. Manager has full access, Senior handles operations, Junior does data entry, Scanner handles QR scanning.')
                            ->label('User Role'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Manager' => 'danger',
                        'Senior' => 'warning',
                        'Junior' => 'info',
                        'Scanner' => 'gray',
                        default => 'success',
                    })
                    ->label('Role'),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            // Removed role and permission relation managers for simplified user experience
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserTenants::route('/'),
            'create' => Pages\CreateUserTenant::route('/create'),
            'edit' => Pages\EditUserTenant::route('/{record}/edit'),
        ];
    }
}
