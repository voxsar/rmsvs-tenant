<x-filament::page>
    <form wire:submit="create">
        {{ $this->form }}
        
        <div class="flex items-center justify-end mt-6 gap-3">
            <x-filament::button type="submit">
                Check In Guests
            </x-filament::button>
        </div>
    </form>
</x-filament::page>