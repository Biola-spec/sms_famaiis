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
    $eventsPayload = $calendarEvents->map(function ($event) {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description ?: '',
            'event_date' => \Carbon\Carbon::parse($event->event_date)->format('Y-m-d'),
            'event_time' => $event->event_time ? \Carbon\Carbon::parse($event->event_time)->format('h:i A') : 'All day',
            'location' => $event->location ?: '',
            'section' => optional($event->section)->name ?: 'All Sections',
        ];
    })->values();

    $timetable = collect($timetable_entries ?? []);
    $timetableGroups = $timetable->groupBy(fn ($entry) => optional($entry->section)->name ?: 'All Sections');
    $teacherTimetable = collect($teacher_timetable_entries ?? []);
    $isTeacher = Auth::user()->role === 'Teacher' || Auth::user()->role === 'Staff' || Auth::user()->hasRole('Teacher') || Auth::user()->hasRole('Staff');
@endphp

<div class="col-12">
    <div class="box school-schedule-widget">
        <div class="box-header with-border d-flex align-items-center justify-content-between flex-wrap py-10">
            <div>
                <h4 class="box-title mb-0 font-size-15 font-weight-700">School Calendar & Timetable</h4>
                <small class="subtitle font-size-11">Events and subject periods published by the Admin</small>
            </div>
            @if(Auth::user()->hasRole('Admin'))
                <div class="d-flex gap-2">
                    <a href="{{ route('event.view') }}" class="btn btn-xs btn-info-light font-size-10 px-6 py-2"><i class="fa fa-calendar"></i> Manage Calendar</a>
                    <a href="{{ route('timetable.index') }}" class="btn btn-xs btn-primary font-size-10 px-6 py-2"><i class="fa fa-clock-o"></i> Manage Timetable</a>
                </div>
            @endif
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-xl-4 col-12 mb-20 mb-xl-0">
                    <div class="d-flex align-items-center justify-content-between mb-8 flex-wrap gap-2">
                        <h6 class="schedule-calendar-title mb-0 font-size-13 font-weight-700">School Calendar</h6>
                        
                        <div class="month-select-pill">
                            <select class="month-select-input" id="schedule-widget-month-select">
                                @foreach($monthsList as $m)
                                    <option value="{{ $m['key'] }}" {{ $m['key'] === $currentMonthKey ? 'selected' : '' }}>
                                        {{ $m['label'] }}{{ $m['is_current'] ? ' • Current' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="event-calendar-grid" id="schedule-widget-grid">
                        <!-- JS populated -->
                    </div>

                    <div class="schedule-event-list mt-10" id="schedule-widget-event-list">
                        <!-- JS populated events for selected month -->
                    </div>
                </div>

                <div class="col-xl-8 col-12">
                    @if($isTeacher)
                        <div class="teacher-schedule-panel mb-15 p-10">
                            <div class="teacher-schedule-heading mb-5">
                                <div><span class="modal-eyebrow">MY TEACHING SCHEDULE</span><h6 class="mb-0 font-size-12 font-weight-700">Classes I Am Taking</h6></div>
                                <span class="teacher-period-count font-size-10 font-weight-700">{{ $teacherTimetable->count() }} periods</span>
                            </div>
                            <div class="table-responsive schedule-table-wrap">
                                <table class="table table-bordered table-sm mb-0 schedule-modal-table font-size-11">
                                    <thead>
                                        <tr>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Section / Class</th>
                                            <th>Subject</th>
                                            <th>Room</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($teacherTimetable as $entry)
                                            <tr>
                                                <td><strong>{{ $entry->day_of_week }}</strong></td>
                                                <td>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                                                <td>{{ optional($entry->section)->name ?: 'All sections' }} / {{ optional($entry->studentClass)->name }}</td>
                                                <td>{{ optional($entry->subject)->name }}</td>
                                                <td>{{ $entry->room ?: '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">No teaching periods have been assigned to you yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <h6 class="all-sections-heading font-size-12 font-weight-700 mb-8">View Section Timetables</h6>
                    @forelse($timetableGroups as $sectionName => $sectionEntries)
                        <button type="button" class="section-timetable-button py-8 px-10 mb-6" data-timetable-modal="timetable-modal-{{ $loop->index }}">
                            <span class="section-timetable-icon"><i class="fa fa-calendar"></i></span>
                            <span><strong>{{ $sectionName }}</strong><small>{{ $sectionEntries->count() }} subject period(s)</small></span>
                            <i class="fa fa-chevron-right section-timetable-arrow"></i>
                        </button>
                    @empty
                        <div class="text-center text-muted p-15 font-size-11">The Admin has not published a timetable yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($timetableGroups as $sectionName => $sectionEntries)
    <div class="school-timetable-modal" id="timetable-modal-{{ $loop->index }}" aria-hidden="true">
        <div class="school-timetable-modal-backdrop" data-timetable-close></div>
        <div class="school-timetable-dialog" role="dialog" aria-modal="true" aria-labelledby="timetable-title-{{ $loop->index }}">
            <div class="school-timetable-modal-header">
                <div><span class="modal-eyebrow">WEEKLY SCHEDULE</span><h4 id="timetable-title-{{ $loop->index }}">{{ $sectionName }} Timetable</h4></div>
                <button type="button" class="school-timetable-close" data-timetable-close aria-label="Close timetable"><i class="fa fa-times"></i></button>
            </div>
            <div class="school-timetable-modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm mb-0 schedule-modal-table font-size-11">
                        <thead><tr><th>Day</th><th>Time</th><th>Class</th><th>Subject</th><th>Teacher</th><th>Room</th></tr></thead>
                        <tbody>
                            @foreach($sectionEntries as $entry)
                                <tr>
                                    <td><strong>{{ $entry->day_of_week }}</strong></td>
                                    <td>{{ \Carbon\Carbon::parse($entry->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($entry->end_time)->format('H:i') }}</td>
                                    <td>{{ optional($entry->studentClass)->name }}</td>
                                    <td>{{ optional($entry->subject)->name }}</td>
                                    <td>{{ optional($entry->teacher)->name ?: 'Unassigned' }}</td>
                                    <td>{{ $entry->room ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endforeach

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

    .month-select-input {
        border: 0;
        background: transparent;
        font-size: 11px;
        font-weight: 700;
        color: #0f172a;
        outline: none;
        cursor: pointer;
        height: 20px;
        padding: 0;
    }

    .school-schedule-widget .event-calendar-grid { display:grid; grid-template-columns:repeat(7,minmax(0,1fr)); gap:4px; }
    .school-schedule-widget .event-calendar-weekday { color:#64748b; font-size:10px; font-weight:700; text-align:center; padding:3px 0; text-transform:uppercase; }
    .school-schedule-widget .event-calendar-day { min-height:28px; border:1px solid #e5e7eb; border-radius:4px; padding:3px; font-size:10px; text-align:center; position:relative; background:#f8fafc; color:#334155; }
    .school-schedule-widget .event-calendar-day.is-empty { border-color:transparent; background:transparent; }
    .school-schedule-widget .event-calendar-day.has-event { background:#1e40af; color:#ffffff; font-weight:700; cursor:pointer; }
    .school-schedule-widget .event-calendar-day.has-event::after { content:""; position:absolute; bottom:2px; left:50%; transform:translateX(-50%); width:10px; height:2px; background:#60a5fa; border-radius:2px; }
    .school-schedule-widget .event-calendar-day.is-today { border:2px solid #2563eb; font-weight:800; }
    .schedule-calendar-title { font-size:13px; font-weight:700; color:#1e293b; }
    .section-timetable-button { width:100%; display:flex; align-items:center; gap:8px; border:1px solid #dbe5f0; border-radius:6px; background:#fff; padding:8px 10px; margin-bottom:6px; text-align:left; color:#1e2e4a; transition:transform .15s ease, box-shadow .15s ease, border-color .15s ease; cursor:pointer; }
    .section-timetable-button:hover { transform:translateY(-1px); border-color:#2e86de; box-shadow:0 4px 10px rgba(46,134,222,.12); }
    .section-timetable-button strong, .section-timetable-button small { display:block; }
    .section-timetable-button strong { font-size:11px; }
    .section-timetable-button small { color:#64748b; font-size:9px; margin-top:1px; }
    .section-timetable-icon { width:26px; height:26px; display:grid; place-items:center; border-radius:4px; color:#2563eb; background:#e8f2fc; font-size:11px; }
    .section-timetable-arrow { margin-left:auto; color:#94a3b8; font-size:10px; }
    .school-timetable-modal { display:none; position:fixed; inset:0; z-index:2000; align-items:center; justify-content:center; padding:20px; }
    .school-timetable-modal.is-open { display:flex; }
    .school-timetable-modal-backdrop { position:absolute; inset:0; background:rgba(8,15,30,.72); backdrop-filter:blur(3px); }
    .school-timetable-dialog { position:relative; z-index:1; width:min(900px, 100%); max-height:min(640px, 90vh); overflow:hidden; border-radius:8px; background:#fff; box-shadow:0 20px 60px rgba(0,0,0,.28); }
    .school-timetable-modal-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; background:#132a46; color:#fff; }
    .school-timetable-modal-header h4 { margin:2px 0 0; color:#fff; font-size:16px; }
    .modal-eyebrow { font-size:9px; letter-spacing:1.2px; color:#9bc7f4; }
    .school-timetable-close { border:0; width:28px; height:28px; border-radius:50%; background:rgba(255,255,255,.12); color:#fff; cursor:pointer; }
    .school-timetable-close:hover { background:#e66767; }
    .school-timetable-modal-body { max-height:calc(min(640px, 90vh) - 76px); overflow:auto; padding:14px 18px; }
    .schedule-modal-table th { font-size:10px; white-space:nowrap; background:#f1f6fb; }
    .schedule-modal-table td { font-size:10px; padding:5px 6px; vertical-align:middle; }
    .teacher-schedule-panel { margin-bottom:12px; padding:10px; border:1px solid #dbe5f0; border-radius:6px; background:#f8fbff; }
    .teacher-schedule-heading { display:flex; align-items:center; justify-content:space-between; margin-bottom:5px; }
    .teacher-schedule-heading h6, .all-sections-heading { margin:2px 0 6px; font-size:11px; font-weight:700; }
    .teacher-period-count { color:#2563eb; font-size:9px; font-weight:700; }
    .all-sections-heading { color:#334155; }
    body.dark-skin .section-timetable-button { background:#172131; border-color:#334155; color:#e5e7eb; }
    body.dark-skin .teacher-schedule-panel { background:#172131; border-color:#334155; }
    body.dark-skin .all-sections-heading, body.dark-skin .schedule-calendar-title { color:#e5e7eb; }
    body.dark-skin .school-timetable-dialog { background:#111827; }
    body.dark-skin .schedule-modal-table th { background:#1f2937; color:#e5e7eb; }
    body.dark-skin .schedule-modal-table td { color:#d1d5db; }
    .schedule-event-row { display:flex; gap:8px; padding:6px; border:1px solid #e2e8f0; border-radius:5px; margin-bottom:5px; background:#ffffff; font-size:11px; align-items:center; }
    .schedule-event-row strong { min-width:44px; color:#1e40af; font-weight:700; background:#dbeafe; padding:3px 5px; border-radius:3px; text-align:center; font-size:10px; }
    .schedule-event-row span { flex:1; color:#1e293b; font-weight:600; }
    .schedule-event-row small { display:block; color:#64748b; font-weight:400; font-size:10px; margin-top:1px; }
    body.dark-skin .schedule-event-row { background:#1e293b; border-color:#334155; }
    body.dark-skin .schedule-event-row span { color:#f1f5f9; }
    body.dark-skin .schedule-event-row strong { background:#1e3a8a; color:#93c5fd; }
    .schedule-table-wrap { max-height:280px; overflow:auto; }
    .schedule-table-wrap th { white-space:nowrap; font-size:9px; }
    .schedule-table-wrap td { font-size:9px; vertical-align:middle; padding:4px 5px; }
    body.dark-skin .school-schedule-widget .event-calendar-day { background:#1e293b; border-color:#334155; color:#cbd5e1; }
    body.dark-skin .school-schedule-widget .event-calendar-day.has-event { background:#2563eb; color:#ffffff; }
    .gap-2 { gap:6px; }
    body.timetable-modal-open { overflow:hidden; }
</style>

<script>
    (function () {
        const eventsData = @json($eventsPayload);
        const gridEl = document.getElementById('schedule-widget-grid');
        const listEl = document.getElementById('schedule-widget-event-list');
        const selectEl = document.getElementById('schedule-widget-month-select');

        const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        function renderCalendar(yearMonth) {
            if (!gridEl || !listEl) return;

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

            // Render Grid
            let html = '';
            weekdays.forEach(wd => {
                html += '<div class="event-calendar-weekday">' + wd + '</div>';
            });

            const totalCells = 42;
            for (let cell = 0; cell < totalCells; cell++) {
                const dayNum = cell - startDayOfWeek + 1;
                const isInMonth = dayNum >= 1 && dayNum <= daysInMonth;
                if (isInMonth) {
                    const dateStr = yearMonth + '-' + String(dayNum).padStart(2, '0');
                    const dayEvts = eventsByDate[dateStr] || [];
                    const hasEvent = dayEvts.length > 0;
                    const isToday = dateStr === todayStr;
                    const titleText = dayEvts.map(e => e.title).join(', ');

                    html += '<div class="event-calendar-day ' + (hasEvent ? 'has-event' : '') + ' ' + (isToday ? 'is-today' : '') + '" title="' + (titleText || '') + '"><span>' + dayNum + '</span></div>';
                } else {
                    html += '<div class="event-calendar-day is-empty"></div>';
                }
            }
            gridEl.innerHTML = html;

            // Render Events List for Selected Month
            if (monthEvents.length === 0) {
                listEl.innerHTML = '<div class="text-muted text-center p-10 font-size-11">No events scheduled for this month.</div>';
            } else {
                let listHtml = '';
                monthEvents.sort((a, b) => a.event_date.localeCompare(b.event_date));
                monthEvents.forEach(e => {
                    const d = new Date(e.event_date + 'T00:00:00');
                    const dayFormatted = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                    listHtml += '<div class="schedule-event-row">';
                    listHtml += '<strong>' + dayFormatted + '</strong>';
                    listHtml += '<span>' + escapeHtml(e.title) + ' <small><i class="fa fa-clock-o"></i> ' + escapeHtml(e.event_time) + (e.location ? ' | <i class="fa fa-map-marker"></i> ' + escapeHtml(e.location) : '') + (e.section && e.section !== 'All Sections' ? ' (' + escapeHtml(e.section) + ')' : '') + '</small></span>';
                    listHtml += '</div>';
                });
                listEl.innerHTML = listHtml;
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        if (selectEl) {
            selectEl.addEventListener('change', function () {
                renderCalendar(this.value);
            });
            renderCalendar(selectEl.value);
        }

        // Modal scripts
        const openModal = function (id) { const modal = document.getElementById(id); if (modal) { modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false'); document.body.classList.add('timetable-modal-open'); } };
        const closeModal = function (modal) { if (modal) { modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('timetable-modal-open'); } };
        document.querySelectorAll('[data-timetable-modal]').forEach(function (button) { button.addEventListener('click', function () { openModal(button.dataset.timetableModal); }); });
        document.querySelectorAll('[data-timetable-close]').forEach(function (button) { button.addEventListener('click', function () { closeModal(button.closest('.school-timetable-modal')); }); });
        document.addEventListener('keydown', function (event) { if (event.key === 'Escape') document.querySelectorAll('.school-timetable-modal.is-open').forEach(closeModal); });
    })();
</script>
