<?php

namespace App\Filament\Widgets;

use App\Models\CheckIn;
use App\Models\Guest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class AbsenceTrackerWidget extends BaseWidget
{
    protected static ?string $heading = 'Absence Tracker';
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Query for guests who are checked out for more than 24 hours
                Guest::query()
                    ->whereHas('checkIns', function (Builder $query) {
                        $query->whereNotNull('date_of_arrival')
                            ->whereDate('date_of_arrival', '<=', Carbon::now()->subHours(24))
                            ->whereNotExists(function ($query) {
                                $query->select('id') // Changed from select(1) to select('id')
                                    ->from('check_ins as ci2')
                                    ->whereColumn('ci2.guest_id', 'check_ins.guest_id')
                                    ->whereNull('ci2.date_of_arrival');
                            });
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Resident Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('room.number')
                    ->label('Room Number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_arrival')
                    ->label('Last Check-Out')
                    ->getStateUsing(function (Guest $record) {
                        return $record->checkIns()
                            ->whereNotNull('date_of_arrival')
                            ->latest('date_of_arrival')
                            ->first()?->date_of_arrival;
                    })
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('absence_duration')
                    ->label('Absence Duration')
                    ->getStateUsing(function (Guest $record) {
                        $lastCheckout = $record->checkIns()
                            ->whereNotNull('date_of_arrival')
                            ->latest('date_of_arrival')
                            ->first()?->date_of_arrival;
                        
                        if (!$lastCheckout) {
                            return null;
                        }
                        
                        $duration = Carbon::parse($lastCheckout)->diffForHumans(null, true);
                        return $duration;
                    }),
                Tables\Columns\IconColumn::make('authorized_absence')
                    ->label('Authorized')
                    ->boolean()
                    ->getStateUsing(function (Guest $record) {
                        return (bool) $record->authorized_absence;
                    })
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->actions([
                Tables\Actions\Action::make('view_profile')
                    ->label('View Profile')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Guest $record): string => route('filament.admin.resources.guests.view', ['record' => $record])),
            ]);
    }
}