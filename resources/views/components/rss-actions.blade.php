@props([
    'url',
    'label' => 'RSS de esta vista',
])

<div x-data="{
        url: @js($url),
        copied: false,
        failed: false,
        feedback: '',
        lastMessage: '',
        timer: null,
        async copyUrl() {
            clearTimeout(this.timer);
            this.copied = false;
            this.failed = false;

            let ok = false;
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(this.url);
                    ok = true;
                }
            } catch {
                ok = false;
            }

            let message = ok ? 'URL RSS copiada' : 'No se pudo copiar';
            if (message === this.lastMessage) {
                message += '\u00A0';
            }
            this.lastMessage = message;
            this.copied = ok;
            this.failed = ! ok;
            this.feedback = message;

            this.timer = setTimeout(() => {
                this.copied = false;
                this.failed = false;
                this.feedback = '';
            }, 2200);
        }
    }"
    class="inline-flex flex-wrap items-center gap-2">
    <a href="{{ $url }}"
       target="_blank"
       rel="noopener noreferrer"
       class="inline-flex items-center gap-2 min-h-[30px] px-3 py-1.5 rounded-lg text-xs font-mono font-medium border border-beacon/40 bg-beacon/10 text-beacon-bright hover:bg-beacon/20 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-beacon">
        <span>{{ $label }}</span>
    </a>

    <button type="button"
            x-on:click="copyUrl()"
            class="inline-flex items-center gap-1.5 min-h-[30px] px-2.5 py-1.5 rounded-lg text-xs font-mono border border-border bg-bg text-text-secondary hover:text-text hover:border-primary/40 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
            :class="{ 'border-live/50 text-live': copied, 'border-priority-critical/50 text-priority-critical': failed }">
        <svg x-show="!copied" aria-hidden="true" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
        </svg>
        <svg x-show="copied" x-cloak aria-hidden="true" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        <span>Copiar enlace RSS</span>
    </button>

    <span role="status"
          aria-live="polite"
          class="font-mono text-[11px] transition-opacity duration-150"
          :class="feedback ? (failed ? 'opacity-100 text-priority-critical' : 'opacity-100 text-live') : 'opacity-0 pointer-events-none'"
          x-text="feedback"></span>
</div>
