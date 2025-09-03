<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'package_name',
        'application_name',
        'description',
        'gitverse_repo_url',
        'codemagic_app_id',
        'google_play_track',
        'gradle_task',
        'build_type',
        'email_recipients',
        'is_active',
        'telegram_chat_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_recipients' => 'array',
    ];

    public function builds(): HasMany
    {
        return $this->hasMany(Build::class);
    }

    public function getLatestBuildAttribute()
    {
        return $this->builds()->latest()->first();
    }

    public function getBuildStatusAttribute()
    {
        $latestBuild = $this->latest_build;
        return $latestBuild ? $latestBuild->status : 'never_built';
    }
}
