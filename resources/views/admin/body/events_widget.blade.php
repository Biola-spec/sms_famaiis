@php
    $calendarDate = now();
    $currentMonthKey = $current_month_key ?? $calendarDate->format('Y-m');
    $monthsList = $months_list ?? [];
    if (empty($monthsList)) {
        $now = now();
        for ($i = -2; $i <= 9; $i++) {
            $dt = $now->copy()->addMonths($i);
            $key = $dt->format('Y-m');
            $monthsList[] = [
                'key' => $key,
                'label' => $dt->format('F Y'),
                'is_current' => $key === $currentMonthKey,
                'year' => (int) $dt->format('Y'),
                'month' => (int) $dt->format('m'),
            ];
        }
    }

    $calendarEvents = collect($calendar_events ?? []);
    $upcomingEventList = collect($upcoming_events ?? []);

    $eventsPayload = $calendarEvents->map(function ($event) {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?: '',
            'event_date' => \Carbon\Carbon::parse($event->event_date)->format('Y-m-d'),
            'event_time' => $event->event_time ? date('H:i', strtotime($event->event_time)) : __('ui.all_day'),
            'location' => $event->location ?: '',
            'section' => optional($event->section)->name ?: 'All Sections',
        ];
    })->values();

    $now = now();
    $nextEvent = $upcomingEventList
        ->concat($calendarEvents)
        ->unique('id')
        ->map(function ($event) {
            $date = \Carbon\Carbon::parse($event->event_date)->startOfDay();
            if (!empty($event->event_time)) {
                $event->_occurs_at = \Carbon\Carbon::parse($date->format('Y-m-d') . ' ' . $event->event_time);
            } else {
                $event->_occurs_at = $date->copy()->endOfDay();
            }
            return $event;
        })
        ->filter(function ($event) use ($now) {
            return $event->_occurs_at->gte($now);
        })
        ->sortBy(function ($event) {
            return $event->_occurs_at->timestamp;
        })
        ->first();
@endphp

