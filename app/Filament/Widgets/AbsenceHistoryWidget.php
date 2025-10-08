<?php

namespace App\Filament\Widgets;

use App\Models\AbsenceRecord;
use App\Models\Guest;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AbsenceHistoryWidget extends BaseWidget
{
    protected static ?string $heading = 'Absence History';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AbsenceRecord::query()->with(['guest', 'guest.assignedRoom'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('guests.first_name')
                    ->label('Resident Name')
                    ->formatStateUsing(fn ($record) => "{$record->guest->first_name} {$record->guest->last_name}")
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('guest.id')
                    ->label('Room Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Absence Start')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Absence End')
                    ->dateTime('M d, Y H:i')
                    ->placeholder('Still Absent')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->getStateUsing(function (AbsenceRecord $record) {
                        if ($record->duration_hours) {
                            return $record->duration_hours > 24 
                                ? floor($record->duration_hours / 24) . ' days, ' . ($record->duration_hours % 24) . ' hours' 
                                : $record->duration_hours . ' hours';
                        }

                        if (!$record->start_date) {
                            return null;
                        }

                        $endDate = $record->end_date ?? Carbon::now();
                        $hours = $record->start_date->diffInHours($endDate);
                        
                        return $hours > 24 
                            ? floor($hours / 24) . ' days, ' . ($hours % 24) . ' hours' 
                            : $hours . ' hours';
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('duration_hours', $direction);
                    }),
                Tables\Columns\IconColumn::make('is_authorized')
                    ->label('Authorized')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('is_authorized')
                    ->label('Authorization Status')
                    ->options([
                        '1' => 'Authorized',
                        '0' => 'Unauthorized',
                    ]),
                Tables\Filters\Filter::make('duration')
                    ->form([
                        Forms\Components\Select::make('duration_filter')
                            ->label('Absence Duration')
                            ->options([
                                '24' => '24+ hours',
                                '48' => '48+ hours',
                                '72' => '72+ hours',
                                '168' => '1+ week',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['duration_filter'], function (Builder $query, $hours) {
                            $hours = (int) $hours;
                            return $query->where(function ($query) use ($hours) {
                                // For completed absences with duration
                                $query->where('duration_hours', '>=', $hours)
                                    ->orWhere(function ($query) use ($hours) {
                                        // For active absences without end_date
                                        $query->whereNull('end_date')
                                            ->where('start_date', '<=', Carbon::now()->subHours($hours));
                                    });
                            });
                        });
                    }),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date'),
                        Forms\Components\DatePicker::make('end_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_profile')
                    ->label('View Resident')
                    ->icon('heroicon-o-eye')
                    ->url(fn (AbsenceRecord $record): string => route('filament.admin.resources.tenant.guests.view', ['record' => $record->guest])),
                Tables\Actions\Action::make('mark_completed')
                    ->label('Mark as Completed')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (AbsenceRecord $record): void {
                        if ($record->status === 'active' && $record->end_date === null) {
                            $record->completeAbsence();
                        }
                    })
                    ->visible(fn (AbsenceRecord $record): bool => $record->status === 'active' && $record->end_date === null),
            ])
            ->defaultSort('start_date', 'desc');
    }
}
