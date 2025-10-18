<?php

namespace App\Filament\Widgets;

use App\Models\CustomRequest;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Carbon;

class MissedConsumablesWidget extends BaseWidget
{
    protected static ?string $heading = 'Missed Consumables';

    protected static ?int $sort = 5;

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
                    ->with(['guest', 'room', 'consumable'])
                    ->where('request_type', 'CONSUMABLE')
                    ->where('status', 'PENDING')
                    ->orderByDesc('created_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('guest_name')
                    ->label('Requester')
                    ->formatStateUsing(fn (CustomRequest $record): string => $record->guest?->first_name.' '.$record->guest?->last_name)
                    ->wrap(),
                Tables\Columns\TextColumn::make('consumable.name')
                    ->label('Consumable')
                    ->placeholder('N/A')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->since()
                    ->sortable(),
                Tables\Columns\IconColumn::make('follow_up')
                    ->label('Follow-up')
                    ->getStateUsing(fn (CustomRequest $record): bool => $this->isOverdue($record, 6))
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn (CustomRequest $record): string => $this->isOverdue($record, 6) ? 'Older than 6 hours' : 'Within response window'),
            ])
            ->actions([
                Tables\Actions\Action::make('open_request')
                    ->label('Open Request')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (CustomRequest $record): string => route('filament.admin.resources.tenant.custom-requests.view', ['record' => $record]))
                    ->visible(fn (CustomRequest $record): bool => filled($record->id)),
            ])
            ->emptyStateHeading('No outstanding consumable requests')
            ->defaultPaginationPageOption(5)
            ->paginated([5, 10]);
    }

    protected function isOverdue(CustomRequest $request, int $hours = 12): bool
    {
        $threshold = Carbon::now()->subHours($hours);

        return $request->created_at?->lessThanOrEqualTo($threshold) ?? false;
    }
}
