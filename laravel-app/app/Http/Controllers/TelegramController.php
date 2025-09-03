<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\CodemagicService;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    public function __construct(
        private CodemagicService $codemagicService,
        private TelegramService $telegramService
    ) {}

    public function webhook(Request $request): JsonResponse
    {
        try {
            $update = $request->all();
            Log::info('Telegram webhook received', $update);

            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    private function handleMessage(array $message): void
    {
        $text = $message['text'] ?? '';
        $chatId = $message['chat']['id'];
        $chatType = $message['chat']['type'] ?? 'private';
        $isGroup = in_array($chatType, ['group', 'supergroup']);

        // Обработка команд в группах (могут содержать упоминание бота)
        if ($isGroup && str_contains($text, '@')) {
            $botUsername = config('services.telegram.bot_username');
            if (!str_contains($text, "@{$botUsername}")) {
                return; // Игнорируем сообщения не для нашего бота
            }
            // Убираем упоминание бота из текста
            $text = str_replace("@{$botUsername}", '', $text);
            $text = trim($text);
        }

        if ($text === '/start') {
            $this->sendWelcomeMessage($chatId, $isGroup);
        } elseif ($text === '/projects') {
            $this->sendProjectsList($chatId, $isGroup);
        } elseif (str_starts_with($text, '/build_')) {
            $this->handleBuildCommand($chatId, $text, $isGroup);
        } elseif ($isGroup && !empty($text)) {
            // В группах игнорируем обычные сообщения
            return;
        } else {
            $this->telegramService->sendMessage($chatId, 'Неизвестная команда. Используйте /start для получения списка команд.');
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $data = $callbackQuery['data'];
        $chatId = $callbackQuery['message']['chat']['id'];
        $messageId = $callbackQuery['message']['message_id'];

        if (str_starts_with($data, 'build_release_')) {
            $projectId = (int) str_replace('build_release_', '', $data);
            $this->triggerBuild($projectId, 'build-release-and-publish-beta', $chatId);
        } elseif (str_starts_with($data, 'build_debug_')) {
            $projectId = (int) str_replace('build_debug_', '', $data);
            $this->triggerBuild($projectId, 'build-debug', $chatId);
        } elseif (str_starts_with($data, 'promote_')) {
            $projectId = (int) str_replace('promote_', '', $data);
            $this->triggerBuild($projectId, 'beta-to-release', $chatId);
        }

        // Отвечаем на callback query
        $this->telegramService->sendMessage($chatId, 'Команда выполнена!', [
            'reply_to_message_id' => $messageId
        ]);
    }

    private function sendWelcomeMessage(int $chatId, bool $isGroup = false): void
    {
        $message = "🤖 <b>Android CI/CD Bot</b>\n\n";
        $message .= "Доступные команды:\n";
        $message .= "/start - Показать это сообщение\n";
        $message .= "/projects - Список проектов\n";
        $message .= "/build_[project_id] - Запустить сборку проекта\n\n";
        
        if ($isGroup) {
            $message .= "💡 <i>В группе используйте команды с упоминанием бота:</i>\n";
            $message .= "<code>@your_bot_name /start</code>\n";
            $message .= "<code>@your_bot_name /projects</code>\n";
        }
        
        $message .= "\nИспользуйте кнопки для быстрого доступа к функциям.";

        $this->telegramService->sendMessage($chatId, $message);
    }

    private function sendProjectsList(int $chatId, bool $isGroup = false): void
    {
        $projects = Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            $this->telegramService->sendMessage($chatId, 'Нет активных проектов.');
            return;
        }

        $message = "📱 <b>Активные проекты:</b>\n\n";
        
        foreach ($projects as $project) {
            $message .= "🔹 <b>{$project->application_name}</b>\n";
            $message .= "📦 <code>{$project->package_name}</code>\n";
            $message .= "🔄 Статус: {$project->build_status}\n";
            
            if ($isGroup) {
                $message .= "⚡ <code>@your_bot_name /build_{$project->id}</code>\n\n";
            } else {
                $message .= "⚡ /build_{$project->id}\n\n";
            }
        }

        $this->telegramService->sendMessage($chatId, $message);
    }

    private function handleBuildCommand(int $chatId, string $command, bool $isGroup = false): void
    {
        $projectId = (int) str_replace('/build_', '', $command);
        $project = Project::find($projectId);

        if (!$project) {
            $this->telegramService->sendMessage($chatId, 'Проект не найден.');
            return;
        }

        if (!$project->is_active) {
            $this->telegramService->sendMessage($chatId, 'Проект неактивен.');
            return;
        }

        $this->telegramService->sendBuildStartKeyboard($project, $isGroup);
    }

    private function triggerBuild(int $projectId, string $workflow, int $chatId): void
    {
        $project = Project::find($projectId);

        if (!$project) {
            $this->telegramService->sendMessage($chatId, 'Проект не найден.');
            return;
        }

        $success = $this->codemagicService->triggerBuild($project, $workflow);

        if ($success) {
            $workflowName = match($workflow) {
                'build-release-and-publish-beta' => 'Release сборка',
                'build-debug' => 'Debug сборка',
                'beta-to-release' => 'Продвижение в Production',
                default => 'Сборка',
            };

            $this->telegramService->sendMessage($chatId, "✅ {$workflowName} для <b>{$project->application_name}</b> запущена!");
        } else {
            $this->telegramService->sendMessage($chatId, "❌ Ошибка запуска сборки для <b>{$project->application_name}</b>");
        }
    }
}