<div class="col-xl-4 col-12">
    <div class="box dashboard-event-calendar">
        <div class="box-header with-border d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
                <h4 class="box-title mb-0">{{ __('ui.calendar') }}</h4>
            </div>
            <div class="d-flex align-items-center gap-2">
                <select class="form-control form-control-sm dashboard-events-month-select" id="events-widget-month-select" style="width: auto; max-width: 150px; font-weight: 600;">
                    @foreach($monthsList as $m)
                        <option value="{{ $m['key'] }}" {{ $m['key'] === $currentMonthKey ? 'selected' : '' }}>
                            {{ $m['label'] }} {{ $m['is_current'] ? '(Current)' : '' }}
                        </option>
                    @endforeach
                </select>
                @if(Auth::user()->role == 'Admin' || Auth::user()->hasRole('Admin'))
                    <a href="{{ route('event.view') }}" class="btn btn-sm btn-info-light">{{ __('ui.manage') }}</a>
                @endif
            </div>
        </div>

        <div class="box-body">
            <div class="event-calendar-grid" id="event-calendar-grid">
                <!-- Populated via JS -->
            </div>

            <div id="event-calendar-popup" class="event-calendar-popup" hidden>
                <div class="event-calendar-popup-inner"></div>
            </div>

            <div class="event-next-panel mt-20">
                <small class="event-next-label">{{ __('ui.next_event') }}</small>
                @if($nextEvent)
                    <div class="event-list-item">
                        <div class="event-date-pill">
                            <strong>{{ date('d', strtotime($nextEvent->event_date)) }}</strong>
                            <span>{{ date('M', strtotime($nextEvent->event_date)) }}</span>
                        </div>
                        <div class="event-list-copy">
                            <p class="mb-0 font-weight-600">{{ $nextEvent->title }}</p>
                            <small>
                                {{ $nextEvent->event_time ? date('H:i', strtotime($nextEvent->event_time)) : __('ui.all_day') }}
                                @if($nextEvent->location)
                                    <span class="mx-5">|</span>{{ $nextEvent->location }}
                                @endif
                            </small>
                            @if($nextEvent->description)
                                <small class="d-block mt-5">{{ \Illuminate\Support\Str::limit(strip_tags($nextEvent->description), 120) }}</small>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="event-empty-state">{{ __('ui.no_upcoming_events') }}</div>
                @endif
            </div>

            <div class="event-list mt-15" id="events-widget-month-events-list">
                <!-- Selected month events list -->
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-event-calendar .box-body {
        padding-top: 14px;
        position: relative;
    }

    .event-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 6px;
    }

    .event-calendar-weekday {
        color: #64748b !important;
        font-size: 11px;
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
    }

    .event-calendar-day {
        align-items: center;
        aspect-ratio: 1;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        color: #334155 !important;
        display: flex;
        font-size: 13px;
        font-weight: 700;
        justify-content: center;
        min-height: 34px;
        position: relative;
    }

    .event-calendar-day.is-empty {
        background: transparent;
        border-color: transparent;
        pointer-events: none;
    }

    .event-calendar-day.is-today {
        border-color: #0b1f3a;
        color: #0b1f3a !important;
        font-weight: 800;
    }

    .event-calendar-day.has-event {
        background: linear-gradient(135deg, #0b1f3a, #164e78);
        border-color: #164e78;
        color: #ffffff !important;
        box-shadow: 0 8px 16px rgba(11, 31, 58, 0.18);
        cursor: pointer;
    }

    .event-calendar-day.has-event::after {
        background: #38bdf8;
        border-radius: 999px;
        bottom: 5px;
        content: "";
        height: 4px;
        position: absolute;
        width: 16px;
    }

    .event-calendar-popup {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.18);
        max-width: 260px;
        padding: 10px 12px;
        pointer-events: none;
        position: absolute;
        z-index: 20;
    }

    .event-calendar-popup-item + .event-calendar-popup-item {
        border-top: 1px solid #e2e8f0;
        margin-top: 8px;
        padding-top: 8px;
    }

    .event-calendar-popup-item strong {
        color: #0b1f3a;
        display: block;
        font-size: 13px;
    }

    .event-calendar-popup-item small,
    .event-calendar-popup-item p {
        color: #64748b;
        font-size: 12px;
        margin: 2px 0 0;
    }

    .event-next-label {
        color: #64748b !important;
        display: block;
        font-weight: 700;
        letter-spacing: .04em;
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .event-list {
        display: grid;
        gap: 10px;
    }

    .event-list-item {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        display: flex;
        gap: 12px;
        padding: 10px;
    }

    .event-date-pill {
        align-items: center;
        background: #e0f2fe;
        border-radius: 6px;
        color: #0b1f3a !important;
        display: flex;
        flex-direction: column;
        flex: 0 0 46px;
        justify-content: center;
        min-height: 46px;
    }

    .event-date-pill strong,
    .event-date-pill span,
    .event-list-copy p,
    .event-list-copy small,
    .event-empty-state {
        color: inherit !important;
    }

    .event-date-pill span {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }

    .event-list-copy {
        color: #334155 !important;
        min-width: 0;
    }

    .event-list-copy p {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .event-list-copy small,
    .event-empty-state {
        color: #64748b !important;
    }

    .event-empty-state {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 6px;
        font-weight: 600;
        padding: 14px;
        text-align: center;
    }

    body.dark-skin .event-calendar-weekday,
    body.dark-skin .event-list-copy small,
    body.dark-skin .event-empty-state,
    body.dark-skin .event-next-label {
        color: #8a99b5 !important;
    }

    body.dark-skin .event-calendar-day {
        background: #272e48;
        border-color: rgba(255, 255, 255, 0.12);
        color: #e1e6f2 !important;
    }

    body.dark-skin .event-calendar-day.is-empty {
        background: transparent;
        border-color: transparent;
    }

    body.dark-skin .event-list-item,
    body.dark-skin .event-empty-state,
    body.dark-skin .event-calendar-popup {
        background: #272e48;
        border-color: rgba(255, 255, 255, 0.12);
    }

    body.dark-skin .event-list-copy,
    body.dark-skin .event-calendar-popup-item strong {
        color: #e1e6f2 !important;
    }
</style>

<script>
(function () {
    const eventsData = @json($eventsPayload);
    const grid = document.getElementById('event-calendar-grid');
    const popup = document.getElementById('event-calendar-popup');
    const selectEl = document.getElementById('events-widget-month-select');
    const monthListEl = document.getElementById('events-widget-month-events-list');

    if (!grid || !popup) return;

    const inner = popup.querySelector('.event-calendar-popup-inner');
    let hoverTimer = null;
    let activeCell = null;
    const isTouch = window.matchMedia('(hover: none)').matches;
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    function hidePopup() {
        popup.hidden = true;
        activeCell = null;
    }

    function renderEvents(events) {
        inner.innerHTML = events.map(function (event) {
            var desc = event.description ? '<p>' + escapeHtml(event.description) + '</p>' : '';
            var loc = event.location ? ' · ' + escapeHtml(event.location) : '';
            return '<div class="event-calendar-popup-item"><strong>' + escapeHtml(event.title) + '</strong><small>' + escapeHtml(event.event_time) + loc + '</small>' + desc + '</div>';
        }).join('');
    }

    function showPopup(cell, clientX, clientY) {
        var raw = cell.getAttribute('data-events');
        if (!raw) return;
        var events;
        try { events = JSON.parse(raw); } catch (e) { return; }
        if (!events || !events.length) return;
        renderEvents(events);
        popup.hidden = false;
        var box = grid.parentElement.getBoundingClientRect();
        var left = clientX - box.left + 12;
        var top = clientY - box.top + 12;
        if (left + 260 > box.width) left = Math.max(0, box.width - 270);
        popup.style.left = left + 'px';
        popup.style.top = top + 'px';
        activeCell = cell;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function renderCalendarGrid(yearMonth) {
        const [year, month] = yearMonth.split('-').map(Number);
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        const startDayOfWeek = firstDay.getDay();

        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        const monthEvents = eventsData.filter(e => e.event_date.startsWith(yearMonth));
        const eventsByDate = {};
        monthEvents.forEach(e => {
            if (!eventsByDate[e.event_date]) eventsByDate[e.event_date] = [];
            eventsByDate[e.event_date].push(e);
        });

        let html = '';
        weekdays.forEach(wd => {
            html += '<div class="event-calendar-weekday">' + wd + '</div>';
        });

        for (let cell = 0; cell < 42; cell++) {
            const dayNum = cell - startDayOfWeek + 1;
            const isInMonth = dayNum >= 1 && dayNum <= daysInMonth;
            if (isInMonth) {
                const dateStr = yearMonth + '-' + String(dayNum).padStart(2, '0');
                const dayEvts = eventsByDate[dateStr] || [];
                const hasEvent = dayEvts.length > 0;
                const isToday = dateStr === todayStr;
                const payload = dayEvts.map(e => ({
                    title: e.title,
                    event_time: e.event_time,
                    description: e.description,
                    location: e.location
                }));

                html += '<div class="event-calendar-day ' + (isInMonth ? '' : 'is-empty') + ' ' + (hasEvent ? 'has-event' : '') + ' ' + (isToday ? 'is-today' : '') + '"';
                if (hasEvent) {
                    html += ' data-events=\'' + JSON.stringify(payload).replace(/'/g, "&apos;") + '\'';
                }
                html += '><span>' + dayNum + '</span></div>';
            } else {
                html += '<div class="event-calendar-day is-empty"></div>';
            }
        }
        grid.innerHTML = html;

        // Render List under calendar
        if (monthListEl) {
            if (monthEvents.length === 0) {
                monthListEl.innerHTML = '<div class="event-empty-state">No events scheduled for this month</div>';
            } else {
                let listHtml = '';
                monthEvents.sort((a, b) => a.event_date.localeCompare(b.event_date));
                monthEvents.slice(0, 4).forEach(e => {
                    const d = new Date(e.event_date + 'T00:00:00');
                    const dayNum = d.getDate();
                    const monthStr = d.toLocaleDateString('en-US', { month: 'short' });

                    listHtml += '<div class="event-list-item">';
                    listHtml += '<div class="event-date-pill"><strong>' + dayNum + '</strong><span>' + monthStr + '</span></div>';
                    listHtml += '<div class="event-list-copy">';
                    listHtml += '<p class="mb-0 font-weight-600">' + escapeHtml(e.title) + '</p>';
                    listHtml += '<small>' + escapeHtml(e.event_time) + (e.location ? '<span class="mx-5">|</span>' + escapeHtml(e.location) : '') + '</small>';
                    listHtml += '</div></div>';
                });
                monthListEl.innerHTML = listHtml;
            }
        }
    }

    if (selectEl) {
        selectEl.addEventListener('change', function () {
            hidePopup();
            renderCalendarGrid(this.value);
        });
        renderCalendarGrid(selectEl.value);
    }

    grid.addEventListener('mouseover', function (e) {
        var cell = e.target.closest('.event-calendar-day.has-event');
        if (!cell || isTouch) return;
        clearTimeout(hoverTimer);
        hoverTimer = setTimeout(function () {
            showPopup(cell, e.clientX, e.clientY);
        }, 200);
    });

    grid.addEventListener('mouseout', function (e) {
        var cell = e.target.closest('.event-calendar-day.has-event');
        if (!cell) return;
        clearTimeout(hoverTimer);
        if (!e.relatedTarget || !cell.contains(e.relatedTarget)) {
            hidePopup();
        }
    });

    grid.addEventListener('click', function (e) {
        var cell = e.target.closest('.event-calendar-day.has-event');
        if (!cell) return;
        if (activeCell === cell && !popup.hidden) {
            hidePopup();
            return;
        }
        showPopup(cell, e.clientX, e.clientY);
    });

    document.addEventListener('click', function (e) {
        if (!grid.contains(e.target)) hidePopup();
    });
})();
</script>
