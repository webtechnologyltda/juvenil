@php
    /** @var string|null $currentIcon */
    /** @var string|null $currentColor */
    $currentColor = $currentColor ?? '#94a3b8';
    $colorStatePath = $colorStatePath ?? 'data.cor';
@endphp

<div
    x-data="{
        color: $wire.$entangle(@js($colorStatePath)).live,
        contrastColor() {
            const clean = (this.color || '#94a3b8').replace('#', '');
            const full = clean.length === 3
                ? clean.split('').map((c) => c + c).join('')
                : clean.padEnd(6, '0');
            const r = parseInt(full.substring(0, 2), 16) || 0;
            const g = parseInt(full.substring(2, 4), 16) || 0;
            const b = parseInt(full.substring(4, 6), 16) || 0;
            const luminance = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
            return luminance < 0.55 ? '#ffffff' : '#0f172a';
        },
    }"
    class="fi-category-icon-preview"
>
    <div class="fi-category-icon-preview-wrap">
        <span class="fi-category-icon-preview-label">Pré-visualização</span>
        <div
            class="fi-category-icon-preview-tile"
            :style="`background-color: ${color || '#94a3b8'};`"
        >
            @if (filled($currentIcon))
                <span
                    :style="`color: ${contrastColor()};`"
                    class="fi-category-icon-preview-glyph"
                >
                    @svg($currentIcon, '', ['style' => 'width: 1.5rem; height: 1.5rem;'])
                </span>
            @else
                <span
                    :style="`color: ${contrastColor()};`"
                    class="fi-category-icon-preview-empty"
                >
                    ?
                </span>
            @endif
        </div>
        <span class="fi-category-icon-preview-meta">{{ $currentIcon ?: 'Sem ícone' }}</span>
    </div>
</div>

@once
    <style>
        [x-cloak] { display: none !important; }

        .fi-category-icon-preview {
            display: flex;
            justify-content: center;
            padding: 0.5rem 0 1rem;
        }
        .fi-category-icon-preview-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        .fi-category-icon-preview-label {
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--ac-text-3, #94a3b8);
        }
        .fi-category-icon-preview-tile {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 150ms ease;
            box-shadow: 0 1px 2px rgb(0 0 0 / 0.08);
        }
        .fi-category-icon-preview-glyph {
            display: inline-flex;
            transition: color 150ms ease;
        }
        .fi-category-icon-preview-empty {
            font-size: 1.25rem;
            font-weight: 700;
            opacity: 0.6;
        }
        .fi-category-icon-preview-meta {
            font-family: var(--font-mono, monospace);
            font-size: 0.75rem;
            color: var(--ac-text-3, #94a3b8);
        }
    </style>
@endonce
