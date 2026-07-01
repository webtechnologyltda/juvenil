@php
    use Illuminate\Contracts\Support\Htmlable;

    $fieldWrapperView = $getFieldWrapperView();
    $extraAttributes = $getExtraAttributes();
    $id = $getId();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $hasBadges = $hasBadges();
    $badgeColor = $getBadgeColor();
@endphp

@php
    $renderOptionLabel = static function (mixed $optionLabel): void {
        if ($optionLabel instanceof Htmlable) {
            echo $optionLabel->toHtml();

            return;
        }

        echo e($optionLabel);
    };
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <div
        {{
            $attributes
                ->merge([
                    'id' => $id,
                ], escape: false)
                ->merge($extraAttributes, escape: false)
                ->class([
                    'fi-fo-modal-table-select',
                    'fi-fo-modal-table-select-disabled' => $isDisabled,
                    'fi-fo-modal-table-select-multiple' => $isMultiple,
                ])
        }}
    >
        @if (((! $isMultiple) && filled($optionLabel = $getOptionLabel())) ||
             ($isMultiple && filled($optionLabels = $getOptionLabels())))
            @if ($isMultiple && $hasBadges)
                <div class="fi-fo-modal-table-select-badges-ctn">
                    @foreach ($optionLabels as $optionLabel)
                        @if ($hasBadges)
                            <x-filament::badge :color="$badgeColor">
                                @php($renderOptionLabel($optionLabel))
                            </x-filament::badge>
                        @else
                            @php($renderOptionLabel($optionLabel))
                        @endif
                    @endforeach
                </div>
            @else
                <div>
                    @if ($hasBadges)
                        <x-filament::badge :color="$badgeColor">
                            @php($renderOptionLabel($optionLabel))
                        </x-filament::badge>
                    @elseif ($isMultiple)
                        @foreach ($optionLabels as $optionLabel)
                            @php($renderOptionLabel($optionLabel))

                            @if (! $loop->last)
                                {{ ', ' }}
                            @endif
                        @endforeach
                    @else
                        @php($renderOptionLabel($optionLabel))
                    @endif
                </div>
            @endif
        @elseif (filled($placeholder = $getPlaceholder()))
            <div class="fi-fo-modal-table-select-placeholder">
                {{ $placeholder }}
            </div>
        @endif

        @if (! $isDisabled)
            <div>
                {{ $getAction('select') }}
            </div>
        @endif
    </div>
</x-dynamic-component>
