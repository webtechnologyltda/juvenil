<aside
    class="js-cookie-consent cookie-consent fixed inset-x-3 bottom-3 z-40 mx-auto max-w-5xl border border-[#9ddbef]/25 bg-[#03181c]/95 p-4 text-white shadow-[0_24px_80px_rgba(0,0,0,0.38)] backdrop-blur-xl sm:inset-x-auto sm:right-6 sm:bottom-6 sm:w-[min(30rem,calc(100vw-3rem))] sm:max-w-none sm:p-5"
    role="dialog"
    aria-live="polite"
    aria-label="Aviso de cookies"
>
    <div class="grid gap-4 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-start">
        <img
            src="{{ asset('img/logo.png') }}"
            alt=""
            width="56"
            height="56"
            class="hidden size-14 object-contain drop-shadow-[0_10px_22px_rgba(0,0,0,0.35)] sm:block"
        >

        <div class="min-w-0">
            <p class="text-xs font-black uppercase tracking-[0.22em] text-[#f46b12]">Cookies do Juvenil</p>
            <p class="cookie-consent__message mt-2 text-sm leading-6 text-[#d8f2fa]">
                {{ trans('cookie-consent::texts.message') }}
                <a href="{{ route('politica-privacidade') }}" class="font-bold text-white underline decoration-[#f46b12] decoration-2 underline-offset-4 transition-colors hover:text-[#f46b12]">Política de Privacidade</a>
                e
                <a href="{{ route('termos-inscricao') }}" class="font-bold text-white underline decoration-[#f46b12] decoration-2 underline-offset-4 transition-colors hover:text-[#f46b12]">Termos de Inscrição</a>.
            </p>
        </div>

        <button
            class="js-cookie-consent-agree cookie-consent__agree inline-flex min-h-12 w-full items-center justify-center bg-[#f46b12] px-5 text-sm font-black uppercase tracking-[0.14em] text-[#052f35] transition-colors duration-300 hover:bg-[#ff8a2a] sm:col-span-2"
            type="button"
        >
            {{ trans('cookie-consent::texts.agree') }}
        </button>
    </div>
</aside>
