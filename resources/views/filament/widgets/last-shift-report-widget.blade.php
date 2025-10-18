<x-filament::widget>
    <x-filament::section>
        <x-slot name="heading">
            Last Shift Report
        </x-slot>

        <x-slot name="description">
            Review and update the narrative for recent shifts.
        </x-slot>

        <x-slot name="headerEnd">
            @if ($reportIsMissing)
                <x-filament::badge color="danger">
                    Awaiting update
                </x-filament::badge>
            @else
                <x-filament::badge color="success">
                    Report available
                </x-filament::badge>
            @endif
        </x-slot>

        {{ $this->form }}

        @unless ($isEditable)
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                Only today's and yesterday's reports can be edited.
            </p>
        @endunless
    </x-filament::section>
</x-filament::widget>
