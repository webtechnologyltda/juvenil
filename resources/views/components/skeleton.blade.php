@props([
    'variant' => 'section',
    'height' => null,
    'rows' => 5,
    'cards' => 4,
])

@php
    $isFrameless = in_array($variant, ['topbar-switcher', 'icon-grid'], true);
@endphp

<div
    {{
        $attributes
            ->class([
                'overflow-hidden',
                'rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900' => ! $isFrameless,
            ])
            ->style([
                'min-height: '.$height => filled($height),
            ])
    }}
    role="status"
    aria-label="Carregando conteúdo"
>
    <div class="animate-pulse">
        @switch($variant)
            @case('stats')
                <div class="p-4">
                    <div class="mb-4 h-4 w-40 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        @for ($i = 0; $i < $cards; $i++)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="mb-5 flex items-center justify-between gap-3">
                                    <div class="h-3 w-24 rounded bg-gray-200 dark:bg-white/10"></div>
                                    <div class="h-8 w-8 rounded-lg bg-gray-200 dark:bg-white/10"></div>
                                </div>
                                <div class="mb-4 h-8 w-32 rounded bg-gray-300 dark:bg-white/15"></div>
                                <div class="h-3 w-44 rounded bg-gray-200 dark:bg-white/10"></div>
                            </div>
                        @endfor
                    </div>
                </div>
                @break

            @case('chart')
                <div class="p-4">
                    <div class="mb-1 h-4 w-44 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="mb-6 h-3 w-64 max-w-full rounded bg-gray-100 dark:bg-white/5"></div>
                    <div class="relative h-72 rounded-lg border border-gray-100 bg-gray-50 p-4 dark:border-white/5 dark:bg-white/5">
                        <div class="absolute inset-x-4 top-8 h-px bg-gray-200 dark:bg-white/10"></div>
                        <div class="absolute inset-x-4 top-20 h-px bg-gray-200 dark:bg-white/10"></div>
                        <div class="absolute inset-x-4 top-32 h-px bg-gray-200 dark:bg-white/10"></div>
                        <div class="absolute inset-x-4 top-44 h-px bg-gray-200 dark:bg-white/10"></div>
                        <div class="absolute inset-x-4 top-56 h-px bg-gray-200 dark:bg-white/10"></div>
                        <div class="flex h-full items-end gap-3 pt-8">
                            @foreach ([42, 64, 35, 78, 52, 88, 46, 70] as $bar)
                                <div class="flex flex-1 items-end gap-1">
                                    <div class="w-full rounded-t bg-primary-200 dark:bg-primary-500/30" style="height: {{ $bar }}%;"></div>
                                    <div class="w-full rounded-t bg-gray-300 dark:bg-white/15" style="height: {{ max(18, 100 - $bar) }}%;"></div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @break

            @case('table')
                <div class="p-4">
                    <div class="mb-1 h-4 w-44 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="mb-5 h-3 w-56 rounded bg-gray-100 dark:bg-white/5"></div>
                    <div class="space-y-2">
                        <div class="grid grid-cols-5 gap-3 rounded-lg bg-gray-50 px-3 py-3 dark:bg-white/5">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                            @endfor
                        </div>
                        @for ($i = 0; $i < $rows; $i++)
                            <div class="grid grid-cols-5 gap-3 border-b border-gray-100 px-3 py-3 last:border-b-0 dark:border-white/5">
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="col-span-2 h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                            </div>
                        @endfor
                    </div>
                </div>
                @break

            @case('topbar-switcher')
                <div class="flex items-center gap-2">
                    <div class="h-8 w-8 rounded-lg bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-4 w-32 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-5 w-16 rounded-full bg-gray-100 dark:bg-white/5"></div>
                </div>
                @break

            @case('icon-grid')
                <div class="p-4">
                    <div class="mb-4 h-10 rounded-lg bg-gray-200 dark:bg-white/10"></div>
                    <div class="mb-3 h-3 w-40 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8">
                        @for ($i = 0; $i < 32; $i++)
                            <div class="flex h-20 flex-col items-center justify-center gap-2 rounded-lg border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                                <div class="h-7 w-7 rounded-lg bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-2 w-10 rounded bg-gray-200 dark:bg-white/10"></div>
                            </div>
                        @endfor
                    </div>
                </div>
                @break

            @case('report')
                <div class="mx-auto my-6 w-full max-w-3xl rounded-lg bg-white p-8 shadow-sm dark:bg-gray-900">
                    <div class="mb-8 flex items-start justify-between">
                        <div class="h-12 w-32 rounded bg-gray-200 dark:bg-white/10"></div>
                        <div class="space-y-2">
                            <div class="ml-auto h-4 w-40 rounded bg-gray-200 dark:bg-white/10"></div>
                            <div class="ml-auto h-3 w-28 rounded bg-gray-100 dark:bg-white/5"></div>
                        </div>
                    </div>
                    <div class="mb-5 grid grid-cols-4 gap-3">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">
                                <div class="mb-3 h-3 w-20 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-5 w-28 rounded bg-gray-300 dark:bg-white/15"></div>
                            </div>
                        @endfor
                    </div>
                    <div class="mb-5 h-40 rounded-lg border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-white/5"></div>
                    <div class="space-y-2">
                        @for ($i = 0; $i < 6; $i++)
                            <div class="grid grid-cols-5 gap-3 rounded-lg bg-gray-50 px-3 py-3 dark:bg-white/5">
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="col-span-2 h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                                <div class="h-3 rounded bg-gray-200 dark:bg-white/10"></div>
                            </div>
                        @endfor
                    </div>
                </div>
                @break

            @default
                <div class="space-y-4 p-4">
                    <div class="h-4 w-48 rounded bg-gray-200 dark:bg-white/10"></div>
                    <div class="h-3 w-72 max-w-full rounded bg-gray-100 dark:bg-white/5"></div>
                    <div class="space-y-2">
                        @for ($i = 0; $i < $rows; $i++)
                            <div class="h-10 rounded-lg bg-gray-100 dark:bg-white/5"></div>
                        @endfor
                    </div>
                </div>
        @endswitch
    </div>
</div>
