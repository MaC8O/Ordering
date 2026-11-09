<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

/**
 * Computes the weekly preorder batch window — always server-side.
 *
 * Customers collect orders during a window (e.g. Saturday 20:00 → Sunday 09:00),
 * then everything is cooked and delivered in a single batch run on the
 * fulfillment day (e.g. Monday 10:00–12:00). All weekday numbers follow Carbon's
 * dayOfWeek: 0 = Sunday … 6 = Saturday.
 */
class PreorderStatus
{
    protected Setting $settings;

    public function __construct(?Setting $settings = null)
    {
        $this->settings = $settings ?? Setting::current();
    }

    public function settings(): Setting
    {
        return $this->settings;
    }

    public function enabled(): bool
    {
        return (bool) $this->settings->preorder_enabled;
    }

    /**
     * Admin override: preorders are forced open right now, ignoring the weekly
     * schedule, until the admin turns it off again.
     */
    public function forcedOpen(): bool
    {
        return $this->enabled() && (bool) $this->settings->preorder_force_open;
    }

    protected function now(): Carbon
    {
        return Carbon::now();
    }

    /**
     * The most recent moment the collection window opened, at or before now.
     */
    protected function mostRecentOpen(): Carbon
    {
        [$h, $m] = $this->hm($this->settings->preorder_open_time);
        $open = $this->now()->copy()->setTime($h, $m, 0);

        // Walk back to the configured open weekday.
        while ($open->dayOfWeek !== (int) $this->settings->preorder_open_day) {
            $open->subDay();
        }

        // If that landed in the future (today is the open day but before open time),
        // step back a full week so it is genuinely "the most recent" open.
        if ($open->greaterThan($this->now())) {
            $open->subWeek();
        }

        return $open;
    }

    /**
     * The cutoff that closes the window opened by mostRecentOpen().
     */
    protected function cutoffAfter(Carbon $open): Carbon
    {
        [$h, $m] = $this->hm($this->settings->preorder_cutoff_time);
        $cut = $open->copy();

        do {
            $cut->addDay();
        } while ($cut->dayOfWeek !== (int) $this->settings->preorder_cutoff_day);

        $cut->setTime($h, $m, 0);

        // Guard against a misconfiguration where cutoff would precede open.
        if ($cut->lessThanOrEqualTo($open)) {
            $cut->addWeek();
        }

        return $cut;
    }

    /**
     * Whether customers can place preorders right now.
     */
    public function isOpen(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        // Admin "open now" override wins over the weekly schedule.
        if ($this->forcedOpen()) {
            return true;
        }

        $open = $this->mostRecentOpen();
        $cutoff = $this->cutoffAfter($open);

        return $this->now()->betweenIncluded($open, $cutoff);
    }

    /**
     * The cutoff moment for the window that is open now (null when closed).
     */
    public function closesAt(): ?Carbon
    {
        if (! $this->isOpen()) {
            return null;
        }

        // Forced-open stays open until the admin closes it — no scheduled cutoff.
        if ($this->forcedOpen()) {
            return null;
        }

        return $this->cutoffAfter($this->mostRecentOpen());
    }

    /**
     * The next moment preorders open, strictly in the future (when closed).
     */
    public function opensAt(): ?Carbon
    {
        if (! $this->enabled() || $this->isOpen()) {
            return null;
        }

        $open = $this->mostRecentOpen();
        $next = $open->copy();

        // Advance in whole weeks until it is in the future.
        while ($next->lessThanOrEqualTo($this->now())) {
            $next->addWeek();
        }

        return $next;
    }

    /**
     * The date+start-time of the batch that the current (or next) window feeds.
     * When open now, this is the upcoming fulfillment day after the cutoff.
     */
    public function fulfillmentAt(): Carbon
    {
        [$h, $m] = $this->hm($this->settings->preorder_window_start);

        // An admin-pinned exact date overrides the recurring weekday — as long as
        // it hasn't already passed (a stale date falls back to the weekly schedule
        // so customers never see a delivery date in the past).
        $pinned = $this->settings->preorder_fulfillment_date;
        if ($pinned && $pinned->copy()->endOfDay()->greaterThanOrEqualTo($this->now())) {
            return $pinned->copy()->setTime($h, $m, 0);
        }

        $anchor = $this->isOpen()
            ? $this->cutoffAfter($this->mostRecentOpen())
            : ($this->opensAt() ?? $this->now());

        $date = $anchor->copy();

        while ($date->dayOfWeek !== (int) $this->settings->preorder_fulfillment_day) {
            $date->addDay();
        }

        return $date->setTime($h, $m, 0);
    }

    /**
     * Whether the batch date is a specific admin-pinned date (vs. the recurring
     * weekday). True only while that pinned date is still current.
     */
    public function hasPinnedDate(): bool
    {
        $pinned = $this->settings->preorder_fulfillment_date;

        return $pinned && $pinned->copy()->endOfDay()->greaterThanOrEqualTo($this->now());
    }

    /**
     * The full delivery date spelled out for customers, e.g.
     * "Monday, 10 November 2026" — localized.
     */
    public function fulfillmentDateLabel(): string
    {
        return $this->fulfillmentAt()
            ->locale(app()->getLocale())
            ->isoFormat('dddd, D MMMM YYYY');
    }

    /**
     * The fulfillment date as a plain Y-m-d string (used as the order's delivery_date).
     */
    public function fulfillmentDate(): string
    {
        return $this->fulfillmentAt()->toDateString();
    }

    public function deliveryFee(): int
    {
        return (int) $this->settings->preorder_delivery_fee;
    }

    /**
     * "Monday, 10 Nov · 10:00–12:00" — localized.
     */
    public function windowLabel(): string
    {
        $date = $this->fulfillmentAt()->locale(app()->getLocale());

        return $date->isoFormat('dddd, D MMM')
            . ' · ' . $this->settings->preorder_window_start
            . '–' . $this->settings->preorder_window_end;
    }

    /**
     * Short time window, e.g. "10:00–12:00".
     */
    public function timeWindow(): string
    {
        return $this->settings->preorder_window_start . '–' . $this->settings->preorder_window_end;
    }

    /**
     * A friendly "opens Saturday at 20:00" style label for when it's closed.
     */
    public function opensAtLabel(): ?string
    {
        $at = $this->opensAt();

        if (! $at) {
            return null;
        }

        $now = $this->now();
        $time = $at->format('H:i');

        if ($at->isSameDay($now)) {
            return __('today at') . " {$time}";
        }
        if ($at->isSameDay($now->copy()->addDay())) {
            return __('tomorrow at') . " {$time}";
        }

        $day = $at->locale(app()->getLocale())->isoFormat('dddd');

        return "{$day} " . __('at') . " {$time}";
    }

    /**
     * @return array{0:int,1:int} hour and minute parsed from "HH:MM" (or "HH:MM:SS")
     */
    protected function hm(string $time): array
    {
        [$h, $m] = array_pad(explode(':', $time), 2, '0');

        return [(int) $h, (int) $m];
    }
}
