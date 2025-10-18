<?php

namespace App\Services;

use App\Models\ScanItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ScanItemNotificationService
{
    /**
     * Retrieve scan items that should trigger missed scan notifications.
     */
    public function getActiveNotificationPayloads(?Carbon $date = null): Collection
    {
        $date ??= Carbon::now();

        return ScanItem::query()
            ->requiringNotification()
            ->get()
            ->map(fn (ScanItem $item) => $item->toNotificationPayload($date))
            ->filter(fn (array $payload) => ! empty($payload['active_windows']));
    }
}
