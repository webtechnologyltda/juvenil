@php
    /** @var \App\Filament\Tables\Columns\ColoredIconColumn $column */
    /** @var \Illuminate\Database\Eloquent\Model $record */
    $record = $getRecord();

    $background = $column->getBackgroundColor($record);
    $iconColor = $column->getIconColor($record);
    $iconName = $column->getIconName($record);
@endphp

<div
    class="flex h-14 w-14 items-center justify-center rounded-xl"
    style="background-color: {{ $background }};"
    title="{{ data_get($record, 'nome') ?? $iconName }}"
>
    <x-dynamic-component
        :component="$iconName"
        class="h-6 w-6"
        :style="'color: '.$iconColor"
    />
</div>
