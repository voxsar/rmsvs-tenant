<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\ConsumableResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\Consumable;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ConsumableResource extends Resource
{
    use HasPermissionBasedAccess;

    protected static ?string $model = Consumable::class;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view consumable');
    }

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01),
                Forms\Components\Toggle::make('is_visible')
                    ->label('Visible in Consumables List')
                    ->default(true)
                    ->helperText('If disabled, this consumable will not appear in the regular list of consumables.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
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
                Tables\Actions\ViewAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('view consumable'))
                    ->tooltip(fn (Tables\Actions\ViewAction $action): string => $action->isDisabled()
                        ? 'You don\'t have permission to view consumables'
                        : 'View this consumable'),
                Tables\Actions\EditAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('update consumable'))
                    ->tooltip(fn (Tables\Actions\EditAction $action): string => $action->isDisabled()
                        ? 'You don\'t have permission to edit consumables'
                        : 'Edit this consumable'),
                Tables\Actions\DeleteAction::make()
                    ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete consumable'))
                    ->tooltip(fn (Tables\Actions\DeleteAction $action): string => $action->isDisabled()
                        ? 'You don\'t have permission to delete consumables'
                        : 'Delete this consumable'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->disabled(fn (): bool => ! Auth::guard('tenant')->user()->can('delete consumable'))
                        ->tooltip(fn (Tables\Actions\DeleteBulkAction $action): string => $action->isDisabled()
                            ? 'You don\'t have permission to delete consumables'
                            : 'Delete selected consumables'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConsumables::route('/'),
            'create' => Pages\CreateConsumable::route('/create'),
            'view' => Pages\ViewConsumable::route('/{record}'),
            'edit' => Pages\EditConsumable::route('/{record}/edit'),
        ];
    }
}
