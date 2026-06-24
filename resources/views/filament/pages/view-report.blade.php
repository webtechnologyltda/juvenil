<x-filament-panels::page>
    @php
        $reportExport = $this->reportExport();
        $isGenerating = $reportExport?->isPendingOrProcessing() ?? true;
        $progressPercent = $reportExport?->progressPercent() ?? 0;
    @endphp

    <div
        x-data="{ isSlow: false }"
        x-init="window.setTimeout(() => { isSlow = true }, 8000)"
        @if ($isGenerating)
            wire:poll.3s="refreshReportExport"
        @endif
        class="relative mx-auto w-full max-w-7xl overflow-hidden rounded-xl border border-gray-200 bg-gray-100 p-3 shadow-sm dark:border-white/10 dark:bg-gray-950"
    >
        @if ($reportExport?->isReady())
            <iframe
                src="{{ $this->fileUrl(inline: true) }}"
                class="block w-full rounded-lg bg-white"
                style="height: min(1120px, calc(100vh - 220px)); min-height: 760px; border: 0;"
                title="Pré-visualização do relatório"
            ></iframe>
        @elseif ($reportExport?->isFailed())
            <div class="flex min-h-[760px] items-center justify-center rounded-lg bg-white p-8 text-center dark:bg-gray-900">
                <div class="max-w-md space-y-4">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl border border-danger-200 bg-danger-50 text-danger-600 shadow-sm dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-300">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-7 w-7" />
                    </div>

                    <div class="space-y-2">
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                            Não foi possível gerar o relatório
                        </h2>

                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $reportExport->error_message ?: 'Tente gerar novamente. Se persistir, reduza os filtros do relatório.' }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="flex min-h-[760px] items-start justify-center overflow-hidden rounded-lg bg-white p-6 dark:bg-gray-900" aria-live="polite">
                <div class="flex w-full max-w-5xl flex-col items-center gap-5 text-center">
                    <div class="flex max-w-sm flex-col items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-xl border border-primary-200 bg-primary-50 text-primary-600 shadow-sm dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-300">
                            <x-filament::loading-indicator class="h-7 w-7" />
                        </div>

                        <div class="space-y-1.5">
                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ $reportExport?->status?->label() ?? 'Na fila' }}
                            </p>

                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $reportExport?->progress_message ?: 'Estamos gerando o arquivo em segundo plano. A página será atualizada automaticamente.' }}
                            </p>

                            <p
                                x-cloak
                                x-show="isSlow"
                                x-transition.opacity
                                class="text-xs text-gray-500 dark:text-gray-500"
                            >
                                Relatórios com muitos campistas podem levar alguns instantes.
                            </p>
                        </div>

                        <div class="flex w-52 gap-1.5" aria-hidden="true">
                            <span class="h-1.5 flex-1 animate-pulse rounded-full bg-primary-500/90"></span>
                            <span class="h-1.5 flex-1 animate-pulse rounded-full bg-primary-400/70 [animation-delay:150ms]"></span>
                            <span class="h-1.5 flex-1 animate-pulse rounded-full bg-primary-300/60 [animation-delay:300ms]"></span>
                        </div>

                        <div class="w-64 max-w-full space-y-1 text-left">
                            <div class="h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                                <div
                                    class="h-full rounded-full bg-primary-500 transition-all duration-500"
                                    style="width: {{ $progressPercent }}%;"
                                ></div>
                            </div>

                            <p class="text-center text-xs text-gray-500 dark:text-gray-500">
                                {{ $progressPercent }}%
                            </p>
                        </div>
                    </div>

                    <div class="w-full max-w-4xl rounded-xl border border-gray-200 bg-gray-50 p-5 dark:border-white/10 dark:bg-white/5">
                        <div class="space-y-3">
                            <div class="h-5 w-2/5 rounded bg-gray-200 dark:bg-white/10"></div>
                            <div class="grid gap-3 md:grid-cols-4">
                                <div class="h-16 rounded bg-white shadow-sm dark:bg-gray-800"></div>
                                <div class="h-16 rounded bg-white shadow-sm dark:bg-gray-800"></div>
                                <div class="h-16 rounded bg-white shadow-sm dark:bg-gray-800"></div>
                                <div class="h-16 rounded bg-white shadow-sm dark:bg-gray-800"></div>
                            </div>
                            <div class="h-80 rounded bg-white shadow-sm dark:bg-gray-800"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div
            wire:loading.flex
            class="absolute inset-3 z-20 hidden min-h-[760px] items-center justify-center rounded-lg bg-white/90 backdrop-blur-sm dark:bg-gray-900/90"
            aria-live="polite"
        >
            <div class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
                <x-filament::loading-indicator class="h-5 w-5 text-primary-600 dark:text-primary-300" />
                Processando solicitação...
            </div>
        </div>
    </div>
</x-filament-panels::page>
