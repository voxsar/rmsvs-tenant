<?php

namespace App\Filament\Widgets;

use App\Models\CustomRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class MissedMealsWidget extends BaseWidget
{
    protected static ?string $heading = 'Missed Meals';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 1,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CustomRequest::query()
                    ->with(['guest', 'room'])
                    ->where('request_type', 'LATE_DINNER')
                    ->where('status', 'PENDING')
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Resident')
                    ->formatStateUsing(fn (CustomRequest $record): string => $record->guest?->first_name.' '.$record->guest?->last_name)
                    ->wrap(),
                Tables\Columns\TextColumn::make('room.room_no')
                    ->label('Room')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
                Tables\Columns\IconColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(fn (CustomRequest $record): bool => $this->isOverdue($record))
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (CustomRequest $record): string => $this->isOverdue($record) ? 'Older than 12 hours' : 'Within response window'),
            ])
            ->actions([
                Tables\Actions\Action::make('open_request')
                    ->label('Open Request')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CustomRequest $record): string => route('filament.admin.resources.tenant.custom-requests.view', ['record' => $record]))
                    ->visible(fn (CustomRequest $record): bool => filled($record->id)),
            ])
            ->emptyStateHeading('No missed meal alerts')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }

    protected function isOverdue(CustomRequest $request): bool
    {
        $threshold = Carbon::now()->subHours(12);

        return $request->created_at?->lessThanOrEqualTo($threshold) ?? false;
    }
}
