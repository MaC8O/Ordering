<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes whether the storefront is currently accepting orders.
 *
 * The rule (always evaluated server-side, never trusted from the client):
 *   accepting_orders (manual override) == true
 *   AND today's schedule row is_open == true
 *   AND now is between open_time and (close_time - cutoff_minutes_before_close)
 */
class StoreStatus
{
    protected Setting $settings;

    /** @var Collection<int, Schedule> keyed by weekday */
    protected Collection $schedule;

    public function __construct(?Setting $settings = null)
    {
        $this->settings = $settings ?? Setting::current();
        $this->schedule = Schedule::all()->keyBy('weekday');
    }

    public function settings(): Setting
    {
        return $this->settings;
    }

    protected function now(): Carbon
    {
        return Carbon::now();
    }

    protected function rowFor(int $weekday): ?Schedule
    {
        return $this->schedule->get($weekday);
    }

    /**
     * The last moment an order will be accepted on the given day's window.
     */
    protected function lastOrderMoment(Carbon $day, Schedule $row): Carbon
    {
        return $this->timeOn($day, $row->close_time)
            ->subMinutes($this->settings->cutoff_minutes_before_close);
    }

    protected function openMoment(Carbon $day, Schedule $row): Carbon
    {
        return $this->timeOn($day, $row->open_time);
    }

    protected function timeOn(Carbon $day, string $time): Carbon
    {
        // $time may arrive as "21:00" or "21:00:00".
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return $day->copy()->setTime((int) $h, (int) $m, 0);
    }

    public function manualOverrideOn(): bool
    {
        return (bool) $this->settings->accepting_orders;
    }

    /**
     * Whether NOW falls inside today's scheduled ordering window,
     * ignoring the manual override switch.
     */
    public function scheduledOpenNow(): bool
    {
        $now = $this->now();
        $row = $this->rowFor((int) $now->dayOfWeek);

        if (! $row || ! $row->is_open) {
            return false;
        }

        return $now->betweenIncluded(
            $this->openMoment($now, $row),
            $this->lastOrderMoment($now, $row),
        );
    }

    public function isOpen(): bool
    {
        return $this->manualOverrideOn() && $this->scheduledOpenNow();
    }

    /**
     * A customer-facing reason for being closed, or null when open.
     */
    public function closedReason(): ?string
    {
        if ($this->isOpen()) {
            return null;
        }

        if (! $this->manualOverrideOn()) {
            return "We're not accepting orders right now.";
        }

        $next = $this->nextOpeningLabel();

        return $next
            ? "We're closed right now. Next opening: {$next}."
            : "We're closed right now. Please check back later.";
    }

    /**
     * The next Carbon moment the store will begin accepting orders,
     * strictly in the future (based on the weekly schedule).
     */
    public function nextOpening(): ?Carbon
    {
        $now = $this->now();

        for ($offset = 0; $offset <= 7; $offset++) {
            $day = $now->copy()->addDays($offset)->startOfDay();
            $row = $this->rowFor((int) $day->dayOfWeek);

            if (! $row || ! $row->is_open) {
                continue;
            }

            $start = $this->openMoment($day, $row);
            $lastOrder = $this->lastOrderMoment($day, $row);

            // Only future window starts that still have a usable ordering window.
            if ($start->greaterThan($now) && $lastOrder->greaterThan($start)) {
                return $start;
            }
        }

        return null;
    }

    public function nextOpeningLabel(): ?string
    {
        $next = $this->nextOpening();

        if (! $next) {
            return null;
        }

        $now = $this->now();
        $time = $next->format('H:i');

        if ($next->isSameDay($now)) {
            return "today at {$time}";
        }

        if ($next->isSameDay($now->copy()->addDay())) {
            return "tomorrow at {$time}";
        }

        return $next->format('l') . " at {$time}";
    }

    /**
     * Localized next-opening label, e.g. "tomorrow at 10:00" / "พรุ่งนี้ เวลา 10:00".
     */
    public function nextOpeningLabelLocalized(): ?string
    {
        $next = $this->nextOpening();

        if (! $next) {
            return null;
        }

        $now = $this->now();
        $time = $next->format('H:i');

        if ($next->isSameDay($now)) {
            return __('today at') . " {$time}";
        }
        if ($next->isSameDay($now->copy()->addDay())) {
            return __('tomorrow at') . " {$time}";
        }

        $day = $next->locale(app()->getLocale())->isoFormat('dddd');

        return "{$day} " . __('at') . " {$time}";
    }

    public function restaurantName(): string
    {
        return $this->settings->restaurant_name ?: 'Front Gate Restaurant';
    }
}
