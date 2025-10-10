<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\RoleResource\Pages;
use App\Filament\Resources\Tenant\RoleResource\RelationManagers;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Role;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class RoleResource extends Resource
{
    use HasPermissionBasedAccess;
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hidden for simplified user experience
    }
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Roles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->unique(ignoreRecord: true)
                            ->required(),
                        Forms\Components\Select::make('permissions')
                            ->relationship('permissions', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                    ])
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
                Tables\Columns\TextColumn::make('guard_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Role $record) {
                        // Don't allow deletion of super-admin role
                        if ($record->name === 'super-admin') {
                            // Return with error
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->deselectRecordsAfterCompletion()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, Collection $records) {
                            // Filter out super-admin role
                            $protectedRoles = ['super-admin', 'admin'];
                            
                            foreach ($records as $key => $record) {
                                if (in_array($record->name, $protectedRoles)) {
                                    $records->forget($key);
                                }
                            }
                            
                            if ($records->isEmpty()) {
                                $action->cancel();
                            }
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
