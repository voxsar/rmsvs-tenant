<?php

namespace App\Filament\Pages\Tenant;

use App\Models\Guest;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class EmergencyRollCall extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static string $view = 'filament.pages.tenant.emergency-roll-call';

    protected static ?string $navigationGroup = 'Property';

    protected static ?string $navigationLabel = 'Emergency Roll Call';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::guard('tenant')->check() &&
               Auth::guard('tenant')->user()->can('view guest');
    }

    public function getTitle(): string
    {
        return 'Emergency Roll Call List';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Guest::query()
                    ->with([
                        'assignedRoom',
                        'checkIns' => fn ($query) => $query->latest('created_at')->limit(1)->with('room'),
                        'absenceRecords' => fn ($query) => $query
                            ->where('status', 'active')
                            ->orderByDesc('start_date'),
                    ])
                    ->where('type', 'RESIDENT')
                    ->where('is_active', 'active')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Guest $record) => $record->first_name.' '.$record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone Number')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->placeholder('No phone number')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->icon('heroicon-m-envelope'),

                Tables\Columns\TextColumn::make('room')
                    ->label('Room')
                    ->getStateUsing(function (Guest $record): string {
                        if ($record->assignedRoom) {
                            return (string) $record->assignedRoom->room_no;
                        }

                        $latestCheckIn = $record->checkIns->first();

                        return $latestCheckIn && $latestCheckIn->room ? (string) $latestCheckIn->room->room_no : 'Unassigned';
                    })
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('last_scan')
                    ->label('Last Scan')
                    ->getStateUsing(function (Guest $record): ?string {
                        $latestCheckIn = $record->checkIns->first();

                        if (! $latestCheckIn) {
                            return null;
                        }

                        // Use created_at as the scan timestamp
                        $timestamp = $latestCheckIn->created_at;

                        return $timestamp ? Carbon::parse($timestamp)->format('M d, Y h:i A') : null;
                    })
                    ->placeholder('No scan recorded')
                    ->sortable()
                    ->tooltip(function (Guest $record): ?string {
                        $latestCheckIn = $record->checkIns->first();
                        if (! $latestCheckIn || ! $latestCheckIn->created_at) {
                            return null;
                        }

                        return Carbon::parse($latestCheckIn->created_at)->diffForHumans();
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(function (Guest $record): string {
                        $status = $this->resolveStatus($record);

                        return match ($status) {
                            'On Site' => 'success',
                            'Authorized Absence' => 'info',
                            'Checked Out' => 'warning',
                            default => 'danger',
                        };
                    })
                    ->formatStateUsing(function (Guest $record): string {
                        return $this->resolveStatus($record);
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'onsite' => 'On Site',
                        'authorized' => 'Authorized Absence',
                        'checkedout' => 'Checked Out',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, function (Builder $query, string $value) {
                            return $query->whereIn('id', $this->idsMatchingStatus($value));
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_photo')
                    ->label('View Photo')
                    ->icon('heroicon-o-photo')
                    ->color('gray')
                    ->modalHeading(fn (Guest $record) => $record->first_name.' '.$record->last_name)
                    ->modalContent(fn (Guest $record) => view('filament.components.guest-photo-modal', ['guest' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (Guest $record) => $record->photo !== null),

                Tables\Actions\Action::make('view_details')
                    ->label('View Profile')
                    ->icon('heroicon-o-user')
                    ->url(fn (Guest $record) => route('filament.admin.resources.tenant.guests.view', ['record' => $record->id]))
                    ->openUrlInNewTab(false)
                    ->visible(fn () => Auth::guard('tenant')->check() && Auth::guard('tenant')->user()->can('view guest')),
            ])
            ->defaultSort('first_name')
            ->paginated([10, 25, 50, 100, 'all'])
            ->poll('30s');
    }

    /**
     * Determine status for a guest
     */
    protected function resolveStatus(Guest $guest): string
    {
        $latestCheckIn = $guest->checkIns->first();
        $activeAbsence = $guest->absenceRecords->first();

        // Check if guest is currently on site (has active check-in)
        if ($latestCheckIn && ($latestCheckIn->date_of_departure === null || Carbon::parse($latestCheckIn->date_of_departure)->isFuture())) {
            return 'On Site';
        }

        // Check for authorized absence
        if ($activeAbsence && $activeAbsence->is_authorized) {
            return 'Authorized Absence';
        }

        return 'Checked Out';
    }

    /**
     * Get guest IDs matching a status filter
     */
    protected function idsMatchingStatus(string $statusKey): array
    {
        $guests = Guest::query()
            ->with([
                'checkIns' => fn ($query) => $query->latest('created_at')->limit(1),
                'absenceRecords' => fn ($query) => $query->where('status', 'active')->orderByDesc('start_date'),
            ])
            ->where('type', 'RESIDENT')
            ->where('is_active', 'active')
            ->get();

        return $guests->filter(function (Guest $guest) use ($statusKey) {
            $status = $this->resolveStatus($guest);

            return match ($statusKey) {
                'onsite' => $status === 'On Site',
                'authorized' => $status === 'Authorized Absence',
                'checkedout' => $status === 'Checked Out',
                default => false,
            };
        })->pluck('id')->all();
    }
}
