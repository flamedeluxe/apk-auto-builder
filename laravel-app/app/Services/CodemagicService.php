<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Build;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CodemagicService
{
    private string $apiToken;
    private string $baseUrl = 'https://api.codemagic.io';

    public function __construct()
    {
        $this->apiToken = config('services.codemagic.api_token');
    }

    public function triggerBuild(Project $project, string $workflowName): bool
    {
        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/builds", [
                'appId' => $project->codemagic_app_id,
                'workflowId' => $workflowName,
                'branch' => 'main', // или master, в зависимости от репозитория
                'environment' => [
                    'APPLICATION_NAME' => $project->application_name,
                    'PACKAGE_NAME' => $project->package_name,
                    'BUILD_TYPE' => $project->build_type,
                    'GRADLE_TASK' => $project->gradle_task,
                    'LARAVEL_WEBHOOK_URL' => config('app.url'),
                    'PROJECT_ID' => $project->id,
                    'EMAIL_RECIPIENTS' => implode(',', $project->email_recipients ?? []),
                    'GOOGLE_PLAY_TRACK' => $project->google_play_track,
                ],
            ]);

            if ($response->successful()) {
                $buildData = $response->json();
                
                // Создаем запись о сборке
                Build::create([
                    'project_id' => $project->id,
                    'build_id' => $buildData['buildId'] ?? uniqid(),
                    'workflow_name' => $workflowName,
                    'status' => 'started',
                    'started_at' => now(),
                ]);

                Log::info("Build triggered for project {$project->name}", [
                    'project_id' => $project->id,
                    'workflow' => $workflowName,
                    'build_id' => $buildData['buildId'] ?? null,
                ]);

                return true;
            }

            Log::error("Failed to trigger build for project {$project->name}", [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception while triggering build for project {$project->name}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    public function getBuildStatus(string $buildId): ?array
    {
        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->apiToken,
            ])->get("{$this->baseUrl}/builds/{$buildId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get build status", [
                'build_id' => $buildId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getBuildLogs(string $buildId): ?string
    {
        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->apiToken,
            ])->get("{$this->baseUrl}/builds/{$buildId}/logs");

            if ($response->successful()) {
                return $response->body();
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Failed to get build logs", [
                'build_id' => $buildId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function cancelBuild(string $buildId): bool
    {
        try {
            $response = Http::withHeaders([
                'x-auth-token' => $this->apiToken,
            ])->post("{$this->baseUrl}/builds/{$buildId}/cancel");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Failed to cancel build", [
                'build_id' => $buildId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
