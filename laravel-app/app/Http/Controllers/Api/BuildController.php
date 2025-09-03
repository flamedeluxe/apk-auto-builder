<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Build;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BuildController extends Controller
{
    public function __construct(
        private TelegramService $telegramService
    ) {}

    public function start(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'workflow_name' => 'required|string',
            'application_name' => 'required|string',
            'package_name' => 'required|string',
            'build_id' => 'required|string',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $project = Project::findOrFail($request->project_id);
            
            $build = Build::create([
                'project_id' => $request->project_id,
                'build_id' => $request->build_id,
                'workflow_name' => $request->workflow_name,
                'status' => $request->status,
                'started_at' => now(),
            ]);

            // Отправляем уведомление в Telegram
            $this->telegramService->sendBuildNotification($project, $build);

            Log::info("Build started", [
                'project_id' => $request->project_id,
                'build_id' => $request->build_id,
                'workflow' => $request->workflow_name,
            ]);

            return response()->json(['success' => true, 'build' => $build]);
        } catch (\Exception $e) {
            Log::error("Failed to process build start", [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function finish(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'build_id' => 'required|string',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $build = Build::where('project_id', $request->project_id)
                ->where('build_id', $request->build_id)
                ->firstOrFail();

            $build->update([
                'status' => $request->status,
                'finished_at' => now(),
            ]);

            $project = $build->project;
            $this->telegramService->sendBuildNotification($project, $build);

            Log::info("Build finished", [
                'project_id' => $request->project_id,
                'build_id' => $request->build_id,
                'status' => $request->status,
            ]);

            return response()->json(['success' => true, 'build' => $build]);
        } catch (\Exception $e) {
            Log::error("Failed to process build finish", [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function publish(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'build_id' => 'required|string',
            'status' => 'required|string',
            'artifact_url' => 'required|url',
            'track' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $build = Build::where('project_id', $request->project_id)
                ->where('build_id', $request->build_id)
                ->firstOrFail();

            $build->update([
                'status' => $request->status,
                'artifact_url' => $request->artifact_url,
                'track' => $request->track,
                'finished_at' => now(),
            ]);

            $project = $build->project;
            $this->telegramService->sendBuildNotification($project, $build);

            Log::info("Build published", [
                'project_id' => $request->project_id,
                'build_id' => $request->build_id,
                'artifact_url' => $request->artifact_url,
                'track' => $request->track,
            ]);

            return response()->json(['success' => true, 'build' => $build]);
        } catch (\Exception $e) {
            Log::error("Failed to process build publish", [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }

    public function promote(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'workflow_name' => 'required|string',
            'application_name' => 'required|string',
            'package_name' => 'required|string',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            $project = Project::findOrFail($request->project_id);
            
            $build = Build::create([
                'project_id' => $request->project_id,
                'build_id' => uniqid('promote_'),
                'workflow_name' => $request->workflow_name,
                'status' => $request->status,
                'track' => 'production',
                'started_at' => now(),
                'finished_at' => now(),
            ]);

            $this->telegramService->sendBuildNotification($project, $build);

            Log::info("Build promoted", [
                'project_id' => $request->project_id,
                'workflow' => $request->workflow_name,
            ]);

            return response()->json(['success' => true, 'build' => $build]);
        } catch (\Exception $e) {
            Log::error("Failed to process build promotion", [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
