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
                'label' => $dt->format('M Y'),
                'full_label' => $dt->format('F Y'),
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
        <div class="box-header with-border d-flex align-items-center justify-content-between flex-wrap gap-2 py-10">
            <h5 class="box-title mb-0 font-size-14 font-weight-700">{{ __('ui.calendar') }}</h5>
            
            <div class="d-flex align-items-center gap-2">
                <div class="month-select-pill">
                    <select class="month-select-input" id="events-widget-month-select">
                        @foreach($monthsList as $m)
                            <option value="{{ $m['key'] }}" {{ $m['key'] === $currentMonthKey ? 'selected' : '' }}>
                                {{ $m['label'] }}{{ $m['is_current'] ? ' • Current' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if(Auth::user()->role == 'Admin' || Auth::user()->hasRole('Admin'))
                    <a href="{{ route('event.view') }}" class="btn btn-xs btn-info-light font-size-10 px-6 py-2">{{ __('ui.manage') }}</a>
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

            <div class="event-next-panel mt-15">
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
                        </div>
                    </div>
                @else
                    <div class="event-empty-state">{{ __('ui.no_upcoming_events') }}</div>
                @endif
            </div>

            <div class="event-list mt-10" id="events-widget-month-events-list">
                <!-- Selected month events list -->
            </div>
        </div>
    </div>
</div>

<style>
    .month-select-pill {
        display: inline-flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 1px 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .month-select-input option,
    select option {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }

    body.dark-skin .month-select-pill {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    body.dark-skin .month-select-input,
    body.dark-skin select,
    body.dark-skin .month-select-input option,
    body.dark-skin select option {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }

    .dashboard-event-calendar .box-body {
        padding-top: 10px;
        position: relative;
    }

    .event-calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 4px;
    }

    .event-calendar-weekday {
        color: #64748b !important;
        font-size: 10px;
        font-weight: 700;
        text-align: center;
        text-transform: uppercase;
    }

    .event-calendar-day {
        align-items: center;
        aspect-ratio: 1;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        color: #334155 !important;
        display: flex;
        font-size: 11px;
        font-weight: 700;
        justify-content: center;
        min-height: 28px;
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
        cursor: pointer;
    }

    .event-calendar-day.has-event::after {
        background: #38bdf8;
        border-radius: 999px;
        bottom: 3px;
        content: "";
        height: 3px;
        position: absolute;
        width: 12px;
    }

    .event-calendar-popup {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        box-shadow: 0 10px 20px rgba(15, 23, 42, 0.15);
        max-width: 240px;
        padding: 8px 10px;
        pointer-events: none;
        position: absolute;
        z-index: 20;
    }

    .event-calendar-popup-item + .event-calendar-popup-item {
        border-top: 1px solid #e2e8f0;
        margin-top: 6px;
        padding-top: 6px;
    }

    .event-calendar-popup-item strong {
        color: #0b1f3a;
        display: block;
        font-size: 12px;
    }

    .event-calendar-popup-item small,
    .event-calendar-popup-item p {
        color: #64748b;
        font-size: 11px;
        margin: 2px 0 0;
    }

    .event-next-label {
        color: #64748b !important;
        display: block;
        font-weight: 700;
        letter-spacing: .04em;
        margin-bottom: 6px;
        font-size: 10px;
        text-transform: uppercase;
    }

    .event-list {
        display: grid;
        gap: 6px;
    }

    .event-list-item {
        align-items: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        display: flex;
        gap: 8px;
        padding: 6px 8px;
    }

    .event-date-pill {
        align-items: center;
        background: #e0f2fe;
        border-radius: 4px;
        color: #0b1f3a !important;
        display: flex;
        flex-direction: column;
        flex: 0 0 38px;
        justify-content: center;
        min-height: 38px;
    }

    .event-date-pill strong { font-size: 12px; }
    .event-date-pill span { font-size: 9px; font-weight: 700; text-transform: uppercase; }

    .event-list-copy { color: #334155 !important; min-width: 0; }
    .event-list-copy p { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; }
    .event-list-copy small { color: #64748b !important; font-size: 10px; }

    .event-empty-state {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 5px;
        font-weight: 600;
        font-size: 11px;
        padding: 10px;
        text-align: center;
        color: #64748b;
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
        var left = clientX - box.left + 10;
        var top = clientY - box.top + 10;
        if (left + 240 > box.width) left = Math.max(0, box.width - 250);
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
