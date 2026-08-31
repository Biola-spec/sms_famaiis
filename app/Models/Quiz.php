<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'section_id',
        'student_id',
        'subject_id',
        'term',
        'title',
        'duration',
        'starts_at',
        'ends_at',
        'retake_limit',
        'created_by',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Quiz $quiz) {
            if ($quiz->starts_at && $quiz->duration) {
                $quiz->ends_at = $quiz->starts_at->copy()->addMinutes((int) $quiz->duration);
            } elseif (!$quiz->starts_at) {
                $quiz->ends_at = null;
            }
        });
    }

    public function examEndTime()
    {
        if ($this->ends_at) {
            return $this->ends_at;
        }

        if ($this->starts_at && $this->duration) {
            return $this->starts_at->copy()->addMinutes((int) $this->duration);
        }

        return null;
    }

    public function isAvailableToTake(): bool
    {
        if ($this->status !== 'published') {
            return false;
        }

        if (!$this->starts_at) {
            return true;
        }

        return now()->gte($this->starts_at);
    }

    public function reviewIsUnlocked(): bool
    {
        $end = $this->examEndTime();

        if (!$end) {
            return true;
        }

        return now()->gte($end);
    }

    public static function hasScheduleOverlap(int $classId, $startsAt, $endsAt, ?int $ignoreId = null): bool
    {
        if (!$startsAt || !$endsAt) {
            return false;
        }

        $query = static::query()
            ->where('class_id', $classId)
            ->whereIn('status', ['published', 'locked'])
            ->whereNotNull('starts_at')
            ->whereNotNull('ends_at')
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    public function student_class()
    {
        return $this->belongsTo(StudentClass::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(SchoolSection::class, 'section_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(SchoolSubject::class, 'subject_id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function passages()
    {
        return $this->hasMany(Passage::class)->orderBy('start_number');
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function retakes()
    {
        return $this->hasMany(QuizRetake::class)->orderByDesc('granted_at');
    }
}
