{{--
    Admin live order feed.

    Polls a lightweight JSON endpoint every 30s to (1) silently refresh any
    [data-live-refresh] region on the page, (2) show a "New order!" toast and
    (3) play a short beep when a new order arrives. Written inline so it ships
    without a JS rebuild.

    Pass $feedUrl (defaults to the orders feed route).
--}}
@php($feedUrl = $feedUrl ?? route('admin.orders.feed'))

@once
    <script>
        (function () {
            const FEED_URL = @js($feedUrl);
            const INTERVAL = 30000;
            let lastId = null;
            let audioCtx = null;

            // Create / unlock the audio context on the first interaction so the
            // browser allows the beep to play.
            function ensureAudio() {
                try {
                    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    if (audioCtx.state === 'suspended') audioCtx.resume().catch(function () {});
                } catch (e) { audioCtx = null; }
            }
            window.addEventListener('pointerdown', ensureAudio, { once: true });
            window.addEventListener('keydown', ensureAudio, { once: true });

            // A short two-tone chime via the Web Audio API (no audio file needed).
            function beep() {
                ensureAudio();
                if (!audioCtx) return;
                const start = audioCtx.currentTime;
                [880, 1175].forEach(function (freq, i) {
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sine';
                    osc.frequency.value = freq;
                    const t = start + i * 0.18;
                    gain.gain.setValueAtTime(0.0001, t);
                    gain.gain.exponentialRampToValueAtTime(0.25, t + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, t + 0.16);
                    osc.connect(gain).connect(audioCtx.destination);
                    osc.start(t);
                    osc.stop(t + 0.18);
                });
            }

            function toast(msg) {
                if (window.Alpine && window.Alpine.store('toasts')) {
                    window.Alpine.store('toasts').push(msg, 'info');
                }
            }

            // Swap every [data-live-refresh="key"] region with a fresh copy from
            // the current URL, preserving filters, pagination and scroll.
            async function refreshRegions() {
                const regions = document.querySelectorAll('[data-live-refresh]');
                if (!regions.length) return;
                try {
                    const res = await fetch(window.location.href, { headers: { 'X-Requested-With': 'fetch' } });
                    if (!res.ok) return;
                    const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
                    regions.forEach(function (cur) {
                        const key = cur.getAttribute('data-live-refresh');
                        const fresh = doc.querySelector('[data-live-refresh="' + (window.CSS && CSS.escape ? CSS.escape(key) : key) + '"]');
                        if (fresh) cur.replaceWith(fresh);
                    });
                } catch (e) { /* transient network error — retry next tick */ }
            }

            async function tick() {
                let isNew = false;
                try {
                    const res = await fetch(FEED_URL, { headers: { 'X-Requested-With': 'fetch' } });
                    if (res.ok) {
                        const data = await res.json();
                        const id = Number(data.latest_id) || 0;
                        if (lastId === null) { lastId = id; return; } // baseline, no refresh on load
                        isNew = id > lastId;
                        lastId = id;
                        if (isNew) {
                            beep();
                            toast('🔔 New order! ' + (data.latest_number || ''));
                        }
                    }
                } catch (e) { /* ignore — keep polling */ }

                // Silent background refresh every tick (option 1).
                refreshRegions();
            }

            function startWhenVisible() {
                tick(); // set baseline right away
                setInterval(tick, INTERVAL);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startWhenVisible);
            } else {
                startWhenVisible();
            }
        })();
    </script>
@endonce
