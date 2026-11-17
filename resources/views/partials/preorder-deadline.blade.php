{{--
    Live preorder deadline + countdown.

    Expects $preorderStatus in scope (shared by the storefront view composer).
    Renders nothing unless there is a scheduled cutoff (a plain weekly window —
    not an admin "forced open" with no deadline).

    Options:
      $variant — 'banner' (light text on a coloured banner) or 'page' (card).
      $announceExpiry — when true, dispatch a window `preorder-expired` event as
                        the deadline passes, so a surrounding form can close.
--}}
@php
    $deadlineIso = $preorderStatus->closesAtIso();
    $deadlineLabel = $preorderStatus->closesAtLabel();
    $nextOpenLabel = $preorderStatus->nextOpenLabel();
    $variant = $variant ?? 'banner';
    $announceExpiry = $announceExpiry ?? false;
@endphp

@if ($deadlineIso)
    <div
        x-data="preorderCountdown({
            deadline: @js($deadlineIso),
            next: @js($nextOpenLabel),
            announce: {{ $announceExpiry ? 'true' : 'false' }},
            words: {
                before: @js(__('Order before :deadline', ['deadline' => $deadlineLabel])),
                closesIn: @js(__('Closes in')),
                d: @js(__('d')),
                h: @js(__('h')),
                m: @js(__('m')),
                lessThanMinute: @js(__('less than a minute left')),
                closedOpens: @js(__('Preordering just closed.')),
                opens: @js(__('Opens :when', ['when' => $nextOpenLabel ?? ''])),
            },
        })"
        x-init="start()"
        @class([
            'text-sm',
            'text-indigo-50/95' => $variant === 'banner',
            'rounded-xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-indigo-900' => $variant === 'page',
        ])
    >
        {{-- Live window still open --}}
        <div x-show="! expired" x-cloak class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
            <span @class(['font-semibold' => $variant === 'page']) x-text="words.before"></span>
            <span
                @class([
                    'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums',
                    'bg-white/20 text-white' => $variant === 'banner',
                    'bg-indigo-600 text-white' => $variant === 'page',
                ])
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l3 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                <span x-text="countdownText"></span>
            </span>
        </div>

        {{-- Deadline has passed (updated live, without a reload) --}}
        <div x-show="expired" x-cloak @class(['font-semibold' => $variant === 'page'])>
            <span x-text="words.closedOpens"></span>
            <span x-show="next" x-text="words.opens"></span>
        </div>
    </div>
@endif

@once
    <script>
        function preorderCountdown(cfg) {
            return {
                expired: false,
                countdownText: '',
                next: cfg.next,
                words: cfg.words,
                _timer: null,
                start() {
                    this.tick();
                    // Refresh every minute, per the requested cadence.
                    this._timer = setInterval(() => this.tick(), 60000);
                },
                tick() {
                    const end = new Date(cfg.deadline).getTime();
                    let secs = Math.floor((end - Date.now()) / 1000);

                    if (isNaN(end)) { return; }

                    if (secs <= 0) {
                        if (! this.expired) {
                            this.expired = true;
                            if (cfg.announce) {
                                window.dispatchEvent(new CustomEvent('preorder-expired'));
                            }
                        }
                        if (this._timer) { clearInterval(this._timer); }
                        return;
                    }

                    const days = Math.floor(secs / 86400);
                    const hours = Math.floor((secs % 86400) / 3600);
                    const mins = Math.floor((secs % 3600) / 60);

                    const parts = [];
                    if (days > 0) { parts.push(days + this.words.d); }
                    if (hours > 0 || days > 0) { parts.push(hours + this.words.h); }
                    parts.push(mins + this.words.m);

                    // Under a minute: avoid a bare "0m".
                    this.countdownText = (days === 0 && hours === 0 && mins === 0)
                        ? this.words.lessThanMinute
                        : this.words.closesIn + ' ' + parts.join(' ');
                },
            };
        }
    </script>
@endonce
