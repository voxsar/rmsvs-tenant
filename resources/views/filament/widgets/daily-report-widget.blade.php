<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">
            Daily Report
        </x-slot>

        <x-slot name="headerEnd">
            <x-filament::badge color="success">
                Auto-saves at midnight
            </x-filament::badge>
        </x-slot>

        {{ $this->form }}
    </x-filament::section>
</x-filament::widget>