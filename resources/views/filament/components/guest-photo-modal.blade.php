<div class="space-y-4">
    @if($guest->photo)
        <div class="flex justify-center">
            <img 
                src="{{ \Illuminate\Support\Facades\Storage::url($guest->photo) }}" 
                alt="{{ $guest->first_name }} {{ $guest->last_name }}"
                class="rounded-lg shadow-lg max-w-md w-full h-auto"
            />
        </div>
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">Name:</span>
                <span class="text-gray-900 dark:text-gray-100">{{ $guest->first_name }} {{ $guest->last_name }}</span>
            </div>
            @if($guest->phone)
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Phone:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $guest->phone }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="font-medium text-gray-700 dark:text-gray-300">Email:</span>
                <span class="text-gray-900 dark:text-gray-100">{{ $guest->email }}</span>
            </div>
            @if($guest->assignedRoom)
                <div class="flex justify-between">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Room:</span>
                    <span class="text-gray-900 dark:text-gray-100">{{ $guest->assignedRoom->room_no }}</span>
                </div>
            @endif
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-gray-400 dark:text-gray-600 mb-2">
                <svg class="w-24 h-24 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400">No photo available for this resident</p>
        </div>
    @endif
</div>
