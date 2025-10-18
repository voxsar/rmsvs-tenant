@props(['class' => ''])

@php
    $panel = filament()->getCurrentPanel();
@endphp

<a
    {{ $attributes->class(['flex items-center gap-3', $class]) }}
    href="{{ $panel?->getUrl() ?? url('/') }}"
>
    <img
        src="{{ asset('images/epop-logo.svg') }}"
        alt="ePop"
        class="h-9"
    >
    <span class="text-lg font-semibold tracking-wide text-gray-900 dark:text-gray-100">ePop</span>
</a>
