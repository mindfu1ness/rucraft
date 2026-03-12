<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Build;
use App\Models\BuildModerationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BuildController extends Controller
{
    /**
     * Создать новую заявку на постройку (для авторизованных пользователей)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'minecraft_version' => 'required|string|max:255',
                'description' => 'nullable|string|max:2000',
                'difficulty' => 'required|in:легкая,обычная,сложная',
                'materials' => 'nullable|array',
                'materials.*.name' => 'required|string|max:255',
                'materials.*.count' => 'required|integer|min:1',
                'image_file' => 'required|file|mimes:png,jpg,jpeg|max:20480',
                'build_file' => 'nullable|file|max:102400', // 100MB
            ], [
                'title.required' => 'Название обязательно',
                'minecraft_version.required' => 'Версия Minecraft обязательна',
                'difficulty.required' => 'Сложность обязательна',
                'difficulty.in' => 'Неверная сложность',
                'image_file.required' => 'Изображение обязательно',
                'image_file.mimes' => 'Изображение должно быть PNG, JPG или JPEG',
                'image_file.max' => 'Размер изображения не более 20 МБ',
                'build_file.max' => 'Размер файла не более 100 МБ',
            ]);

            $user = $request->user();

            // Сохранение изображения
            $imageFile = $request->file('image_file');
            $imageFilename = uniqid() . '_' . $imageFile->getClientOriginalName();
            $imagePath = $imageFile->storeAs('builds', $imageFilename, 'public');

            // Сохранение файла постройки (если есть)
            $buildFilePath = null;
            if ($request->hasFile('build_file')) {
                $buildFile = $request->file('build_file');
                $buildFilename = uniqid() . '_' . $buildFile->getClientOriginalName();
                $buildFilePath = $buildFile->storeAs('builds', $buildFilename, 'public');
            }

            // Создание заявки на модерацию
            $moderationRequest = BuildModerationRequest::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'minecraft_version' => $validated['minecraft_version'],
                'image' => $imagePath,
                'build_file' => $buildFilePath,
                'description' => $validated['description'] ?? null,
                'difficulty' => $validated['difficulty'],
                'materials' => $validated['materials'] ?? null,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Постройка отправлена на модерацию',
                'data' => [
                    'id' => $moderationRequest->id,
                    'title' => $moderationRequest->title,
                    'status' => $moderationRequest->status,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            if (isset($buildFilePath)) {
                Storage::disk('public')->delete($buildFilePath);
            }

            return response()->json([
                'error' => 'Ошибка валидации',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            if (isset($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }
            if (isset($buildFilePath)) {
                Storage::disk('public')->delete($buildFilePath);
            }

            Log::error('Error in BuildController@store', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Не удалось создать заявку',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function index(): JsonResponse
    {
        $builds = Build::query()
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Build $build) {
                return [
                    'id' => $build->id,
                    'title' => $build->title,
                    'description' => $build->description,
                    'image' => $build->image,
                    'minecraft_version' => $build->minecraft_version,
                    'difficulty' => $build->difficulty,
                    'author' => [
                        'id' => $build->user?->id,
                        'name' => $build->user?->name ?? 'Неизвестный автор',
                    ],
                    'created_at' => $build->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $builds,
        ]);
    }

    public function show(Build $build): JsonResponse
    {
        $build->load(['user', 'images']);

        return response()->json([
            'data' => [
                'id' => $build->id,
                'title' => $build->title,
                'description' => $build->description,
                'images' => $build->images->map(fn ($image) => $image->image_path)->values(),
                'blocks' => collect($build->materials ?? [])->map(function ($item) {
                    return [
                        'name' => $item['name'] ?? '',
                        'count' => (int) ($item['count'] ?? 0),
                    ];
                })->values(),
                'file_url' => $build->build_file,
                'author' => [
                    'id' => $build->user?->id,
                    'name' => $build->user?->name ?? 'Неизвестный автор',
                ],
                'created_at' => $build->created_at?->toIso8601String(),
            ],
        ]);
    }

    public function downloadFile(Build $build)
    {
        $path = $build->build_file;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $filename = basename($path);

        return Storage::disk('public')->download($path, $filename);
    }
}

