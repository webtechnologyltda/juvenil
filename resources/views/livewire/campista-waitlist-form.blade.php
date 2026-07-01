<div class="mt-4">
    @if($generalPosition && $sexPosition)
        <div class="mb-4 border border-[#9ddbef]/25 bg-[#052f35] p-4 text-left text-[#d8f2fa]">
            <p class="text-sm font-black uppercase tracking-[0.14em] text-[#f46b12]">Fila registrada</p>
            <p class="mt-2 text-sm font-semibold text-[#d8f2fa]">
                Você está na posição geral {{ $generalPosition }} e na posição {{ $sexPosition }} da fila por sexo.
            </p>
        </div>
    @endif

    <div class="flex justify-center">
        {{ $this->joinWaitlistAction }}
    </div>

    <x-filament-actions::modals />
</div>
