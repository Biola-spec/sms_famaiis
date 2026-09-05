@extends('admin.admin_master')
@section('admin')
<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="row">
                <div class="col-md-8 offset-md-2">
                    <div class="box">
                        <div class="box-header with-border">
                            <h3 class="box-title">Quiz — {{ $lesson->title }}</h3>
                            <a href="{{ route('learnhub.lesson', $lesson->id) }}" class="btn btn-sm btn-default" style="float:right">← Exit</a>
                        </div>
                        <form action="{{ route('learnhub.quiz.submit', $lesson->id) }}" method="POST" class="box-body">
                            @csrf
                            @foreach($questions as $q)
                            <div class="mb-4 p-3 border rounded">
                                <p><strong>Q{{ $q->question_number }}.</strong> {{ $q->question }}</p>
                                @foreach(['A', 'B', 'C', 'D'] as $opt)
                                    @php 
                                        $optLower = strtolower($opt);
                                        $text = $q->$optLower;
                                        if ($opt !== 'A' && !$text) continue;
                                    @endphp
                                <p>
                                    <input class="with-gap" type="radio" name="answers[q{{ $q->question_number }}]" value="{{ $opt }}" id="q{{ $q->question_number }}{{ $opt }}" required>
                                    <label for="q{{ $q->question_number }}{{ $opt }}">{{ $opt }}. {{ $text ?: '&nbsp;' }}</label>
                                </p>
                                @endforeach
                            </div>
                            @endforeach
                            <button type="submit" class="btn btn-success btn-block">Submit Quiz</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
