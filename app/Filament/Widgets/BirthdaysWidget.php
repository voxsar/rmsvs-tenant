<?php

namespace App\Filament\Widgets;

use App\Models\Guest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BirthdaysWidget extends BaseWidget
{
    protected static ?string $heading = 'Upcoming Birthdays';
    protected static ?int $sort = 3;
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                // Find guests with birthdays in the next 30 days
                Guest::query()
                    ->whereRaw('
                        (
                            MONTH(date_of_birth) = MONTH(CURRENT_DATE) AND 
                            DAY(date_of_birth) >= DAY(CURRENT_DATE)
                        ) OR (
                            MONTH(date_of_birth) = MONTH(DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH)) AND
                            DAY(date_of_birth) <= DAY(DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY))
                        )
                    ')
                    ->orderByRaw('
                        CASE
                            WHEN MONTH(date_of_birth) = MONTH(CURRENT_DATE) AND DAY(date_of_birth) >= DAY(CURRENT_DATE)
                            THEN CONCAT(YEAR(CURRENT_DATE), MONTH(date_of_birth), DAY(date_of_birth))
                            ELSE CONCAT(YEAR(DATE_ADD(CURRENT_DATE, INTERVAL 1 MONTH)), MONTH(date_of_birth), DAY(date_of_birth))
                        END
                    ')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Resident Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label('Birth Date')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('upcoming_birthday')
                    ->label('Upcoming Birthday')
                    ->getStateUsing(function (Guest $record) {
                        if (!$record->date_of_birth) {
                            return null;
                        }
                        
                        $birthdate = Carbon::parse($record->date_of_birth);
                        $today = Carbon::today();
                        
                        $birthdayThisYear = Carbon::createFromDate(
                            $today->year,
                            $birthdate->month,
                            $birthdate->day
                        );
                        
                        if ($birthdayThisYear->isPast()) {
                            $birthdayThisYear->addYear();
                        }
                        
                        return $birthdayThisYear->format('M d, Y');
                    }),
                Tables\Columns\TextColumn::make('days_until')
                    ->label('Days Until')
                    ->getStateUsing(function (Guest $record) {
                        if (!$record->date_of_birth) {
                            return null;
                        }
                        
                        $birthdate = Carbon::parse($record->date_of_birth);
                        $today = Carbon::today();
                        
                        $birthdayThisYear = Carbon::createFromDate(
                            $today->year,
                            $birthdate->month,
                            $birthdate->day
                        );
                        
                        if ($birthdayThisYear->isPast()) {
                            $birthdayThisYear->addYear();
                        }
                        
                        $daysUntil = $today->diffInDays($birthdayThisYear, false);
                        return $daysUntil === 0 ? 'Today!' : $daysUntil . ' days';
                    }),
                Tables\Columns\TextColumn::make('age')
                    ->label('Turning Age')
                    ->getStateUsing(function (Guest $record) {
                        if (!$record->date_of_birth) {
                            return null;
                        }
                        
                        $birthdate = Carbon::parse($record->date_of_birth);
                        $today = Carbon::today();
                        
                        $birthdayThisYear = Carbon::createFromDate(
                            $today->year,
                            $birthdate->month,
                            $birthdate->day
                        );
                        
                        if ($birthdayThisYear->isPast()) {
                            return $today->diffInYears($birthdate) + 1;
                        }
                        
                        return $today->diffInYears($birthdate) + 1;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_profile')
                    ->label('View Profile')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Guest $record): string => route('filament.admin.resources.guests.view', ['record' => $record])),
            ]);
    }
}