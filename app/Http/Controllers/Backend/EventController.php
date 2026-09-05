<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\SchoolSection;
use App\Models\User;
use App\Notifications\EventNotification;
use Illuminate\Support\Facades\Notification;
use Auth;

class EventController extends Controller
{
    public function ViewEvent()
    {
        $user = Auth::user();
        $scheduleData = app(\App\Services\SchoolScheduleService::class)->dashboardData($user);
        $data['allData'] = Event::with('section')->orderBy('event_date', 'asc')->get();
        return view('backend.event.view_event', array_merge($data, $scheduleData));
    }

    public function AddEvent()
    {
        $data['sections'] = SchoolSection::all();
        return view('backend.event.add_event', $data);
    }

    public function StoreEvent(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'event_date' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:20480', // 20MB limit
        ]);

        $event = new Event();
        $event->title = $request->title;
        $event->description = $request->description;
        $event->event_date = $request->event_date;
        $event->event_time = $request->event_time;
        $event->location = $request->location;
        $event->cta_text = $request->cta_text;
        $event->reminder_at = $request->reminder_at;
        $event->section_id = $request->section_id;

        if ($request->file('image')) {
            $file = $request->file('image');
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('upload/event_images'), $filename);
            $event->image = 'upload/event_images/' . $filename;
        }

        $event->save();

        if ($request->notify_all) {
            $this->NotifyAllUsers($event);
        }

        $notification = [
            'message' => 'Event Added Successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route('event.view')->with($notification);
    }

    public function EditEvent($id)
    {
        $data['editData'] = Event::findOrFail($id);
        $data['sections'] = SchoolSection::all();
        return view('backend.event.edit_event', $data);
    }

    public function UpdateEvent(Request $request, $id)
    {
        $request->validate([
            'title' => 'required',
            'event_date' => 'required|date',
            'image' => 'nullable|image|mimes:jpg,png,jpeg,gif,webp|max:20480', // 20MB limit
        ]);

        $event = Event::findOrFail($id);
        $event->title = $request->title;
        $event->description = $request->description;
        $event->event_date = $request->event_date;
        $event->event_time = $request->event_time;
        $event->location = $request->location;
        $event->cta_text = $request->cta_text;
        $event->reminder_at = $request->reminder_at;
        $event->section_id = $request->section_id;

        if ($request->file('image')) {
            $file = $request->file('image');
            @unlink(public_path($event->image));
            $filename = date('YmdHi') . $file->getClientOriginalName();
            $file->move(public_path('upload/event_images'), $filename);
            $event->image = 'upload/event_images/' . $filename;
        }

        $event->save();

        if ($request->notify_all) {
            $this->NotifyAllUsers($event);
        }

        $notification = [
            'message' => 'Event Updated Successfully',
            'alert-type' => 'success'
        ];

        return redirect()->route('event.view')->with($notification);
    }

    public function DeleteEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();

        $notification = [
            'message' => 'Event Deleted Successfully',
            'alert-type' => 'info'
        ];

        return redirect()->route('event.view')->with($notification);
    }

    public function NotifyAllUsers($event)
    {
        $query = User::query();
        
        if ($event->section_id) {
            // If section specific, notify users (Students/Teachers/Parents) associated with that section
            $query->where(function($q) use ($event) {
                // Students in section
                $q->whereHas('sections', function($sq) use ($event) {
                    $sq->where('section_id', $event->section_id);
                })
                // Teachers in section
                ->orWhereHas('teacherSections', function($tq) use ($event) {
                    $tq->where('section_id', $event->section_id);
                })
                // Parents of students in section
                ->orWhereHas('children', function($cq) use ($event) {
                    $cq->whereHas('sections', function($sq) use ($event) {
                        $sq->where('section_id', $event->section_id);
                    });
                })
                // Always include Admins
                ->orWhereIn('role', ['Admin']);
            });
        }

        $users = $query->get();

        Notification::send($users, new EventNotification($event));
        
        $event->is_notified = true;
        $event->save();
    }

    public function ViewRegistrations($event_id)
    {
        $data['event'] = Event::findOrFail($event_id);
        $data['registrations'] = \App\Models\EventRegistration::where('event_id', $event_id)->latest()->get();
        return view('backend.event.view_registrations', $data);
    }
}
