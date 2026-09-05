<div class="row">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, .content-wrapper { font-family: 'Inter', sans-serif !important; background-color: #f8fafc !important; }
        .box { border-radius: 16px !important; border: none !important; box-shadow: 0 4px 20px rgba(0,0,0,0.04) !important; overflow: hidden; }
        .box-header { background: #fff !important; border-bottom: 1px solid #f1f5f9 !important; padding: 22px 28px !important; }
        .box-title { font-weight: 700 !important; color: #0f172a !important; font-size: 1.25rem !important; }
        
        .quiz-option { 
            cursor: pointer; 
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
            border: 2px solid #f1f5f9; 
            border-radius: 14px !important; 
            background: #fff;
            padding: 20px 24px !important;
            margin-bottom: 16px !important;
            display: flex;
            align-items: center;
            position: relative;
        }
        .quiz-option:hover { 
            border-color: #cbd5e1; 
            transform: translateY(-2px);
            background: #fdfdfd;
        }
        .quiz-option.selected { 
            background-color: #f5f8ff; 
            border-color: #4f46e5 !important; 
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.1);
        }
        
        .option-letter { 
            width: 40px; height: 40px; 
            background: #f8fafc; 
            border-radius: 12px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: 700; 
            margin-right: 20px;
            color: #64748b;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
            transition: all 0.2s;
            font-size: 1.1em;
        }
        .selected .option-letter { 
            background: #4f46e5; 
            color: white; 
            border-color: #4f46e5; 
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }
        
        .option-content { color: #334155; font-weight: 500; }
        .selected .option-content { color: #1e1b4b; font-weight: 600; }
        
        .question-image-container img, .passage-content img {
            max-width: 100% !important;
            max-height: 300px !important;
            object-fit: contain;
            border-radius: 12px;
            margin: 20px auto;
            display: block;
            box-shadow: 0 8px 20px rgba(0,0,0,0.08);
            border: 1px solid #f1f5f9;
        }
        .passage-box {
            background-color: #f8fafc !important;
            color: #1e293b !important;
            border-left: 6px solid #0ea5e9 !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        [data-layout-mode="dark"] .passage-box {
            background-color: #1e293b !important;
            color: #f1f5f9 !important;
            border-left-color: #38bdf8 !important;
        }
        .passage-content { line-height: 1.8; font-size: 1.1em; }
        
        .btn-primary { background: #4f46e5 !important; border: none !important; font-weight: 600; border-radius: 12px; padding: 12px 28px !important; box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2) !important; }
        .btn-success { background: #10b981 !important; border: none !important; font-weight: 600; border-radius: 12px; padding: 12px 28px !important; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2) !important; }
        .btn-secondary { background: #64748b !important; border: none !important; font-weight: 600; border-radius: 12px; padding: 12px 28px !important; }
        
        #timerDisplay { color: #ef4444; font-weight: 800; letter-spacing: 1px; }
        
        .q-nav-btn {
            width: 42px; height: 42px;
            border-radius: 12px;
            font-weight: 700;
            transition: all 0.2s;
            border: 2px solid transparent;
            margin: 5px;
        }
        .q-nav-btn.active {
            border-color: #4f46e5 !important;
            background: #fff !important;
            color: #4f46e5 !important;
            transform: scale(1.15);
            box-shadow: 0 6px 15px rgba(79, 70, 229, 0.15);
        }
        .badge { padding: 8px 14px; border-radius: 8px; font-weight: 600; }
        .bg-primary-light { background-color: #f5f8ff; }
    </style>

    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">{{ $quizTitle }}</h3>
                <div class="pull-right">
                    <span class="badge badge-primary">Question {{ $currentQuestionIndex + 1 }} of {{ count($questions) }}</span>
                </div>
            </div>
            
            <div class="box-body" style="min-height: 500px; padding: 30px !important;">
                @php 
                    $index = $currentQuestionIndex;
                    $question = $questions[$index];
                @endphp

                <div class="p-2" wire:key="question-{{ $question['id'] }}">
                    <!-- Passages -->
                    @foreach($activePassages as $passage)
                        <div class="mb-5 p-4 passage-box">
                            <h5 class="font-weight-bold mb-3 d-flex align-items-center text-info">
                                <i class="fa fa-file-text-o mr-2"></i> Reading Passage (Q{{ $passage['start_number'] }}-{{ $passage['end_number'] }})
                            </h5>
                            <div class="passage-content">
                                {!! $passage['content'] !!}
                            </div>
                            @if($passage['image'])
                            <div class="mt-3">
                                <img src="{{ asset('upload/questions/' . $passage['image']) }}" class="img-fluid rounded">
                            </div>
                            @endif
                        </div>
                    @endforeach

                    <div class="question-content">
                        <h4 class="mb-4">
                            {!! $question['question'] !!}
                        </h4>
                    </div>

                    @if($question['image'])
                    <div class="mb-5 question-image-container">
                        <img src="{{ asset('upload/questions/' . $question['image']) }}">
                    </div>
                    @endif
                    
                    <div class="options-container mt-4">
                        @foreach(['A', 'B', 'C', 'D', 'E'] as $opt)
                            @php 
                                $optLower = strtolower($opt);
                                $optText = $question["option_$optLower"];
                                $optImg = $question["image_$optLower"];
                                $qid = (string) $question['id'];
                                $isSelected = ($answers[$qid] ?? '') == $opt;
                                if ($opt !== 'A' && !$optText && !$optImg) continue;
                            @endphp
                            <label class="quiz-option {{ $isSelected ? 'selected' : '' }}">
                                <input type="radio" wire:model.live="answers.{{ $qid }}" value="{{ $opt }}" style="display: none;">
                                <div class="option-letter">{{ $opt }}</div>
                                <div class="flex-grow-1">
                                    <div class="option-content">{!! $optText ?: '&nbsp;' !!}</div>
                                    @if($optImg)
                                    <div class="mt-2">
                                        <img src="{{ asset('upload/questions/' . $optImg) }}" class="img-fluid rounded border" style="max-height: 120px;">
                                    </div>
                                    @endif
                                </div>
                                @if($isSelected)
                                    <div class="ml-3 text-primary"><i class="fa fa-check-circle fa-2x"></i></div>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="box-footer bg-light p-4 d-flex justify-content-between align-items-center">
                <button type="button" wire:click="previousQuestion" class="btn btn-secondary btn-lg {{ $currentQuestionIndex == 0 ? 'disabled' : '' }}" {{ $currentQuestionIndex == 0 ? 'disabled' : '' }}>
                    <i class="fa fa-chevron-left mr-2"></i> Previous
                </button>
                
                @if($currentQuestionIndex == count($questions) - 1)
                    <button type="button" wire:click="submit" class="btn btn-success btn-lg px-5 shadow" onclick="return confirm('Ready to submit your quiz?')">
                        <i class="fa fa-paper-plane mr-2"></i> Final Submit
                    </button>
                @else
                    <button type="button" wire:click="nextQuestion" class="btn btn-primary btn-lg px-5 shadow">
                        Next <i class="fa fa-chevron-right ml-2"></i>
                    </button>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div style="position: sticky; top: 20px;">
            <!-- Student Profile -->
            <div class="box box-widget widget-user-2 mb-4">
                <div class="widget-user-header bg-primary" style="padding: 25px !important;">
                    <div class="widget-user-image">
                        <img class="rounded-circle bg-white" src="{{ (!empty(Auth::user()->image)) ? url('upload/user_images/'.Auth::user()->image) : url('upload/no_image.jpg') }}" alt="User Avatar" style="width: 70px; height: 70px; object-fit: cover; border: 3px solid rgba(255,255,255,0.2);">
                    </div>
                    <div class="ml-2">
                        <h3 class="widget-user-username text-white font-weight-bold mb-0" style="font-size: 1.4em;">{{ Auth::user()->name }}</h3>
                        <h5 class="widget-user-desc text-white-50">{{ Auth::user()->id_no }}</h5>
                    </div>
                </div>
            </div>

            <!-- Timer -->
            <div class="box mb-4">
                <div class="box-header text-center py-3">
                    <h4 class="box-title text-muted"><i class="fa fa-clock-o mr-2"></i> Time Remaining</h4>
                </div>
                <div class="box-body text-center py-4">
                    <h1 id="timerDisplay" class="display-4 mb-0">--:--</h1>
                </div>
            </div>

            <!-- Question Bank -->
            <div class="box">
                <div class="box-header with-border py-3">
                    <h4 class="box-title text-muted"><i class="fa fa-th mr-2"></i> Navigator</h4>
                </div>
                <div class="box-body p-3">
                    <div class="d-flex flex-wrap justify-content-start">
                        @foreach($questions as $idx => $q)
                            @php $navQid = (string) $q['id']; @endphp
                            <button type="button" wire:click="goToQuestion({{ $idx }})" 
                               class="btn btn-sm q-nav-btn {{ !empty($answers[$navQid]) ? 'btn-success' : 'btn-outline-secondary' }} {{ $currentQuestionIndex == $idx ? 'active' : '' }}">
                                {{ $idx+1 }}
                            </button>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-3 border-top d-flex justify-content-around">
                        <div class="d-flex align-items-center">
                            <span class="badge badge-success p-1 mr-2" style="width: 12px; height: 12px;"> </span>
                            <small class="text-muted font-weight-bold">Answered</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="badge badge-secondary p-1 mr-2" style="width: 12px; height: 12px; background: #e2e8f0;"> </span>
                            <small class="text-muted font-weight-bold">Pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        let timeLeft = @json($timeLeft);
        const display = document.getElementById('timerDisplay');
        
        function updateDisplay() {
            if(!display) return;
            let minutes = Math.floor(timeLeft / 60);
            let seconds = timeLeft % 60;
            display.innerText = 
                (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                (seconds < 10 ? "0" + seconds : seconds);
        }

        updateDisplay();
        
        let timer = setInterval(function() {
            if (timeLeft <= 0) {
                clearInterval(timer);
                if(display) display.innerText = "00:00";
                $wire.submit();
            } else {
                timeLeft--;
                updateDisplay();
            }
        }, 1000);

        $wire.on('error', (event) => {
            if(window.toastr) toastr.error(event[0].message);
            else alert(event[0].message);
        });

        // Prevent back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
    @endscript
</div>
