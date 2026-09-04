@push('scripts')
<script type="text/javascript">
$(function () {
    var optionsUrl = @json(route('academic.cbt.options'));
    var activeYearId = @json($activeYear->id ?? '');
    var selectedSectionId = @json(isset($quiz) ? (string) ($quiz->section_id ?? '') : (string) old('section_id', ''));
    var selectedSubjectId = @json(isset($quiz) ? (string) ($quiz->subject_id ?? '') : (string) old('subject_id', ''));
    var selectedStudentId = @json(isset($quiz) ? (string) ($quiz->student_id ?? '') : (string) old('student_id', ''));

    function esc(value) {
        return $('<div>').text(value == null ? '' : value).html();
    }

    function loadCbtOptions(reloadSections) {
        var classId = $('#class_id').val();
        if (!classId) {
            return;
        }

        var sectionId = $('#section_id').val() || selectedSectionId || '';

        $.ajax({
            url: optionsUrl,
            type: 'GET',
            data: {
                class_id: classId,
                section_id: sectionId || '',
                year_id: activeYearId
            },
            dataType: 'json'
        }).done(function (data) {
            if (reloadSections) {
                var sectionHtml = '<option value="">All Sections</option>';
                $.each(data.sections || [], function (_, value) {
                    sectionHtml += '<option value="' + value.id + '">' + esc(value.name) + '</option>';
                });
                $('#section_id').html(sectionHtml);
                if (selectedSectionId) {
                    $('#section_id').val(String(selectedSectionId));
                }
            }

            var subjectHtml = '<option value="" disabled>Select Subject</option>';
            $.each(data.subjects || [], function (_, value) {
                var id = value.id || value.subject_id;
                if (!id) {
                    return;
                }
                subjectHtml += '<option value="' + id + '">' + esc(value.name) + '</option>';
            });
            $('#subject_id').html(subjectHtml);
            if (selectedSubjectId) {
                $('#subject_id').val(String(selectedSubjectId));
            } else {
                $('#subject_id').val('');
            }

            var studentHtml = '<option value="">All Students in Class</option>';
            $.each(data.students || [], function (_, value) {
                var id = value.id || value.student_id;
                if (!id) {
                    return;
                }
                var label = (value.name || '') + (value.id_no ? ' (' + value.id_no + ')' : '');
                studentHtml += '<option value="' + id + '">' + esc(label) + '</option>';
            });
            $('#student_id').html(studentHtml);
            if (selectedStudentId) {
                $('#student_id').val(String(selectedStudentId));
            }
        }).fail(function (xhr) {
            if (reloadSections) {
                $('#section_id').html('<option value="">All Sections</option>');
            }
            $('#subject_id').html('<option value="" disabled>Select Subject</option>');
            $('#student_id').html('<option value="">All Students in Class</option>');
            if (window.toastr) {
                toastr.error(xhr.status === 403 ? 'You cannot load options for this class.' : 'Could not load class options.');
            }
        });
    }

    $('#class_id').on('change', function () {
        selectedSectionId = '';
        selectedSubjectId = '';
        selectedStudentId = '';
        $('#section_id').html('<option value="">All Sections</option>');
        loadCbtOptions(true);
    });

    $('#section_id').on('change', function () {
        selectedSectionId = $(this).val() || '';
        selectedSubjectId = '';
        selectedStudentId = '';
        loadCbtOptions(false);
    });

    $('#subject_id').on('change', function () {
        selectedSubjectId = $(this).val() || '';
    });

    $('#student_id').on('change', function () {
        selectedStudentId = $(this).val() || '';
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
        var pad = function (n) { return n < 10 ? '0' + n : n; };

        $('#exam_end_date').val(endDateTime.getFullYear() + '-' + pad(endDateTime.getMonth() + 1) + '-' + pad(endDateTime.getDate()));
        $('#exam_end_time').val(pad(endDateTime.getHours()) + ':' + pad(endDateTime.getMinutes()));
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
            $('#duration').val(Math.round(diffMs / 60000));
        }
    }

    $('#exam_date, #exam_time, #duration').on('change input', calculateEndTime);
    $('#exam_end_date, #exam_end_time').on('change input', calculateDurationFromEnd);

    if ($('#class_id').val()) {
        loadCbtOptions(true);
    }

    if ($('#exam_date').val() && !$('#exam_end_date').val()) {
        calculateEndTime();
    }
});
</script>
@endpush
