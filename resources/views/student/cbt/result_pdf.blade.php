<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CBT Result #{{ $attempt->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #333; font-size: 13px; }
        h1 { font-size: 20px; }
        .score { font-size: 22px; font-weight: bold; margin: 12px 0; }
        .item { border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; }
        .ok { border-color: #16a34a; }
        .bad { border-color: #dc2626; }
    </style>
</head>
<body>
    <h1>{{ $attempt->quiz->title }}</h1>
    <p>Subject: {{ optional($attempt->quiz->subject)->name }}</p>
    <p>Submitted: {{ optional($attempt->submitted_at)->format('M d, Y h:i A') }}</p>
    <p class="score">Score: {{ $attempt->score }} / {{ $attempt->quiz->questions_count ?? $attempt->answers->count() }}</p>

    @foreach($attempt->answers as $key => $answer)
        <div class="item {{ $answer->is_correct ? 'ok' : 'bad' }}">
            <p><strong>Q{{ $key+1 }}.</strong> {{ strip_tags($answer->question->question ?? '') }}</p>
            <p>Your answer: Option {{ $answer->selected_option }}
                @if($answer->is_correct)
                    (Correct)
                @else
                    (Incorrect — correct: Option {{ $answer->question->correct_answer ?? '-' }})
                @endif
            </p>
        </div>
    @endforeach
</body>
</html>
