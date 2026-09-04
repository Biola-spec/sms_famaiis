@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Edit Quiz Setup</h3>
                        </div>
                        <div class="box-body">
                            <form method="post" action="{{ route('academic.cbt.update', $quiz->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Quiz Title <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" required value="{{ old('title', $quiz->title) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Duration (Minutes) <span class="text-danger">*</span></label>
                                            <input type="number" name="duration" id="duration" class="form-control" required min="1" value="{{ old('duration', $quiz->duration) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Retake Limit <span class="text-danger">*</span></label>
                                            <input type="number" name="retake_limit" class="form-control" required min="1" value="{{ old('retake_limit', $quiz->retake_limit) }}">
                                            <small class="text-muted">Attempts allowed.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Start Date</label>
                                            <input type="date" name="exam_date" id="exam_date" class="form-control" value="{{ old('exam_date', optional($quiz->starts_at)->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Start Time</label>
                                            <input type="time" name="exam_time" id="exam_time" class="form-control" value="{{ old('exam_time', optional($quiz->starts_at)->format('H:i')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>End Date</label>
                                            <input type="date" name="exam_end_date" id="exam_end_date" class="form-control" value="{{ old('exam_end_date', optional($quiz->examEndTime())->format('Y-m-d')) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>End Time</label>
                                            <input type="time" name="exam_end_time" id="exam_end_time" class="form-control" value="{{ old('exam_end_time', optional($quiz->examEndTime())->format('H:i')) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Class <span class="text-danger">*</span></label>
                                            <select name="class_id" id="class_id" class="form-control" required>
                                                <option value="" disabled>Select Class</option>
                                                @foreach($classes as $class)
                                                    <option value="{{ $class->id }}" {{ $quiz->class_id == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Section <span class="text-info">(Optional)</span></label>
                                            <select name="section_id" id="section_id" class="form-control">
                                                <option value="">All Sections</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Subject <span class="text-danger">*</span></label>
                                            <select name="subject_id" id="subject_id" class="form-control" required>
                                                <option value="" disabled>Select Subject</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Term <span class="text-danger">*</span></label>
                                            <select name="term" class="form-control" required>
                                                @foreach($terms as $term)
                                                    <option value="{{ $term }}" {{ $quiz->term == $term ? 'selected' : '' }}>{{ $term }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Assign to Particular Student <span class="text-info">(Optional - Leave blank for whole class)</span></label>
                                            <select name="student_id" id="student_id" class="form-control">
                                                <option value="">All Students in Class</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs-right mt-3">
                                    <button type="submit" class="btn btn-info btn-rounded">Update Quiz Setup</button>
                                    <a href="{{ route('academic.cbt.index') }}" class="btn btn-secondary btn-rounded float-right">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@include('backend.academic.cbt._form_scripts')
@endsection
