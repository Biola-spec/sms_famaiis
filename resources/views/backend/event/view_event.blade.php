@extends('admin.admin_master')
@section('admin')

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

    $allEvents = collect($allData ?? []);
    $eventsPayload = $allEvents->map(function ($event) {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?: '',
            'event_date' => \Carbon\Carbon::parse($event->event_date)->format('Y-m-d'),
            'event_time' => $event->event_time ? \Carbon\Carbon::parse($event->event_time)->format('h:i A') : 'All day',
            'location' => $event->location ?: '',
            'section' => optional($event->section)->name ?: 'All Sections',
            'is_notified' => (bool)$event->is_notified,
            'edit_url' => route('event.edit', $event->id),
            'delete_url' => route('event.delete', $event->id),
            'registrations_url' => route('event.registrations.view', $event->id),
        ];
    })->values();

    $isAdmin = Auth::user()->hasRole('Admin');
@endphp

<div class="content-wrapper">
    <div class="container-full">
        <!-- Compact Header -->
        <div class="content-header py-10 px-20">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="page-title mb-0 font-size-15 font-weight-700 text-dark">
                    <i class="fa fa-calendar text-primary mr-5"></i> School Calendar
                </h5>

                <div class="d-flex align-items-center flex-wrap gap-2">
                    <!-- Compact Month Selector Pill -->
                    <div class="month-select-pill">
                        <span class="month-select-label"><i class="fa fa-filter text-primary"></i> Month:</span>
                        <select id="calendar-month-select" class="month-select-input">
                            @foreach($monthsList as $m)
                                <option value="{{ $m['key'] }}" {{ $m['key'] === $currentMonthKey ? 'selected' : '' }}>
                                    {{ $m['label'] }}{{ $m['is_current'] ? ' • Current' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- View Switcher -->
                    <div class="btn-group" role="group">
                        <button type="button" id="btn-toggle-calendar" class="btn btn-xs btn-primary active font-size-10 font-weight-600 px-8 py-3"><i class="fa fa-calendar mr-3"></i> Grid View</button>
                        <button type="button" id="btn-toggle-table" class="btn btn-xs btn-outline-secondary font-size-10 font-weight-600 px-8 py-3"><i class="fa fa-list mr-3"></i> Table View</button>
                    </div>

                    @if($isAdmin)
                        <a href="{{ route('event.add') }}" class="btn btn-xs btn-success font-size-10 font-weight-600 py-3 px-8"><i class="fa fa-plus-circle mr-3"></i> Add Event</a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content pt-0">
            <!-- CALENDAR GRID VIEW SECTION -->
            <div id="calendar-grid-view-section">
                <div class="row">
                    <!-- Compact Calendar Grid Column (Max half the page width) -->
                    <div class="col-xl-5 col-lg-6 col-12">
                        <div class="box box-solid border shadow-sm mb-15">
                            <div class="box-header with-border py-8 px-12 d-flex align-items-center justify-content-between bg-light-gray">
                                <h6 class="box-title font-size-12 font-weight-700 text-dark mb-0" id="current-selected-month-label">Calendar</h6>
                                <span class="badge badge-primary font-size-9 px-6 py-2" id="month-event-count-badge">0 events</span>
                            </div>
                            <div class="box-body p-0">
                                <div class="full-school-calendar">
                                    <div class="calendar-grid-header">
                                        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                                    </div>
                                    <div class="calendar-grid-body" id="full-calendar-grid-body">
                                        <!-- JS dynamic rendering -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Events Schedule List Column (Fills remaining half) -->
                    <div class="col-xl-7 col-lg-6 col-12">
                        <div class="box box-solid border shadow-sm mb-15">
                            <div class="box-header with-border py-8 px-12 bg-light-gray d-flex align-items-center justify-content-between">
                                <h6 class="box-title font-size-12 font-weight-700 text-dark mb-0">
                                    <i class="fa fa-list-alt text-info mr-5"></i> Events Schedule
                                </h6>
                                <small class="text-muted font-size-10">Click any event for details</small>
                            </div>
                            <div class="box-body p-10" style="max-height: 420px; overflow-y: auto;" id="weekly-events-breakdown">
                                <!-- JS populated weekly cards -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLE VIEW SECTION -->
            <div id="calendar-table-view-section" style="display: none;">
                <div class="box box-solid border shadow-sm">
                    <div class="box-header with-border py-8 px-15 d-flex justify-content-between align-items-center">
                        <h6 class="box-title font-size-13 font-weight-700">All Events List</h6>
                        @if($isAdmin)
                            <a href="{{ route('event.add') }}" class="btn btn-xs btn-success"><i class="fa fa-plus"></i> Add Event</a>
                        @endif
                    </div>
                    <div class="box-body p-12">
                        <div class="table-responsive">
                            <table id="example1" class="table table-bordered table-hover font-size-11 mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="5%" class="py-6 font-size-10">SL</th>
                                        <th class="py-6 font-size-10">Title</th>
                                        <th class="py-6 font-size-10">Date & Time</th>
                                        <th class="py-6 font-size-10">Location</th>
                                        <th class="py-6 font-size-10">Section</th>
                                        <th class="py-6 font-size-10">Notified</th>
                                        @if($isAdmin)
                                            <th width="18%" class="py-6 font-size-10">Action</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allData as $key => $event)
                                        <tr>
                                            <td class="py-6 font-size-11">{{ $key + 1 }}</td>
                                            <td class="py-6 font-weight-600 font-size-11">{{ $event->title }}</td>
                                            <td class="py-6 font-size-10">
                                                {{ date('d M Y', strtotime($event->event_date)) }} <br>
                                                <small class="text-muted">{{ $event->event_time ? \Carbon\Carbon::parse($event->event_time)->format('h:i A') : 'All day' }}</small>
                                            </td>
                                            <td class="py-6 font-size-10">{{ $event->location ?: '-' }}</td>
                                            <td class="py-6"><span class="badge badge-info font-size-9">{{ optional($event->section)->name ?: 'All Sections' }}</span></td>
                                            <td class="py-6">
                                                @if($event->is_notified)
                                                    <span class="badge badge-success font-size-9">Yes</span>
                                                @else
                                                    <span class="badge badge-secondary font-size-9">No</span>
                                                @endif
                                            </td>
                                            @if($isAdmin)
                                                <td class="py-6">
                                                    <a href="{{ route('event.edit', $event->id) }}" class="btn btn-xs btn-info" title="Edit"><i class="fa fa-edit"></i></a>
                                                    <a href="{{ route('event.registrations.view', $event->id) }}" class="btn btn-xs btn-primary" title="View Registrations"><i class="fa fa-users"></i></a>
                                                    <a href="{{ route('event.delete', $event->id) }}" class="btn btn-xs btn-danger" id="delete" title="Delete"><i class="fa fa-trash"></i></a>
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Event Detail Modal -->
<div class="modal fade" id="eventDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" style="max-width: 380px;" role="document">
        <div class="modal-content border-0 shadow-lg rounded">
            <div class="modal-header bg-dark text-white py-8 px-12">
                <h6 class="modal-title font-size-12 font-weight-700 text-white" id="modalEventTitle">Event Details</h6>
                <button type="button" class="close text-white opacity-8 font-size-14" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-12">
                <div class="mb-8">
                    <span class="badge badge-info font-size-9 font-weight-600 mb-4" id="modalEventSection">All Sections</span>
                    <h6 class="font-weight-700 text-dark mb-2 font-size-13" id="modalEventHeading">Title</h6>
                </div>
                <div class="row bg-light py-6 px-8 rounded mb-8 mx-0 border">
                    <div class="col-6 px-4">
                        <small class="text-muted font-size-9 d-block"><i class="fa fa-calendar text-primary"></i> Date</small>
                        <strong id="modalEventDate" class="font-size-10 text-dark">-</strong>
                    </div>
                    <div class="col-6 px-4">
                        <small class="text-muted font-size-9 d-block"><i class="fa fa-clock-o text-primary"></i> Time</small>
                        <strong id="modalEventTime" class="font-size-10 text-dark">-</strong>
                    </div>
                </div>
                <div class="mb-8" id="modalEventLocationWrap">
                    <small class="text-muted font-size-9 d-block"><i class="fa fa-map-marker text-danger"></i> Location</small>
                    <span id="modalEventLocation" class="font-weight-600 font-size-10 text-dark">-</span>
                </div>
                <div class="mb-4">
                    <small class="text-muted font-size-9 d-block"><i class="fa fa-align-left text-info"></i> Description</small>
                    <p id="modalEventDescription" class="text-secondary font-size-10 mb-0 line-height-14">-</p>
                </div>
            </div>
            <div class="modal-footer bg-light py-6 px-12" id="modalEventActions">
                <button type="button" class="btn btn-xs btn-secondary font-size-10" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-light-gray { background-color: #f8fafc; }

    .month-select-pill {
        display: inline-flex;
        align-items: center;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        padding: 2px 6px;
        gap: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    .month-select-label {
        font-size: 10px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .month-select-input {
        border: 0;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        color: #0f172a;
        outline: none;
        cursor: pointer;
        padding: 0 2px;
        height: 22px;
    }

    .full-school-calendar {
        border: 0;
        background: #ffffff;
    }

    .calendar-grid-header {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        background: #0f172a;
        color: #f8fafc;
        font-weight: 700;
        text-align: center;
        padding: 5px 0;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .calendar-grid-body {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 1px;
        background: #e2e8f0;
    }

    .calendar-day-cell {
        background: #ffffff;
        min-height: 48px;
        padding: 3px 4px;
        position: relative;
        display: flex;
        flex-direction: column;
        transition: background 0.12s ease;
    }

    .calendar-day-cell:hover {
        background: #f1f5f9;
    }

    .calendar-day-cell.is-empty {
        background: #fafafa;
        opacity: 0.4;
    }

    .calendar-day-cell.is-today {
        background: #eff6ff;
    }

    .calendar-day-cell.is-today .day-number {
        background: #2563eb;
        color: #ffffff;
        border-radius: 50%;
        width: 17px;
        height: 17px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 9px;
    }

    .day-number {
        font-weight: 700;
        font-size: 10px;
        color: #475569;
        margin-bottom: 2px;
        line-height: 1;
    }

    .day-events-list {
        display: flex;
        flex-direction: column;
        gap: 2px;
        overflow-y: auto;
        max-height: 30px;
    }

    .event-chip {
        background: #2563eb;
        color: #ffffff;
        padding: 1px 4px;
        border-radius: 2px;
        font-size: 9px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        transition: transform 0.1s ease, background 0.12s ease;
        line-height: 1.2;
    }

    .event-chip:hover {
        background: #1d4ed8;
        transform: translateY(-1px);
    }

    .event-chip-section {
        background: rgba(255, 255, 255, 0.25);
        padding: 0 2px;
        border-radius: 2px;
        font-size: 8px;
        margin-right: 2px;
        font-weight: 700;
    }

    /* Weekly breakdown styling */
    .week-card {
        border: 1px solid #e2e8f0;
        border-radius: 5px;
        margin-bottom: 8px;
        overflow: hidden;
        background: #ffffff;
    }

    .week-card-header {
        background: #f8fafc;
        padding: 5px 8px;
        font-weight: 700;
        font-size: 10px;
        color: #0f172a;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .week-event-row {
        padding: 6px 8px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.12s ease;
    }

    .week-event-row:hover {
        background: #f8fafc;
    }

    .week-event-row:last-child {
        border-bottom: none;
    }

    .week-event-date-pill {
        background: #eff6ff;
        color: #1d4ed8;
        font-weight: 700;
        padding: 2px 4px;
        border-radius: 4px;
        text-align: center;
        min-width: 38px;
        border: 1px solid #dbeafe;
    }

    .week-event-date-pill strong {
        display: block;
        font-size: 11px;
        line-height: 1;
    }

    .week-event-date-pill small {
        font-size: 8px;
        text-transform: uppercase;
        color: #3b82f6;
    }

    .week-event-details {
        flex: 1;
        min-width: 0;
    }

    .week-event-title {
        font-weight: 700;
        font-size: 11px;
        color: #1e293b;
        margin-bottom: 1px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .week-event-meta {
        font-size: 9px;
        color: #64748b;
    }

    .month-select-input option,
    select option {
        background-color: #ffffff !important;
        color: #0f172a !important;
    }

    body.dark-skin .bg-light-gray {
        background-color: #0f172a !important;
    }

    body.dark-skin .month-select-pill {
        background: #1e293b !important;
        border-color: #334155 !important;
    }

    body.dark-skin .month-select-label {
        color: #94a3b8 !important;
    }

    body.dark-skin .month-select-input,
    body.dark-skin select,
    body.dark-skin .month-select-input option,
    body.dark-skin select option {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
    }

    body.dark-skin .full-school-calendar {
        background: #1e293b;
    }

    body.dark-skin .calendar-grid-body {
        background: #334155;
    }

    body.dark-skin .calendar-day-cell {
        background: #1e293b;
        color: #f1f5f9;
    }

    body.dark-skin .calendar-day-cell.is-empty {
        background: #0f172a;
    }

    body.dark-skin .calendar-day-cell.is-today {
        background: #1e3a8a;
    }

    body.dark-skin .day-number {
        color: #cbd5e1;
    }

    body.dark-skin .week-card {
        background: #1e293b;
        border-color: #334155;
    }

    body.dark-skin .week-card-header {
        background: #0f172a;
        color: #f1f5f9;
        border-color: #334155;
    }

    body.dark-skin .week-event-row {
        border-color: #334155;
    }

    body.dark-skin .week-event-title {
        color: #f1f5f9;
    }

    body.dark-skin .week-event-date-pill {
        background: #1e3a8a;
        color: #93c5fd;
        border-color: #2563eb;
    }

    body.dark-skin .week-event-date-pill small {
        color: #93c5fd;
    }
</style>

<script>
(function () {
    const eventsData = @json($eventsPayload);
    const isAdmin = @json($isAdmin);
    const monthsList = @json($monthsList);

    const monthSelect = document.getElementById('calendar-month-select');
    const monthLabelEl = document.getElementById('current-selected-month-label');
    const monthCountBadgeEl = document.getElementById('month-event-count-badge');
    const calendarGridBody = document.getElementById('full-calendar-grid-body');
    const weeklyBreakdownEl = document.getElementById('weekly-events-breakdown');

    const btnCalendar = document.getElementById('btn-toggle-calendar');
    const btnTable = document.getElementById('btn-toggle-table');
    const calendarSection = document.getElementById('calendar-grid-view-section');
    const tableSection = document.getElementById('calendar-table-view-section');

    // Toggle between views
    btnCalendar.addEventListener('click', function () {
        btnCalendar.classList.add('btn-primary', 'active');
        btnCalendar.classList.remove('btn-outline-secondary');
        btnTable.classList.remove('btn-primary', 'active');
        btnTable.classList.add('btn-outline-secondary');
        calendarSection.style.display = 'block';
        tableSection.style.display = 'none';
    });

    btnTable.addEventListener('click', function () {
        btnTable.classList.add('btn-primary', 'active');
        btnTable.classList.remove('btn-outline-secondary');
        btnCalendar.classList.remove('btn-primary', 'active');
        btnCalendar.classList.add('btn-outline-secondary');
        tableSection.style.display = 'block';
        calendarSection.style.display = 'none';
    });

    function renderFullCalendar(yearMonth) {
        if (!calendarGridBody || !weeklyBreakdownEl) return;

        const selectedObj = monthsList.find(m => m.key === yearMonth);
        if (selectedObj && monthLabelEl) {
            monthLabelEl.textContent = 'Calendar — ' + (selectedObj.full_label || selectedObj.label);
        }

        const [year, month] = yearMonth.split('-').map(Number);
        const firstDay = new Date(year, month - 1, 1);
        const lastDay = new Date(year, month, 0);
        const daysInMonth = lastDay.getDate();
        const startDayOfWeek = firstDay.getDay();

        const today = new Date();
        const todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        const monthEvents = eventsData.filter(e => e.event_date.startsWith(yearMonth));
        if (monthCountBadgeEl) {
            monthCountBadgeEl.textContent = monthEvents.length + ' event' + (monthEvents.length === 1 ? '' : 's');
        }

        const eventsByDate = {};
        monthEvents.forEach(e => {
            if (!eventsByDate[e.event_date]) eventsByDate[e.event_date] = [];
            eventsByDate[e.event_date].push(e);
        });

        // Build 42 grid cells
        let gridHtml = '';
        const totalCells = 42;
        for (let cell = 0; cell < totalCells; cell++) {
            const dayNum = cell - startDayOfWeek + 1;
            const isInMonth = dayNum >= 1 && dayNum <= daysInMonth;

            if (isInMonth) {
                const dateStr = yearMonth + '-' + String(dayNum).padStart(2, '0');
                const dayEvts = eventsByDate[dateStr] || [];
                const isToday = dateStr === todayStr;

                gridHtml += '<div class="calendar-day-cell ' + (isToday ? 'is-today' : '') + '">';
                gridHtml += '<div class="day-number">' + dayNum + '</div>';
                gridHtml += '<div class="day-events-list">';
                dayEvts.forEach(evt => {
                    gridHtml += '<div class="event-chip" data-event-id="' + evt.id + '">';
                    if (evt.section && evt.section !== 'All Sections') {
                        gridHtml += '<span class="event-chip-section">' + escapeHtml(evt.section) + '</span>';
                    }
                    gridHtml += escapeHtml(evt.title);
                    gridHtml += '</div>';
                });
                gridHtml += '</div></div>';
            } else {
                gridHtml += '<div class="calendar-day-cell is-empty"></div>';
            }
        }
        calendarGridBody.innerHTML = gridHtml;

        // Attach click handlers to event chips
        calendarGridBody.querySelectorAll('.event-chip').forEach(chip => {
            chip.addEventListener('click', function (e) {
                e.stopPropagation();
                const eventId = Number(chip.getAttribute('data-event-id'));
                const evtObj = eventsData.find(item => item.id === eventId);
                if (evtObj) openEventModal(evtObj);
            });
        });

        // Render Weekly Breakdown on Side Panel
        renderWeeklyBreakdown(year, month, daysInMonth, monthEvents);
    }

    function renderWeeklyBreakdown(year, month, daysInMonth, monthEvents) {
        if (monthEvents.length === 0) {
            weeklyBreakdownEl.innerHTML = '<div class="text-center text-muted py-15 font-size-11"><i class="fa fa-info-circle mb-3 d-block font-size-16 text-secondary"></i>No events scheduled for this month.</div>';
            return;
        }

        monthEvents.sort((a, b) => a.event_date.localeCompare(b.event_date));

        const weeks = {};
        monthEvents.forEach(evt => {
            const dayNum = Number(evt.event_date.split('-')[2]);
            const weekNum = Math.ceil(dayNum / 7);
            const weekKey = 'Week ' + weekNum;
            if (!weeks[weekKey]) weeks[weekKey] = [];
            weeks[weekKey].push(evt);
        });

        let html = '';
        Object.keys(weeks).forEach(wKey => {
            html += '<div class="week-card">';
            html += '<div class="week-card-header"><span>' + wKey + '</span> <span class="badge badge-secondary font-size-8">' + weeks[wKey].length + ' event(s)</span></div>';
            weeks[wKey].forEach(evt => {
                const d = new Date(evt.event_date + 'T00:00:00');
                const dayNum = d.getDate();
                const weekdayShort = d.toLocaleDateString('en-US', { weekday: 'short' });

                html += '<div class="week-event-row" style="cursor: pointer;" data-event-id="' + evt.id + '">';
                html += '<div class="week-event-date-pill"><strong>' + dayNum + '</strong><small>' + weekdayShort + '</small></div>';
                html += '<div class="week-event-details">';
                html += '<div class="week-event-title">' + escapeHtml(evt.title) + '</div>';
                html += '<div class="week-event-meta"><i class="fa fa-clock-o text-primary"></i> ' + escapeHtml(evt.event_time) + (evt.location ? ' | <i class="fa fa-map-marker text-danger"></i> ' + escapeHtml(evt.location) : '') + '</div>';
                if (evt.section && evt.section !== 'All Sections') {
                    html += '<span class="badge badge-info font-size-8 py-1 px-4 mt-2">' + escapeHtml(evt.section) + '</span>';
                }
                html += '</div></div>';
            });
            html += '</div>';
        });

        weeklyBreakdownEl.innerHTML = html;

        weeklyBreakdownEl.querySelectorAll('.week-event-row').forEach(row => {
            row.addEventListener('click', function () {
                const eventId = Number(row.getAttribute('data-event-id'));
                const evtObj = eventsData.find(item => item.id === eventId);
                if (evtObj) openEventModal(evtObj);
            });
        });
    }

    function openEventModal(evt) {
        document.getElementById('modalEventTitle').textContent = evt.title;
        document.getElementById('modalEventHeading').textContent = evt.title;
        document.getElementById('modalEventSection').textContent = evt.section || 'All Sections';
        document.getElementById('modalEventDate').textContent = evt.event_date;
        document.getElementById('modalEventTime').textContent = evt.event_time;

        const locWrap = document.getElementById('modalEventLocationWrap');
        if (evt.location) {
            locWrap.style.display = 'block';
            document.getElementById('modalEventLocation').textContent = evt.location;
        } else {
            locWrap.style.display = 'none';
        }

        document.getElementById('modalEventDescription').textContent = evt.description || 'No description provided.';

        const actionsEl = document.getElementById('modalEventActions');
        let actionsHtml = '';
        if (isAdmin) {
            actionsHtml += '<a href="' + evt.edit_url + '" class="btn btn-xs btn-info"><i class="fa fa-edit"></i> Edit</a>';
            actionsHtml += '<a href="' + evt.registrations_url + '" class="btn btn-xs btn-primary"><i class="fa fa-users"></i> Registrations</a>';
            actionsHtml += '<a href="' + evt.delete_url + '" class="btn btn-xs btn-danger" onclick="return confirm(\'Are you sure you want to delete this event?\')"><i class="fa fa-trash"></i> Delete</a>';
        }
        actionsHtml += '<button type="button" class="btn btn-xs btn-secondary" data-dismiss="modal">Close</button>';
        actionsEl.innerHTML = actionsHtml;

        $('#eventDetailModal').modal('show');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    if (monthSelect) {
        monthSelect.addEventListener('change', function () {
            renderFullCalendar(this.value);
        });
        renderFullCalendar(monthSelect.value);
    }
})();
</script>

@endsection
