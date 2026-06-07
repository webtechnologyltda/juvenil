@php
    $statePath = $getStatePath();
    $placeholder = $getPlaceholder() ?? __('filament-forms::components.select.placeholder');
    $catalog = $getCatalog();
    $customIcons = $getCustomIcons();
    $eventName = 'icon-picker-selected-'.\App\Livewire\IconPickerModal::eventToken($statePath);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}').live,
            open: false,
        }"
        x-on:{{ $eventName }}.window="state = $event.detail.icon; open = false"
        wire:ignore.self
        class="fi-icon-picker"
    >
        {{-- Trigger button --}}
        <div
            role="button"
            tabindex="0"
            aria-haspopup="dialog"
            x-bind:aria-expanded="open.toString()"
            x-on:click="open = true"
            x-on:keydown.enter.prevent="open = true"
            x-on:keydown.space.prevent="open = true"
            class="fi-icon-picker-trigger"
        >
            @php
                $currentState = $getState();
            @endphp
            @if (filled($currentState))
                <span class="fi-icon-picker-trigger-icon">
                    @svg($currentState, 'fi-icon-picker-glyph', ['style' => 'width: 1rem; height: 1rem;'])
                </span>
                <span x-text="state" class="fi-icon-picker-trigger-text">{{ $currentState }}</span>
            @else
                <span x-show="state" x-cloak class="fi-icon-picker-trigger-text" x-text="state"></span>
                <span x-show="! state" class="fi-icon-picker-trigger-placeholder">
                    {{ $placeholder }}
                </span>
            @endif
            <span class="fi-icon-picker-trigger-chevron-wrap">
                <button
                    type="button"
                    x-show="state"
                    x-cloak
                    x-on:click.stop="state = null"
                    x-on:keydown.enter.stop
                    x-on:keydown.space.stop
                    class="fi-icon-picker-trigger-clear"
                    aria-label="{{ __('filament-forms::components.select.actions.unselect.label') }}"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="fi-icon-picker-trigger-chevron"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </span>
        </div>

        {{-- Modal teleported to <body> to escape Filament's stacking/transform context --}}
        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-transition.opacity
                x-on:keydown.escape.window="open = false"
                class="fi-icon-picker-overlay"
            >
                <div x-on:click.outside="open = false" class="fi-icon-picker-dialog">
                    <div class="fi-icon-picker-dialog-header">
                        <div>
                            <h2 class="fi-icon-picker-dialog-title">
                                {{ __('icon_picker.title') }}
                            </h2>
                        </div>
                        <button
                            type="button"
                            x-on:click="open = false"
                            class="fi-icon-picker-dialog-close"
                            aria-label="{{ __('icon_picker.close') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>

                    @livewire('icon-picker-modal', [
                        'statePath' => $statePath,
                        'catalog' => $catalog,
                        'customIcons' => $customIcons,
                        'lazy' => true,
                    ], key('icon-picker-'.$statePath))
                </div>
            </div>
        </template>
    </div>
</x-dynamic-component>

@once
    <style global>
            [x-cloak] { display: none !important; }

            .fi-icon-picker-trigger {
                display: inline-flex;
                align-items: center;
                gap: 0.625rem;
                width: 100%;
                min-height: 2.5rem;
                padding: 0.5rem 0.75rem;
                border-radius: 0.5rem;
                background-color: var(--ac-surface, #fff);
                border: 1px solid var(--ac-border-strong, #cbd5e1);
                cursor: pointer;
                text-align: start;
                color: inherit;
            }
            .dark .fi-icon-picker-trigger {
                background-color: rgb(255 255 255 / 0.04);
                border-color: rgb(255 255 255 / 0.1);
                color: #e2e8f0;
            }
            .fi-icon-picker-trigger:hover {
                border-color: var(--color-primary-500, #10b981);
            }
            .fi-icon-picker-trigger-icon {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 1.75rem;
                height: 1.75rem;
                border-radius: 0.375rem;
                background-color: var(--ac-bg-alt, #f1f5f9);
                color: var(--color-primary-700, #047857);
                flex-shrink: 0;
            }
            .dark .fi-icon-picker-trigger-icon {
                background-color: rgb(255 255 255 / 0.08);
                color: #6ee7b7;
            }
            .fi-icon-picker-trigger-text {
                font-family: var(--font-mono, monospace);
                font-size: 0.8125rem;
                color: var(--ac-text, #0f172a);
                flex: 1;
                min-width: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .dark .fi-icon-picker-trigger-text {
                color: #e2e8f0;
            }
            .fi-icon-picker-trigger-placeholder {
                color: var(--ac-text-3, #94a3b8);
                font-size: 0.8125rem;
                flex: 1;
            }
            .fi-icon-picker-trigger-chevron-wrap {
                display: inline-flex;
                gap: 0.375rem;
                align-items: center;
                flex-shrink: 0;
                color: var(--ac-text-3, #94a3b8);
            }
            .fi-icon-picker-trigger-clear {
                background: transparent;
                border: 0;
                color: currentColor;
                padding: 0.125rem;
                cursor: pointer;
                display: inline-flex;
                border-radius: 0.25rem;
            }
            .fi-icon-picker-trigger-clear:hover {
                background-color: var(--ac-bg-alt, #f1f5f9);
                color: var(--ac-text, #0f172a);
            }
            .dark .fi-icon-picker-trigger-clear:hover {
                background-color: rgb(255 255 255 / 0.08);
                color: #f8fafc;
            }

            /* Overlay (teleported) */
            .fi-icon-picker-overlay {
                position: fixed;
                inset: 0;
                background-color: rgb(0 0 0 / 0.55);
                z-index: 100;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 1.5rem;
            }
            .fi-icon-picker-overlay [x-cloak] { display: none !important; }

            .fi-icon-picker-dialog {
                background-color: var(--ac-surface, #fff);
                border: 1px solid var(--ac-border, #e2e8f0);
                border-radius: 0.75rem;
                max-width: 720px;
                width: 100%;
                max-height: 80vh;
                display: flex;
                flex-direction: column;
                box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.4);
                font-family: var(--font-sans, system-ui, sans-serif);
            }
            .dark .fi-icon-picker-dialog {
                background-color: #1e293b;
                border-color: rgb(255 255 255 / 0.08);
            }
            .fi-icon-picker-dialog-header {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                padding: 1.125rem 1.25rem;
                border-bottom: 1px solid var(--ac-border, #e2e8f0);
                gap: 1rem;
            }
            .dark .fi-icon-picker-dialog-header {
                border-bottom-color: rgb(255 255 255 / 0.08);
            }
            .fi-icon-picker-dialog-title {
                font-size: 1rem;
                font-weight: 700;
                color: var(--ac-text, #0f172a);
                margin: 0;
                letter-spacing: -0.01em;
            }
            .dark .fi-icon-picker-dialog-title {
                color: #f8fafc;
            }
            .fi-icon-picker-dialog-close {
                background: transparent;
                border: 0;
                color: var(--ac-text-3, #94a3b8);
                padding: 0.375rem;
                cursor: pointer;
                border-radius: 0.375rem;
                display: inline-flex;
                flex-shrink: 0;
            }
            .fi-icon-picker-dialog-close:hover {
                background-color: var(--ac-bg-alt, #f1f5f9);
                color: var(--ac-text, #0f172a);
            }
            .dark .fi-icon-picker-dialog-close:hover {
                background-color: rgb(255 255 255 / 0.08);
                color: #f8fafc;
            }

            .fi-icon-picker-modal-body {
                display: flex;
                flex-direction: column;
                min-height: 0;
                flex: 1;
            }
            .fi-icon-picker-search {
                padding: 0.875rem 1.25rem;
                border-bottom: 1px solid var(--ac-border, #e2e8f0);
                position: relative;
            }
            .dark .fi-icon-picker-search {
                border-bottom-color: rgb(255 255 255 / 0.08);
            }
            .fi-icon-picker-search-icon {
                position: absolute;
                left: 2.125rem;
                top: 50%;
                transform: translateY(-50%);
                color: var(--ac-text-3, #94a3b8);
                pointer-events: none;
            }
            .fi-icon-picker-search-input {
                width: 100%;
                min-height: 2.5rem;
                padding: 0.5rem 0.75rem 0.5rem 2.5rem;
                border-radius: 0.5rem;
                background-color: var(--ac-bg-alt, #f8fafc);
                border: 1px solid var(--ac-border, #e2e8f0);
                font-size: 0.875rem;
                color: var(--ac-text, #0f172a);
                outline: none;
            }
            .fi-icon-picker-search-input:focus {
                border-color: var(--color-primary-600, #047857);
                box-shadow: 0 0 0 3px var(--color-primary-100, #d1fae5);
            }
            .dark .fi-icon-picker-search-input {
                background-color: rgb(255 255 255 / 0.04);
                border-color: rgb(255 255 255 / 0.1);
                color: #f8fafc;
            }
            .fi-icon-picker-search-input::placeholder {
                color: var(--ac-text-3, #94a3b8);
            }
            .fi-icon-picker-subtitle {
                padding: 0.5rem 1.25rem;
                font-size: 0.75rem;
                color: var(--ac-text-3, #94a3b8);
            }

            .fi-icon-picker-grid-wrap {
                overflow-y: auto;
                padding: 0.5rem 1.25rem 1.25rem;
                flex: 1;
                min-height: 12rem;
                transition: opacity 150ms ease;
            }
            .fi-icon-picker-grid-wrap.fi-icon-picker-loading {
                opacity: 0.6;
            }
            .fi-icon-picker-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
                gap: 0.5rem;
            }
            .fi-icon-picker-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 0.375rem;
                padding: 0.625rem 0.375rem;
                border-radius: 0.5rem;
                border: 1px solid var(--ac-border, #e2e8f0);
                background-color: var(--ac-surface, #fff);
                cursor: pointer;
                transition: all 120ms ease;
                color: var(--ac-text, #0f172a);
            }
            .fi-icon-picker-item:hover {
                border-color: var(--color-primary-500, #10b981);
                background-color: var(--color-primary-50, #ecfdf5);
            }
            .dark .fi-icon-picker-item {
                background-color: rgb(255 255 255 / 0.03);
                border-color: rgb(255 255 255 / 0.08);
                color: #e2e8f0;
            }
            .dark .fi-icon-picker-item:hover {
                border-color: #34d399;
                background-color: rgb(16 185 129 / 0.1);
            }
            .fi-icon-picker-item-glyph {
                color: currentColor;
                flex-shrink: 0;
            }
            .fi-icon-picker-item-label {
                font-size: 0.625rem;
                font-family: var(--font-mono, monospace);
                color: var(--ac-text-3, #94a3b8);
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .fi-icon-picker-empty {
                text-align: center;
                padding: 2.5rem 1rem;
                color: var(--ac-text-3, #94a3b8);
                font-size: 0.875rem;
            }
            .fi-icon-picker-load-more-wrap {
                display: flex;
                justify-content: center;
                padding: 1.25rem 0 0.25rem;
            }
            .fi-icon-picker-load-more {
                padding: 0.5rem 1rem;
                border-radius: 0.5rem;
                background-color: var(--ac-bg-alt, #f1f5f9);
                border: 1px solid var(--ac-border, #e2e8f0);
                color: var(--ac-text, #0f172a);
                font-size: 0.8125rem;
                cursor: pointer;
            }
            .fi-icon-picker-load-more:hover {
                border-color: var(--color-primary-500, #10b981);
            }
            .fi-icon-picker-load-more[disabled] {
                opacity: 0.5;
                cursor: wait;
            }
            .dark .fi-icon-picker-load-more {
                background-color: rgb(255 255 255 / 0.04);
                border-color: rgb(255 255 255 / 0.1);
                color: #f8fafc;
            }
    </style>
@endonce
