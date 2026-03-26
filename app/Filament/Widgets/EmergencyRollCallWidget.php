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
    protected static ?string $heading = 'Emergency Roll Call List';

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
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
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
            ->actions([
                Tables\Actions\Action::make('view_photo')
                    ->label('View Photo')
                    ->icon('heroicon-o-photo')
                    ->modalHeading('Guest Photo')
                    ->modalContent(fn (Guest $record) => view('filament.widgets.guest-photo-modal', ['guest' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->visible(fn (Guest $record): bool => filled($record->photo)),
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
