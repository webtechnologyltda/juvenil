<div class="fi-icon-picker-modal-body">
    <div class="fi-icon-picker-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="fi-icon-picker-search-icon"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ __('icon_picker.search_placeholder') }}"
            class="fi-icon-picker-search-input"
            autofocus
        >
    </div>

    <div class="fi-icon-picker-subtitle">
        {{ number_format($total, 0, ',', '.') }} {{ __('icon_picker.icons_available') }}
    </div>

    <div class="fi-icon-picker-grid-wrap" wire:loading.class="fi-icon-picker-loading">
        <div wire:loading wire:target="search, loadMore">
            <x-skeleton variant="icon-grid" />
        </div>

        <div wire:loading.remove wire:target="search, loadMore">
            @if ($total === 0)
                <div class="fi-icon-picker-empty">
                    {{ __('icon_picker.no_results') }}
                </div>
            @else
                <div class="fi-icon-picker-grid">
                    @foreach ($icons as $i)
                        <button
                            type="button"
                            wire:click="select(@js($i['icon']))"
                            class="fi-icon-picker-item"
                            title="{{ $i['icon'] }}"
                        >
                            @svg($i['icon'], 'fi-icon-picker-item-glyph', ['style' => 'width: 1.25rem; height: 1.25rem;'])
                            <span class="fi-icon-picker-item-label">{{ $i['icon'] }}</span>
                        </button>
                    @endforeach
                </div>

                @if ($hasMore)
                    <div class="fi-icon-picker-load-more-wrap">
                        <button
                            type="button"
                            wire:click="loadMore"
                            wire:loading.attr="disabled"
                            class="fi-icon-picker-load-more"
                        >
                            <span wire:loading.remove wire:target="loadMore">{{ __('icon_picker.load_more') }}</span>
                            <span wire:loading wire:target="loadMore">{{ __('icon_picker.loading') }}</span>
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
