<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Build;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $botToken;
    private string $baseUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->baseUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $message, array $options = []): bool
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ], $options));

            if ($response->successful()) {
                Log::info("Telegram message sent successfully", [
                    'chat_id' => $chatId,
                    'message_length' => strlen($message),
                ]);
                return true;
            }

            Log::error("Failed to send Telegram message", [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception while sending Telegram message", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function sendBuildNotification(Project $project, Build $build): bool
    {
        $message = $this->formatBuildMessage($project, $build);
        $chatId = $project->telegram_chat_id ?: config('services.telegram.default_chat_id');

        if (!$chatId) {
            Log::warning("No Telegram chat ID configured for project {$project->name}");
            return false;
        }

        return $this->sendMessage($chatId, $message);
    }

    private function formatBuildMessage(Project $project, Build $build): string
    {
        $statusEmoji = match($build->status) {
            'started', 'debug_started' => '🚀',
            'finished', 'published', 'debug_published' => '✅',
            'promoted_to_production' => '🎉',
            'failed', 'error' => '❌',
            default => 'ℹ️',
        };

        $message = "{$statusEmoji} <b>{$project->application_name}</b>\n";
        $message .= "📦 Package: <code>{$project->package_name}</code>\n";
        $message .= "🔄 Workflow: <code>{$build->workflow_name}</code>\n";
        $message .= "📊 Status: <b>{$build->status_text}</b>\n";

        if ($build->track) {
            $message .= "🎯 Track: <b>{$build->track}</b>\n";
        }

        if ($build->started_at) {
            $message .= "⏰ Started: {$build->started_at->format('d.m.Y H:i')}\n";
        }

        if ($build->finished_at) {
            $message .= "⏱️ Duration: {$build->duration} мин\n";
        }

        if ($build->artifact_url) {
            $message .= "\n📥 <a href=\"{$build->artifact_url}\">Download APK/AAB</a>\n";
        }

        if ($build->error_message) {
            $message .= "\n❌ Error: <code>{$build->error_message}</code>\n";
        }

        return $message;
    }

    public function sendProjectCreatedNotification(Project $project): bool
    {
        $message = "🆕 <b>Новый проект создан</b>\n";
        $message .= "📱 App: <b>{$project->application_name}</b>\n";
        $message .= "📦 Package: <code>{$project->package_name}</code>\n";
        $message .= "🔗 Repo: <code>{$project->gitverse_repo_url}</code>\n";

        $chatId = config('services.telegram.admin_chat_id');
        return $this->sendMessage($chatId, $message);
    }

    public function sendBuildStartKeyboard(Project $project): bool
    {
        $message = "🔨 <b>{$project->application_name}</b>\n";
        $message .= "Выберите тип сборки:";

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '🚀 Release Build',
                        'callback_data' => "build_release_{$project->id}"
                    ],
                    [
                        'text' => '🐛 Debug Build',
                        'callback_data' => "build_debug_{$project->id}"
                    ]
                ],
                [
                    [
                        'text' => '🎯 Promote to Production',
                        'callback_data' => "promote_{$project->id}"
                    ]
                ]
            ]
        ];

        $chatId = $project->telegram_chat_id ?: config('services.telegram.default_chat_id');
        
        return $this->sendMessage($chatId, $message, [
            'reply_markup' => json_encode($keyboard)
        ]);
    }
}
