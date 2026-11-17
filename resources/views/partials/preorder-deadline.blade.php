{{--
    Live preorder countdown — rendered as its own element (separate boxes for
    days / hours / minutes), meant to sit next to the "Preorder now" button
    rather than inside a sentence.

    Expects $preorderStatus in scope (shared by the storefront view composer).
    Renders nothing unless there is a scheduled cutoff (a plain weekly window —
    not an admin "forced open" with no deadline).

    Options:
      $variant — kept for backwards-compatibility; the box style is shared across
                 the storefront banner, the menu promo and the preorder page
                 (all sit on the purple gradient), so the look is the same.
      $announceExpiry — when true, dispatch a window `preorder-expired` event as
                        the deadline passes, so a surrounding form can close.
--}}
@php
    $deadlineIso = $preorderStatus->closesAtIso();
    $nextOpenLabel = $preorderStatus->nextOpenLabel();
    $announceExpiry = $announceExpiry ?? false;
@endphp

@if ($deadlineIso)
    <div
        x-data="preorderCountdown({
            deadline: @js($deadlineIso),
            next: @js($nextOpenLabel),
            announce: {{ $announceExpiry ? 'true' : 'false' }},
            urgentHours: 6,
            words: {
                days: @js(__('days')),
                hrs: @js(__('hrs')),
                min: @js(__('min')),
                closed: @js(__('Preordering just closed.')),
                opens: @js(__('Opens :when', ['when' => $nextOpenLabel ?? ''])),
            },
        })"
        x-init="start()"
        class="shrink-0"
    >
        {{-- Live window still open: three boxes, tabular digits so nothing jumps.
             Under the urgent threshold the whole block warms to amber. --}}
        <div
            x-show="! expired"
            x-cloak
            class="flex items-stretch gap-1 rounded-xl p-1 ring-1 transition-colors"
            :class="urgent ? 'bg-amber-500/90 ring-amber-200/60' : 'bg-white/10 ring-white/20'"
            role="timer"
        >
            <template x-for="cell in cells" :key="cell.label">
                <div class="flex min-w-[2.75rem] flex-col items-center rounded-lg bg-white/15 px-2.5 py-1.5">
                    <span class="text-xl font-bold leading-none tabular-nums text-white" x-text="cell.value"></span>
                    <span class="mt-1 text-[10px] font-semibold uppercase leading-none tracking-wider text-white/75" x-text="cell.label"></span>
                </div>
            </template>
        </div>

        {{-- Deadline has passed (updated live, without a reload). --}}
        <div
            x-show="expired"
            x-cloak
            class="rounded-xl bg-white/15 px-3 py-2 text-xs font-semibold text-white ring-1 ring-white/20"
        >
            <span x-text="words.closed"></span>
            <span x-show="next" x-text="words.opens"></span>
        </div>
    </div>
@endif

@once
    <script>
        function preorderCountdown(cfg) {
            const pad = (n) => String(n).padStart(2, '0');

            return {
                expired: false,
                urgent: false,
                days: '00',
                hours: '00',
                mins: '00',
                next: cfg.next,
                words: cfg.words,
                _timer: null,

                get cells() {
                    return [
                        { value: this.days, label: this.words.days },
                        { value: this.hours, label: this.words.hrs },
                        { value: this.mins, label: this.words.min },
                    ];
                },

                start() {
                    this.tick();
                    // Tick every second so the urgent threshold flips promptly and
                    // the minute boundary stays accurate; only the rendered digits
                    // (minute granularity) actually change.
                    this._timer = setInterval(() => this.tick(), 1000);
                },

                tick() {
                    const end = new Date(cfg.deadline).getTime();
                    if (isNaN(end)) { return; }

                    let secs = Math.floor((end - Date.now()) / 1000);

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

                    this.urgent = secs < (cfg.urgentHours * 3600);
                    this.days = pad(Math.floor(secs / 86400));
                    this.hours = pad(Math.floor((secs % 86400) / 3600));
                    this.mins = pad(Math.floor((secs % 3600) / 60));
                },
            };
        }
    </script>
@endonce
