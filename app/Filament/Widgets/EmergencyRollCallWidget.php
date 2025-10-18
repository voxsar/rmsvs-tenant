<?php

namespace App\Filament\Widgets;

use App\Models\Guest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class EmergencyRollCallWidget extends BaseWidget
{
    protected static ?string $heading = 'Emergency Roll Call';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Guest::query()
                    ->with([
                        'assignedRoom',
                        'checkIns' => fn ($query) => $query->latest('date_of_arrival')->limit(1)->with('room'),
                        'absenceRecords' => fn ($query) => $query
                            ->where('status', 'active')
                            ->orderByDesc('start_date'),
                    ])
                    ->where('type', 'RESIDENT')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Resident')
                    ->formatStateUsing(fn (Guest $record) => $record->first_name.' '.$record->last_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('last_seen')
                    ->label('Last Seen')
                    ->getStateUsing(function (Guest $record): ?string {
                        $latestCheckIn = $record->checkIns->first();

                        if (! $latestCheckIn) {
                            return null;
                        }

                        $timestamp = $latestCheckIn->date_of_departure ?? $latestCheckIn->date_of_arrival;

                        return $timestamp ? Carbon::parse($timestamp)->diffForHumans() : null;
                    })
                    ->placeholder('No activity recorded')
                    ->sortable(),
                Tables\Columns\TextColumn::make('roll_call_status')
                    ->label('Status')
                    ->badge()
                    ->color(function (Guest $record): string {
                        [$status] = $this->resolveStatus($record);

                        return match ($status) {
                            'On Site' => 'success',
                            'Authorized Absence' => 'info',
                            'Requires Follow-up' => 'danger',
                            default => 'warning',
                        };
                    })
                    ->formatStateUsing(function (Guest $record): string {
                        [$status, $note] = $this->resolveStatus($record);

                        return $note ? $status.' · '.$note : $status;
                    }),
                Tables\Columns\IconColumn::make('attention')
                    ->label('Action Needed')
                    ->getStateUsing(function (Guest $record): bool {
                        [$status] = $this->resolveStatus($record);

                        return $status === 'Requires Follow-up';
                    })
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->tooltip('Investigate immediately'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('attention')
                    ->label('Attention')
                    ->options([
                        'requires' => 'Requires Follow-up',
                        'authorized' => 'Authorized Absence',
                        'onsite' => 'On Site',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['value'] ?? null, function (Builder $query, string $value) {
                            return $query->whereIn('id', $this->idsMatchingStatus($value));
                        });
                    }),
            ])
            ->defaultSort('first_name')
            ->paginated([10, 25, 50]);
    }

    /**
     * Determine a roll call status for the record.
     *
     * @return array{string, string|null}
     */
    protected function resolveStatus(Guest $guest): array
    {
        $latestCheckIn = $guest->checkIns->first();
        $activeAbsence = $guest->absenceRecords->first();

        if ($latestCheckIn && ($latestCheckIn->date_of_departure === null || Carbon::parse($latestCheckIn->date_of_departure)->isFuture())) {
            return ['On Site', null];
        }

        if ($activeAbsence) {
            if ($activeAbsence->is_authorized) {
                return ['Authorized Absence', $activeAbsence->start_date?->diffForHumans()];
            }

            return ['Requires Follow-up', $activeAbsence->start_date?->diffForHumans()];
        }

        return ['Checked Out', $latestCheckIn?->date_of_departure?->diffForHumans()];
    }

    /**
     * Resolve guest ids that match a filter value.
     */
    protected function idsMatchingStatus(string $statusKey): array
    {
        $guests = Guest::query()
            ->with([
                'checkIns' => fn ($query) => $query->latest('date_of_arrival')->limit(1),
                'absenceRecords' => fn ($query) => $query->where('status', 'active')->orderByDesc('start_date'),
            ])
            ->where('type', 'RESIDENT')
            ->get();

        return $guests->filter(function (Guest $guest) use ($statusKey) {
            [$status] = $this->resolveStatus($guest);

            return match ($statusKey) {
                'requires' => $status === 'Requires Follow-up',
                'authorized' => $status === 'Authorized Absence',
                'onsite' => $status === 'On Site',
                default => false,
            };
        })->pluck('id')->all();
    }
}
