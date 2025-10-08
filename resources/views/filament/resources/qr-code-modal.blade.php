<div class="p-4 flex flex-col items-center justify-center space-y-4">
    @if($record->qr_code)
        <div class="text-center">
            <h3 class="text-lg font-medium">Room Access QR Code</h3>
            <p class="text-sm text-gray-500">For {{ $record->guest->first_name }} {{ $record->guest->last_name }}, Room {{ $record->room->room_no }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <img src="{{ Storage::url($record->qr_code) }}" alt="Room Access QR Code" class="w-64 h-64 mx-auto">
        </div>
        <div class="text-center text-sm text-gray-500">
            <p>Scan this QR code for room access</p>
            <p>Generated on {{ $record->created_at->format('Y-m-d H:i') }}</p>
        </div>
    @else
        <div class="p-6 text-center">
            <p class="text-gray-500">No QR code has been generated for this check-in yet.</p>
        </div>
    @endif
</div>