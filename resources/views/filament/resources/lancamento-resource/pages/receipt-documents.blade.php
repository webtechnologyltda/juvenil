@php
    $documents = $documents ?? [];
@endphp

@if (count($documents))
    <div class="juvenil-lancamento-receipts" aria-label="Comprovantes anexados">
        @foreach ($documents as $document)
            <article class="juvenil-lancamento-receipt-card juvenil-lancamento-receipt-card--{{ $document['type'] }}">
                <div class="juvenil-lancamento-receipt-card__preview">
                    @if ($document['type'] === 'image')
                        <img
                            src="{{ $document['preview_url'] }}"
                            alt="Prévia do comprovante {{ $document['name'] }}"
                            loading="lazy"
                        >
                    @elseif ($document['type'] === 'pdf')
                        <iframe
                            src="{{ $document['preview_url'] }}"
                            title="Prévia do comprovante {{ $document['name'] }}"
                            loading="lazy"
                        ></iframe>
                    @else
                        <div class="juvenil-lancamento-receipt-card__file">
                            <span>{{ strtoupper(pathinfo($document['name'], PATHINFO_EXTENSION) ?: 'DOC') }}</span>
                        </div>
                    @endif
                </div>

                <div class="juvenil-lancamento-receipt-card__body">
                    <strong>{{ $document['name'] }}</strong>

                    @if (filled($document['observation']))
                        <p>{{ $document['observation'] }}</p>
                    @endif

                    <a href="{{ $document['url'] }}" target="_blank" rel="noopener noreferrer">
                        Abrir documento
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@else
    <p class="juvenil-lancamento-receipts__empty">Sem comprovantes anexados.</p>
@endif
