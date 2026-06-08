@php
    /** @var \App\Filament\Tables\Columns\ColoredIconColumn $column */
    /** @var \Illuminate\Database\Eloquent\Model $record */
    $record = $getRecord();

    $background = $column->getBackgroundColor($record);
    $iconColor = $column->getIconColor($record);
    $iconName = $column->getIconName($record);
@endphp

<div
    class="flex h-8 w-8 items-center justify-center rounded-lg"
    style="background-color: {{ $background }};"
    title="{{ data_get($record, 'nome') ?? $iconName }}"
>
    <x-dynamic-component
        :component="$iconName"
        class="h-[1.1rem] w-[1.1rem]"
        :style="'color: '.$iconColor"
    />
</div>
