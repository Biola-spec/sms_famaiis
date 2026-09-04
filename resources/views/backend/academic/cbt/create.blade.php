@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-12">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Create Objective Quiz</h3>
                        </div>
                        <div class="box-body">
                            <form method="post" action="{{ route('academic.cbt.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Quiz Title <span class="text-danger">*</span></label>
                                            <input type="text" name="title" class="form-control" required value="{{ old('title') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Duration (Minutes) <span class="text-danger">*</span></label>
                                            <input type="number" name="duration" id="duration" class="form-control" required min="1" value="{{ old('duration', 45) }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Retake Limit <span class="text-danger">*</span></label>
                                            <input type="number" name="retake_limit" class="form-control" required min="1" value="{{ old('retake_limit', 1) }}">
                                            <small class="text-muted">Allowed student attempts.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Start Date</label>
                                            <input type="date" name="exam_date" id="exam_date" class="form-control" value="{{ old('exam_date') }}">
                                            <small class="text-muted">Leave blank for unscheduled quiz.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Start Time</label>
                                            <input type="time" name="exam_time" id="exam_time" class="form-control" value="{{ old('exam_time', '09:00') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>End Date</label>
                                            <input type="date" name="exam_end_date" id="exam_end_date" class="form-control" value="{{ old('exam_end_date') }}">
                                            <small class="text-muted">Auto-calculated from duration or manual.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>End Time</label>
                                            <input type="time" name="exam_end_time" id="exam_end_time" class="form-control" value="{{ old('exam_end_time') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Class <span class="text-danger">*</span></label>
                                            <select name="class_id" id="class_id" class="form-control" required>
                                                <option value="" selected disabled>Select Class</option>
                                                @foreach($classes as $class)
                                                    <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>{{ $class->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Section <span class="text-info">(Optional)</span></label>
                                            <select name="section_id" id="section_id" class="form-control">
                                                <option value="" selected>All Sections</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Subject <span class="text-danger">*</span></label>
                                            <select name="subject_id" id="subject_id" class="form-control" required>
                                                <option value="" selected disabled>Select Subject</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Term <span class="text-danger">*</span></label>
                                            <select name="term" class="form-control" required>
                                                <option value="" selected disabled>Select Term</option>
                                                @foreach($terms as $term)
                                                    <option value="{{ $term }}" {{ old('term', '1st Term') == $term ? 'selected' : '' }}>{{ $term }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Assign to Particular Student <span class="text-info">(Optional - Leave blank for whole class)</span></label>
                                            <select name="student_id" id="student_id" class="form-control">
                                                <option value="" selected>All Students in Class</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="text-xs-right mt-3">
                                    <button type="submit" class="btn btn-info btn-rounded">Create Quiz</button>
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
@endsection

@include('backend.academic.cbt._form_scripts')
