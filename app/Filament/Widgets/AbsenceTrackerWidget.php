<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRecord;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class AbsenceTrackerWidget extends BaseWidget
{
    protected static ?string $heading = 'Absences Longer Than 24 Hours';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AbsenceRecord::longerThan(24)
                    ->with(['guest', 'guest.assignedRoom', 'checkIn.room'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Resident')
                    ->formatStateUsing(fn (AbsenceRecord $record): string => $record->guest?->first_name.' '.$record->guest?->last_name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('room')
                    ->label('Room')
                    ->getStateUsing(function (AbsenceRecord $record): string {
                        return $record->guest?->assignedRoom?->room_no
                            ?? $record->checkIn?->room?->room_no
                            ?? 'Unassigned';
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Started')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function (AbsenceRecord $record): string {
                        $endDate = $record->end_date ?? Carbon::now();
                        $hours = $record->start_date?->diffInHours($endDate) ?? 0;

                        if ($hours >= 24) {
                            $days = intdiv($hours, 24);
                            $remainingHours = $hours % 24;

                            return $remainingHours > 0
                                ? sprintf('%d d %d h', $days, $remainingHours)
                                : sprintf('%d d', $days);
                        }

                        return $hours.' h';
                    })
                    ->badge()
                    ->color(fn (AbsenceRecord $record): string => $record->is_authorized ? 'info' : 'danger'),
                Tables\Columns\IconColumn::make('is_authorized')
                    ->label('Authorized')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_guest')
                    ->label('View Resident')
                    ->url(fn (AbsenceRecord $record): string => route('filament.admin.resources.tenant.guests.view', ['record' => $record->guest]))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (AbsenceRecord $record): bool => filled($record->guest)),
            ])
            ->emptyStateHeading('No prolonged absences found')
            ->defaultSort('start_date', 'desc')
            ->paginated([10, 25]);
    }
}
