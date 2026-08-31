@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Objective Quizzes (CBT)</h3>
                            <a href="{{ route('academic.cbt.create') }}" class="btn btn-primary float-right">Create New Quiz</a>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            @if(Auth::user()->role == 'Admin' || Auth::user()->hasRole('Admin'))
                                                <th>Teacher</th>
                                            @endif
                                            <th>Class</th>
                                            <th>Subject</th>
                                            <th>Questions</th>
                                            <th>Term</th>
                                            <th>Duration</th>
                                            <th>Schedule</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($quizzes as $quiz)
                                        <tr>
                                            <td>{{ $quiz->title }}</td>
                                            @if(Auth::user()->role == 'Admin' || Auth::user()->hasRole('Admin'))
                                                <td>{{ $quiz->creator->name ?? 'System' }}</td>
                                            @endif
                                            <td>{{ $quiz->student_class->name }}</td>
                                            <td>{{ $quiz->subject->name }}</td>
                                            <td><span class="badge badge-pill badge-primary">{{ $quiz->questions_count }} Uploaded</span></td>
                                            <td>{{ $quiz->term }}</td>
                                            <td>{{ $quiz->duration }} mins</td>
                                            <td>
                                                @if($quiz->starts_at)
                                                    {{ $quiz->starts_at->format('M d, Y H:i') }}
                                                    @if($quiz->ends_at)
                                                        <br><small>Ends {{ $quiz->ends_at->format('H:i') }}</small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">Unscheduled</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $quiz->status == 'published' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($quiz->status) }}
                                                </span>
                                            </td>
                                            <td width="15%">
                                                <a href="{{ route('academic.cbt.show', $quiz->id) }}" class="btn btn-info btn-sm" title="Manage Questions"><i class="fa fa-list"></i></a>
                                                <a href="{{ route('academic.cbt.edit', $quiz->id) }}" class="btn btn-warning btn-sm" title="Edit Quiz Setup"><i class="fa fa-edit"></i></a>
                                                
                                                <form method="POST" action="{{ route('academic.cbt.destroy', $quiz->id) }}" style="display:inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this quiz and all questions?')" title="Delete"><i class="fa fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $quizzes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
