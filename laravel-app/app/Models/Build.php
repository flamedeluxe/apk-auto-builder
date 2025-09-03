<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Build extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'build_id',
        'workflow_name',
        'status',
        'artifact_url',
        'track',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getDurationAttribute()
    {
        if ($this->started_at && $this->finished_at) {
            return $this->started_at->diffInMinutes($this->finished_at);
        }
        return null;
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'started', 'debug_started' => 'warning',
            'finished', 'published', 'debug_published' => 'success',
            'promoted_to_production' => 'info',
            'failed', 'error' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'started' => 'Сборка началась',
            'debug_started' => 'Debug сборка началась',
            'finished' => 'Сборка завершена',
            'published' => 'Опубликовано',
            'debug_published' => 'Debug APK готов',
            'promoted_to_production' => 'Продвинуто в Production',
            'failed' => 'Ошибка сборки',
            'error' => 'Ошибка',
            default => 'Неизвестно',
        };
    }
}
