<div class="p-4">
    @if($guest->photo)
        <div class="flex flex-col items-center space-y-4">
            <div class="w-full max-w-md">
                <img src="{{ Storage::url($guest->photo) }}" 
                     alt="{{ $guest->first_name }} {{ $guest->last_name }}"
                     class="w-full h-auto rounded-lg shadow-lg">
            </div>
            <div class="text-center">
                <h3 class="text-lg font-semibold">{{ $guest->first_name }} {{ $guest->last_name }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $guest->email }} | {{ $guest->phone }}
                </p>
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <p class="text-gray-500">No photo available</p>
        </div>
    @endif
</div>
