<?php

namespace App\Services;

use App\Models\Event;
use App\Models\SchoolTimetable;
use App\Models\User;
use Carbon\Carbon;

class SchoolScheduleService
{
    public function dashboardData(?User $user = null): array
    {
        $timetableQuery = SchoolTimetable::with(['section', 'studentClass', 'subject', 'teacher'])
            ->where('is_active', true);
        $teacherTimetable = collect();

        if ($user && !$this->isAdmin($user)) {
            $sectionIds = $this->sectionIdsFor($user);
            $timetableQuery->where(function ($query) use ($sectionIds) {
                $query->whereNull('section_id');
                if ($sectionIds->isNotEmpty()) {
                    $query->orWhereIn('section_id', $sectionIds);
                }
            });

            if ($this->isTeacher($user)) {
                $teacherTimetable = SchoolTimetable::with(['section', 'studentClass', 'subject'])
                    ->where('is_active', true)
                    ->where('teacher_id', $user->id)
                    ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
                    ->orderBy('start_time')->get();
            }
        }

        $now = Carbon::now();
        $currentMonthKey = $now->format('Y-m');

        // Build list of months (past 2 months to next 9 months, plus any months with events)
        $monthsMap = collect();
        for ($i = -2; $i <= 9; $i++) {
            $dt = $now->copy()->addMonths($i);
            $key = $dt->format('Y-m');
            $monthsMap->put($key, [
                'key' => $key,
                'label' => $dt->format('F Y'),
                'is_current' => $key === $currentMonthKey,
                'year' => (int) $dt->format('Y'),
                'month' => (int) $dt->format('m'),
            ]);
        }

        $eventMonths = Event::selectRaw("DATE_FORMAT(event_date, '%Y-%m') as month_key, DATE_FORMAT(event_date, '%M %Y') as month_label, YEAR(event_date) as year_num, MONTH(event_date) as month_num")
            ->groupBy('month_key', 'month_label', 'year_num', 'month_num')
            ->orderBy('month_key')
            ->get();

        foreach ($eventMonths as $em) {
            if ($em->month_key && !$monthsMap->has($em->month_key)) {
                $monthsMap->put($em->month_key, [
                    'key' => $em->month_key,
                    'label' => $em->month_label,
                    'is_current' => $em->month_key === $currentMonthKey,
                    'year' => (int) $em->year_num,
                    'month' => (int) $em->month_num,
                ]);
            }
        }

        $monthsList = $monthsMap->sortBy('key')->values()->all();

        $startDate = $now->copy()->subMonths(3)->startOfMonth();
        $endDate = $now->copy()->addMonths(9)->endOfMonth();

        return [
            'upcoming_events' => Event::with('section')
                ->where('event_date', '>=', Carbon::today())
                ->orderBy('event_date')->orderBy('event_time')
                ->limit(8)->get(),
            'calendar_events' => Event::with('section')
                ->whereBetween('event_date', [$startDate, $endDate])
                ->orderBy('event_date')->orderBy('event_time')->get(),
            'months_list' => $monthsList,
            'current_month_key' => $currentMonthKey,
            'timetable_entries' => $timetableQuery
                ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
                ->orderBy('start_time')->get(),
            'teacher_timetable_entries' => $teacherTimetable,
        ];
    }

    private function isAdmin(User $user): bool
    {
        return $user->role === 'Admin' || $user->hasRole('Admin') || $user->hasRole('Super Admin');
    }

    private function isTeacher(User $user): bool
    {
        return $user->role === 'Teacher' || $user->role === 'Staff' || $user->hasRole('Teacher') || $user->hasRole('Staff');
    }

    private function sectionIdsFor(User $user)
    {
        $ids = collect();

        if ($this->isTeacher($user)) {
            $ids = $ids->merge($user->teacherSections()->wherePivot('is_active', true)->pluck('school_sections.id'))
                ->merge($user->teacherAssignments()->whereNotNull('section_id')->pluck('section_id'));
        } elseif ($user->hasRole('Student') || strtolower((string) $user->usertype) === 'student') {
            $ids = $user->activeSections()->pluck('school_sections.id');
            if ($user->section_id) {
                $ids->push($user->section_id);
            }
        } elseif ($user->hasRole('Parent') || strtolower((string) $user->usertype) === 'parent') {
            $ids = $user->children()->with('activeSections')->get()
                ->flatMap(fn ($child) => $child->activeSections->pluck('id'));
        }

        return $ids->filter()->unique()->values();
    }
}
