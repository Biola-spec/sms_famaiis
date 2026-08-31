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

<script type="text/javascript">
$(document).ready(function(){
    var activeYearId = "{{ $activeYear->id ?? '' }}";
    var selectedSectionId = "{{ $quiz->section_id }}";
    var selectedSubjectId = "{{ $quiz->subject_id }}";
    var selectedStudentId = "{{ $quiz->student_id }}";

    function updateSections(class_id, callback) {
        $.ajax({
            url: "{{ route('academic.marks.sections') }}",
            type: "GET",
            data: {class_id: class_id},
            dataType: "json",
            success: function(data){
                var d = '<option value="">All Sections</option>';
                $.each(data, function(key, value){
                    d += '<option value="'+ value.id +'" '+ (value.id == selectedSectionId ? 'selected' : '') +'>'+ value.name +'</option>';
                });
                $('#section_id').html(d);
                if(callback) callback();
            }
        });
    }

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
                    var d = '<option value="" disabled>Select Subject</option>';
                    $.each(data, function(key, value){
                        var subId = value.subject_id || value.id || (value.school_subject ? value.school_subject.id : '');
                        var subName = value.name || (value.school_subject ? value.school_subject.name : 'Subject #' + subId);
                        if (subId) {
                            d += '<option value="'+ subId +'" '+ (subId == selectedSubjectId ? 'selected' : '') +'>'+ subName +'</option>';
                        }
                    });
                    $('#subject_id').html(d);
                }
            });

            // Get Students
            $.ajax({
                url: "{{ route('student.marks.getstudents') }}",
                type: "GET",
                data: {year_id: activeYearId, class_id: class_id, section_id: section_id},
                dataType: "json",
                success: function(data){
                    var d = '<option value="" ' + (!selectedStudentId ? 'selected' : '') + '>All Students in Class</option>';
                    $.each(data, function(key, value){
                        var stId = value.id || value.student_id || (value.student ? value.student.id : '');
                        var stName = value.name || (value.student ? value.student.name : '');
                        var stIdNo = value.id_no || (value.student ? value.student.id_no : '');
                        if (stId) {
                            d += '<option value="'+ stId +'" '+ (stId == selectedStudentId ? 'selected' : '') +'>'+ stName + (stIdNo ? ' (' + stIdNo + ')' : '') +'</option>';
                        }
                    });
                    $('#student_id').html(d);
                }
            });
        }
    }

    // Initial Load
    var initialClassId = $('#class_id').val();
    if(initialClassId) {
        updateSections(initialClassId, updateSubjectsAndStudents);
        updateSubjectsAndStudents();
    }

    $('#class_id').on('change', function(){
        selectedSectionId = "";
        selectedSubjectId = "";
        selectedStudentId = "";
        updateSubjectsAndStudents();
        updateSections($(this).val(), updateSubjectsAndStudents);
    });

    $('#section_id').on('change', function(){
        updateSubjectsAndStudents();
    });

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

    if ($('#exam_date').val() && !$('#exam_end_date').val()) {
        calculateEndTime();
    }
});
</script>
@endsection
