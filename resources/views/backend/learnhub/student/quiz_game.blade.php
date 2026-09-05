@extends('admin.admin_master')
@section('admin')
<style>
    .quiz-game-wrap { max-width: 720px; margin: 0 auto; }
    .game-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff; border-radius: 12px; padding: 20px; margin-bottom: 20px;
    }
    .game-stats { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 12px; }
    .game-stat {
        background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px 14px;
        font-size: 13px; font-weight: 600;
    }
    .game-stat span { font-size: 18px; display: block; }
    .progress-dots { display: flex; gap: 6px; justify-content: center; margin: 16px 0; }
    .progress-dot {
        width: 12px; height: 12px; border-radius: 50%;
        background: #ddd; transition: all 0.3s;
    }
    .progress-dot.active { background: #667eea; transform: scale(1.2); }
    .progress-dot.done { background: #28a745; }
    .question-card {
        background: #fff; border-radius: 12px; padding: 24px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 2px solid #eee;
        transition: transform 0.2s;
    }
    .question-card.shake { animation: shake 0.4s; }
    .question-card.pop { animation: pop 0.3s; }
    @keyframes shake {
        0%,100% { transform: translateX(0); }
        25% { transform: translateX(-8px); }
        75% { transform: translateX(8px); }
    }
    @keyframes pop {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }
    .option-btn {
        display: block; width: 100%; text-align: left; padding: 14px 18px;
        margin-bottom: 10px; border: 2px solid #e0e0e0; border-radius: 10px;
        background: #fafafa; cursor: pointer; transition: all 0.2s; font-size: 14px;
    }
    .option-btn:hover:not(:disabled) { border-color: #667eea; background: #f0f0ff; }
    .option-btn.selected { border-color: #667eea; background: #eef0ff; }
    .option-btn.correct { border-color: #28a745; background: #d4edda; }
    .option-btn.wrong { border-color: #dc3545; background: #f8d7da; }
    .option-btn:disabled { cursor: default; opacity: 0.9; }
    .xp-bar-wrap { background: rgba(255,255,255,0.3); border-radius: 8px; height: 10px; margin-top: 10px; overflow: hidden; }
    .xp-bar { height: 100%; background: #ffd700; border-radius: 8px; transition: width 0.4s; width: 0%; }
    .streak-fire { color: #ff6b35; }
    .game-start, .game-finish { text-align: center; padding: 40px 20px; }
    .btn-play {
        background: linear-gradient(135deg, #667eea, #764ba2); color: #fff;
        border: none; padding: 14px 32px; border-radius: 30px; font-size: 16px; font-weight: 700;
    }
    .btn-play:hover { color: #fff; opacity: 0.9; }
    .timer-display { font-family: monospace; font-size: 20px; }
</style>

<div class="content-wrapper">
    <div class="container-full">
        <section class="content">
            <div class="quiz-game-wrap">
                <div class="game-header">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <small>Quiz Game — from your lesson note</small>
                            <h4 class="mb-0 mt-1">{{ $lesson->title }}</h4>
                        </div>
                        <a href="{{ route('learnhub.lesson', $lesson->id) }}" class="btn btn-sm btn-light">← Back to Note</a>
                    </div>
                    <div class="game-stats" id="gameStats" style="display:none">
                        <div class="game-stat">XP <span id="xpDisplay">0</span></div>
                        <div class="game-stat"><span class="streak-fire">🔥</span> Streak <span id="streakDisplay">0</span></div>
                        <div class="game-stat">Question <span id="qNumDisplay">1</span>/{{ $questions->count() }}</div>
                        <div class="game-stat timer-display">⏱ <span id="timerDisplay">0:00</span></div>
                    </div>
                    <div class="xp-bar-wrap" id="xpBarWrap" style="display:none">
                        <div class="xp-bar" id="xpBar"></div>
                    </div>
                </div>

                <div id="gameStart" class="question-card game-start">
                    <h2>🎮 Ready to Play?</h2>
                    <p class="text-muted">Answer questions from <strong>{{ $lesson->title }}</strong>. Earn XP, build streaks, and beat your best score!</p>
                    <ul class="text-left text-muted small" style="max-width:320px;margin:0 auto 20px">
                        <li>+100 XP per correct answer</li>
                        <li>+25 bonus XP for each streak level</li>
                        <li>Pass with 50% or more to unlock the badge</li>
                    </ul>
                    <button type="button" class="btn btn-play" id="btnStartGame">Start Game</button>
                </div>

                <div id="gamePlay" style="display:none">
                    <div class="progress-dots" id="progressDots"></div>
                    <div class="question-card" id="questionCard">
                        <p class="text-muted mb-1">Question <span id="currentQ">1</span></p>
                        <h4 id="questionText"></h4>
                        <div id="optionsContainer" class="mt-4"></div>
                        <button type="button" class="btn btn-primary btn-block mt-3" id="btnNext" disabled>Next Question →</button>
                    </div>
                </div>

                <form id="gameSubmitForm" action="{{ route('learnhub.quiz.submit', $lesson->id) }}" method="POST" style="display:none">
                    @csrf
                    <input type="hidden" name="game_mode" value="1">
                    <input type="hidden" name="game_points" id="inputGamePoints" value="0">
                    <input type="hidden" name="max_streak" id="inputMaxStreak" value="0">
                    <input type="hidden" name="time_seconds" id="inputTimeSeconds" value="0">
                    <div id="answersHidden"></div>
                </form>
            </div>
        </section>
    </div>
</div>

<script>
(function() {
    @php
        $gameQuestions = $questions->map(function ($q) {
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $opt) {
                $optLower = strtolower($opt);
                $text = $q->$optLower;
                if ($opt === 'A' || $text) {
                    $options[$opt] = $text ?: '&nbsp;';
                }
            }
            return [
                'num' => $q->question_number,
                'question' => $q->question,
                'options' => $options,
                'correct' => $q->correct_answer,
            ];
        })->values();
    @endphp
    const questions = @json($gameQuestions);
    const total = questions.length;
    let current = 0, xp = 0, streak = 0, maxStreak = 0, answers = {};
    let timerInterval = null, seconds = 0;

    const els = {
        start: document.getElementById('gameStart'),
        play: document.getElementById('gamePlay'),
        stats: document.getElementById('gameStats'),
        xpBarWrap: document.getElementById('xpBarWrap'),
        xpDisplay: document.getElementById('xpDisplay'),
        streakDisplay: document.getElementById('streakDisplay'),
        qNumDisplay: document.getElementById('qNumDisplay'),
        timerDisplay: document.getElementById('timerDisplay'),
        xpBar: document.getElementById('xpBar'),
        dots: document.getElementById('progressDots'),
        card: document.getElementById('questionCard'),
        questionText: document.getElementById('questionText'),
        optionsContainer: document.getElementById('optionsContainer'),
        btnNext: document.getElementById('btnNext'),
        currentQ: document.getElementById('currentQ'),
        form: document.getElementById('gameSubmitForm'),
        answersHidden: document.getElementById('answersHidden'),
        inputGamePoints: document.getElementById('inputGamePoints'),
        inputMaxStreak: document.getElementById('inputMaxStreak'),
        inputTimeSeconds: document.getElementById('inputTimeSeconds'),
    };

    questions.forEach((_, i) => {
        const dot = document.createElement('div');
        dot.className = 'progress-dot' + (i === 0 ? ' active' : '');
        dot.dataset.index = i;
        els.dots.appendChild(dot);
    });

    function formatTime(s) {
        const m = Math.floor(s / 60), sec = s % 60;
        return m + ':' + String(sec).padStart(2, '0');
    }

    function startTimer() {
        timerInterval = setInterval(() => {
            seconds++;
            els.timerDisplay.textContent = formatTime(seconds);
        }, 1000);
    }

    function updateStats() {
        els.xpDisplay.textContent = xp;
        els.streakDisplay.textContent = streak;
        els.qNumDisplay.textContent = current + 1;
        const maxXp = total * 175;
        els.xpBar.style.width = Math.min(100, (xp / maxXp) * 100) + '%';
    }

    function renderQuestion() {
        const q = questions[current];
        els.currentQ.textContent = current + 1;
        els.questionText.textContent = q.question;
        els.optionsContainer.innerHTML = '';
        els.btnNext.disabled = true;

        document.querySelectorAll('.progress-dot').forEach((d, i) => {
            d.classList.remove('active');
            if (i < current) d.classList.add('done');
            if (i === current) d.classList.add('active');
        });

        Object.entries(q.options).forEach(([key, text]) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'option-btn';
            btn.innerHTML = '<strong>' + key + '.</strong> ' + text;
            btn.dataset.option = key;
            btn.addEventListener('click', () => selectOption(btn, q));
            els.optionsContainer.appendChild(btn);
        });
        updateStats();
    }

    function selectOption(btn, q) {
        if (els.btnNext.dataset.answered) return;
        els.btnNext.dataset.answered = '1';

        const selected = btn.dataset.option;
        answers['q' + q.num] = selected;

        els.optionsContainer.querySelectorAll('.option-btn').forEach(b => {
            b.disabled = true;
            if (b.dataset.option === q.correct) b.classList.add('correct');
            else if (b.dataset.option === selected) b.classList.add('wrong');
        });

        if (selected === q.correct) {
            streak++;
            if (streak > maxStreak) maxStreak = streak;
            const bonus = Math.max(0, streak - 1) * 25;
            xp += 100 + bonus;
            els.card.classList.add('pop');
            setTimeout(() => els.card.classList.remove('pop'), 300);
        } else {
            streak = 0;
            els.card.classList.add('shake');
            setTimeout(() => els.card.classList.remove('shake'), 400);
        }

        updateStats();
        els.btnNext.disabled = false;
        els.btnNext.textContent = current < total - 1 ? 'Next Question →' : 'See Results 🏆';
    }

    document.getElementById('btnStartGame').addEventListener('click', () => {
        els.start.style.display = 'none';
        els.play.style.display = 'block';
        els.stats.style.display = 'flex';
        els.xpBarWrap.style.display = 'block';
        startTimer();
        renderQuestion();
    });

    els.btnNext.addEventListener('click', () => {
        delete els.btnNext.dataset.answered;
        if (current < total - 1) {
            current++;
            renderQuestion();
        } else {
            finishGame();
        }
    });

    function finishGame() {
        clearInterval(timerInterval);
        els.inputGamePoints.value = xp;
        els.inputMaxStreak.value = maxStreak;
        els.inputTimeSeconds.value = seconds;
        els.answersHidden.innerHTML = '';
        Object.entries(answers).forEach(([key, val]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'answers[' + key + ']';
            input.value = val;
            els.answersHidden.appendChild(input);
        });
        els.form.submit();
    }
})();
</script>
@endsection
