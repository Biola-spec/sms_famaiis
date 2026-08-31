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

<script type="text/javascript">
$(document).ready(function(){
    var activeYearId = "{{ $activeYear->id ?? '' }}";

    function updateSubjectsAndStudents() {
        var class_id = $('#class_id').val();
        var section_id = $('#section_id').val();

        if(class_id) {
            // Get Subjects
            $.ajax({
                url: "{{ route('marks.getsubject') }}",
                type: "GET",
                data: {class_id: class_id, section_id: section_id},
                dataType: "json",
                success: function(data){
                    var d = '<option value="" selected disabled>Select Subject</option>';
                    $.each(data, function(key, value){
                        var subId = value.subject_id || value.id || (value.school_subject ? value.school_subject.id : '');
                        var subName = value.name || (value.school_subject ? value.school_subject.name : 'Subject #' + subId);
                        if (subId) {
                            d += '<option value="'+ subId +'">'+ subName +'</option>';
                        }
                    });
                    $('#subject_id').html(d);
                },
                error: function() {
                    $('#subject_id').html('<option value="" selected disabled>Select Subject</option>');
                }
            });

            // Get Students
            $.ajax({
                url: "{{ route('student.marks.getstudents') }}",
                type: "GET",
                data: {year_id: activeYearId, class_id: class_id, section_id: section_id},
                dataType: "json",
                success: function(data){
                    var d = '<option value="" selected>All Students in Class</option>';
                    $.each(data, function(key, value){
                        var stId = value.id || value.student_id || (value.student ? value.student.id : '');
                        var stName = value.name || (value.student ? value.student.name : '');
                        var stIdNo = value.id_no || (value.student ? value.student.id_no : '');
                        if (stId) {
                            d += '<option value="'+ stId +'">'+ stName + (stIdNo ? ' (' + stIdNo + ')' : '') +'</option>';
                        }
                    });
                    $('#student_id').html(d);
                },
                error: function() {
                    $('#student_id').html('<option value="" selected>All Students in Class</option>');
                }
            });
        }
    }

    $('#class_id').on('change', function(){
        var class_id = $(this).val();
        if(class_id) {
            // Update subjects & students immediately
            updateSubjectsAndStudents();

            // Load sections
            $.ajax({
                url: "{{ route('academic.marks.sections') }}",
                type: "GET",
                data: {class_id: class_id},
                dataType: "json",
                success: function(data){
                    var d = '<option value="" selected>All Sections</option>';
                    $.each(data, function(key, value){
                        d += '<option value="'+ value.id +'">'+ value.name +'</option>';
                    });
                    $('#section_id').html(d);
                }
            });
        }
    });

    $('#section_id').on('change', function(){
        updateSubjectsAndStudents();
    });

    // Automatically calculate End Date & End Time from Start Date, Start Time & Duration
    function calculateEndTime() {
        var startDateVal = $('#exam_date').val();
        var startTimeVal = $('#exam_time').val() || '00:00';
        var durationVal = parseInt($('#duration').val(), 10);

        if (!startDateVal || isNaN(durationVal) || durationVal <= 0) {
            return;
        }

        var startDateTime = new Date(startDateVal + 'T' + startTimeVal);
        if (isNaN(startDateTime.getTime())) {
            return;
        }

        var endDateTime = new Date(startDateTime.getTime() + (durationVal * 60000));
        var pad = function(n) { return n < 10 ? '0' + n : n; };

        var endY = endDateTime.getFullYear();
        var endM = pad(endDateTime.getMonth() + 1);
        var endD = pad(endDateTime.getDate());
        var endH = pad(endDateTime.getHours());
        var endMin = pad(endDateTime.getMinutes());

        $('#exam_end_date').val(endY + '-' + endM + '-' + endD);
        $('#exam_end_time').val(endH + ':' + endMin);
    }

    // Automatically calculate Duration if End Date & End Time are changed
    function calculateDurationFromEnd() {
        var startDateVal = $('#exam_date').val();
        var startTimeVal = $('#exam_time').val() || '00:00';
        var endDateVal = $('#exam_end_date').val();
        var endTimeVal = $('#exam_end_time').val() || '00:00';

        if (!startDateVal || !endDateVal) {
            return;
        }

        var startDateTime = new Date(startDateVal + 'T' + startTimeVal);
        var endDateTime = new Date(endDateVal + 'T' + endTimeVal);

        if (isNaN(startDateTime.getTime()) || isNaN(endDateTime.getTime())) {
            return;
        }

        var diffMs = endDateTime.getTime() - startDateTime.getTime();
        if (diffMs > 0) {
            var diffMins = Math.round(diffMs / 60000);
            $('#duration').val(diffMins);
        }
    }

    $('#exam_date, #exam_time, #duration').on('change input', function() {
        calculateEndTime();
    });

    $('#exam_end_date, #exam_end_time').on('change input', function() {
        calculateDurationFromEnd();
    });

    // Initial calculation if values present
    if ($('#exam_date').val()) {
        calculateEndTime();
    }
});
</script>
@endsection
