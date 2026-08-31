@extends('admin.admin_master')
@section('admin')
@php
    $questionCount = $attempt->quiz->questions_count ?? $attempt->quiz->questions()->count();
    $reviewUnlocked = $reviewUnlocked ?? $attempt->quiz->reviewIsUnlocked();
@endphp
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="box">
                        <div class="box-header with-border text-center">
                            <h3 class="box-title">Quiz Result: {{ $attempt->quiz->title }}</h3>
                        </div>
                        <div class="box-body">
                            @if(session('message'))
                                <div class="alert alert-{{ session('alert-type') }}">{{ session('message') }}</div>
                            @endif
                            <div class="text-center mb-4">
                                <h1 class="display-4 font-weight-bold text-{{ $attempt->score > ($questionCount / 2) ? 'success' : 'danger' }}">
                                    {{ $attempt->score }} / {{ $questionCount }}
                                </h1>
                                <p class="text-muted">Submitted on {{ optional($attempt->submitted_at)->format('M d, Y h:i A') }}</p>
                            </div>

                            <hr>
                            @if($reviewUnlocked)
                                <h4>Detailed Review</h4>
                                @foreach($attempt->answers as $key => $answer)
                                <div class="mb-4 p-3 border rounded {{ $answer->is_correct ? 'border-success' : 'border-danger' }}">
                                    <h5>Q{{ $key+1 }}. {!! $answer->question->question !!}</h5>
                                    @if($answer->question->image)
                                    <div class="mb-2">
                                        <img src="{{ asset('upload/questions/' . $answer->question->image) }}" style="max-height: 150px; border-radius: 4px;" alt="Question Image">
                                    </div>
                                    @endif
                                    <p><strong>Your Answer:</strong> Option {{ $answer->selected_option }}
                                        @if($answer->is_correct)
                                            <i class="fa fa-check text-success"></i>
                                        @else
                                            <i class="fa fa-times text-danger"></i> (Correct: Option {{ $answer->question->correct_answer }})
                                        @endif
                                    </p>
                                </div>
                                @endforeach
                            @else
                                <div class="alert alert-warning">
                                    Full review available after {{ optional($attempt->quiz->examEndTime())->format('M d, Y h:i A') }}.
                                    Your score is shown above.
                                </div>
                            @endif

                            <div class="text-center mt-4">
                                @if($reviewUnlocked)
                                    <a href="{{ route('student.cbt.result.download', $attempt->id) }}" class="btn btn-success">Download PDF</a>
                                @endif
                                <a href="{{ route('student.cbt.index') }}" class="btn btn-primary">Back to Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
