<?php

namespace App\Filament\Resources\Tenant;

use App\Filament\Resources\Tenant\ActivityRecordResource\Pages;
use App\Filament\Traits\HasPermissionBasedAccess;
use App\Models\CheckIn;
use App\Models\CustomRequest;
use App\Models\MealRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivityRecordResource extends Resource
{
    use HasPermissionBasedAccess;

    protected static ?string $model = CheckIn::class; // We'll use CheckIn as the base model but override the query

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Scans';

    protected static ?string $navigationLabel = 'Scan History';

    protected static ?string $modelLabel = 'Activity Record';

    protected static ?string $pluralModelLabel = 'Activity Records';

    protected static ?int $navigationSort = 1; // Show at top of Scans group

    public static function shouldRegisterNavigation(): bool
    {
        // Check if user has permission to view any of the record types
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $user = Auth::guard('tenant')->user();

        return $user->can('view check-in') ||
               $user->can('view guest-request') ||
               $user->can('view meal-record');
    }

    public static function canAccess(): bool
    {
        if (! Auth::guard('tenant')->check()) {
            return false;
        }

        $user = Auth::guard('tenant')->user();

        return $user->can('view check-in') ||
               $user->can('view guest-request') ||
               $user->can('view meal-record');
    }

    public static function form(Form $form): Form
    {
        // This resource is primarily for viewing, individual forms will be handled by specific resources
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getUnifiedQuery())
            ->columns([
                Tables\Columns\TextColumn::make('activity_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        in_array($state, ['Access', 'Check-In', 'Check-Out']) => 'success',
                        $state === 'Guest Request' => 'warning',
                        in_array($state, ['Meal', 'Meal Record']) => 'info',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Guest')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name']),

                Tables\Columns\TextColumn::make('room_no')
                    ->label('Room')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->room_building ? "Building: {$record->room_building}, Floor: {$record->room_floor}" : null),

                Tables\Columns\TextColumn::make('description')
                    ->label('Details')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'PENDING' => 'warning',
                        'APPROVED' => 'success',
                        'DENIED' => 'danger',
                        'Active' => 'success',
                        'Completed' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activity_type')
                    ->label('Activity Type')
                    ->options([
                        'Access' => 'Access',
                        'Guest Request' => 'Guest Request',
                        'Meal' => 'Meal',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'PENDING' => 'Pending',
                        'APPROVED' => 'Approved',
                        'DENIED' => 'Denied',
                        'Active' => 'Active',
                        'Completed' => 'Completed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => static::getRecordUrl($record))
                    ->openUrlInNewTab(false),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_checkin')
                    ->label('New Check-In')
                    ->icon('heroicon-o-key')
                    ->color('success')
                    ->url(route('filament.admin.resources.tenant.check-ins.create'))
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('create check-in')),

                Tables\Actions\Action::make('create_request')
                    ->label('New Guest Request')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('warning')
                    ->url(route('filament.admin.resources.tenant.custom-requests.create'))
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('create guest-request')),

                Tables\Actions\Action::make('create_meal')
                    ->label('New Meal')
                    ->icon('heroicon-o-cake')
                    ->color('info')
                    ->url(route('filament.admin.resources.tenant.meal-records.create'))
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('create meal-record')),
            ])
            ->bulkActions([
                // No bulk actions for this unified view
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('delete')
                        ->label('Delete Selected')
                        ->icon('heroicon-o-trash')
                        ->action(fn (Collection $records) => $records->each->delete())
                        ->requiresConfirmation(),

                    // approve button
                    Tables\Actions\BulkAction::make('approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->action(function (Collection $records) {
                            foreach ($records as $record) {
                                if ($record->status === 'PENDING') {
                                    $record->update(['status' => 'APPROVED']);
                                }
                            }
                        })
                        ->requiresConfirmation(),

                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function getUnifiedQuery(): Builder
    {
        // Create union query using raw SQL wrapped in a proper Eloquent builder
        $checkInsQuery = CheckIn::query()
            ->select([
                DB::raw("'Access' as activity_type"),
                'check_ins.id',
                'check_ins.guest_id',
                'check_ins.room_id',
                DB::raw("CONCAT(guests.first_name, ' ', guests.last_name) as guest_name"),
                'guests.first_name as guest_first_name',
                'guests.last_name as guest_last_name',
                'rooms.room_no',
                'rooms.building as room_building',
                'rooms.floor as room_floor',
                DB::raw("CASE 
                    WHEN check_ins.date_of_departure IS NULL THEN 'Active Check-In'
                    ELSE 'Check-Out Completed'
                END as description"),
                DB::raw("CASE 
                    WHEN check_ins.date_of_departure IS NULL THEN 'Active'
                    ELSE 'Completed'
                END as status"),
                'check_ins.created_at',
                'check_ins.updated_at',
                DB::raw("'check_in' as record_type"),
            ])
            ->join('guests', 'check_ins.guest_id', '=', 'guests.id')
            ->join('rooms', 'check_ins.room_id', '=', 'rooms.id');

        $requestsQuery = CustomRequest::query()
            ->select([
                'custom_requests.activity_type as activity_type',
                'custom_requests.id',
                'custom_requests.guest_id',
                'custom_requests.room_id',
                DB::raw("CONCAT(guests.first_name, ' ', guests.last_name) as guest_name"),
                'guests.first_name as guest_first_name',
                'guests.last_name as guest_last_name',
                'rooms.room_no',
                'rooms.building as room_building',
                'rooms.floor as room_floor',
                DB::raw("CONCAT(COALESCE(custom_requests.request_type, 'Request'), ': ', COALESCE(custom_requests.description, 'No description')) as description"),
                'custom_requests.status',
                'custom_requests.created_at',
                'custom_requests.updated_at',
                DB::raw("'custom_request' as record_type"),
            ])
            ->join('guests', 'custom_requests.guest_id', '=', 'guests.id')
            ->join('rooms', 'custom_requests.room_id', '=', 'rooms.id');

        $mealRecordsQuery = MealRecord::query()
            ->select([
                DB::raw("'Meal' as activity_type"),
                'meal_records.id',
                'meal_records.guest_id',
                'meal_records.room_id',
                DB::raw("CONCAT(guests.first_name, ' ', guests.last_name) as guest_name"),
                'guests.first_name as guest_first_name',
                'guests.last_name as guest_last_name',
                'rooms.room_no',
                'rooms.building as room_building',
                'rooms.floor as room_floor',
                DB::raw("CONCAT('Meal: ', COALESCE(meals.meal_type, 'Unknown'), ' at ', DATE_FORMAT(meal_records.date_of_transit, '%Y-%m-%d %H:%i')) as description"),
                DB::raw("'Completed' as status"),
                'meal_records.created_at',
                'meal_records.updated_at',
                DB::raw("'meal_record' as record_type"),
            ])
            ->join('guests', 'meal_records.guest_id', '=', 'guests.id')
            ->join('rooms', 'meal_records.room_id', '=', 'rooms.id')
            ->leftJoin('meals', 'meal_records.meal_id', '=', 'meals.id');

        // Use unionAll to preserve all records and maintain column order from first query
        $unifiedQuery = $checkInsQuery
            ->unionAll($requestsQuery)
            ->unionAll($mealRecordsQuery);

        // Log::debug('Unified Query: ' . $unifiedQuery->get());
        return $unifiedQuery;
    }

    protected static function getRecordUrl($record): string
    {
        return match ($record->record_type) {
            'check_in' => route('filament.admin.resources.tenant.check-ins.view', ['record' => $record->id]),
            'custom_request' => route('filament.admin.resources.tenant.custom-requests.view', ['record' => $record->id]),
            'meal_record' => route('filament.admin.resources.tenant.meal-records.edit', ['record' => $record->id]),
            default => '#',
        };
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityRecords::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Disable direct creation since we use specific resource links
    }

    public static function canEdit($record): bool
    {
        return false; // Disable direct editing since we redirect to specific resources
    }

    public static function canDelete($record): bool
    {
        return false; // Disable deletion from unified view
    }

    public static function getModelLabel(): string
    {
        return 'activity-record';
    }
}
