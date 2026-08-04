<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Link;
use App\Services\ShortCodeGeneratorService;
use App\Services\UrlNormalizerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LinkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $links = $request->user()->links()
            ->when($request->user()->current_workspace_id, function ($query) use ($request) {
                return $query->where('workspace_id', $request->user()->current_workspace_id);
            }, function ($query) {
                return $query->whereNull('workspace_id');
            })
            ->latest()
            ->paginate($request->query('per_page', 15));

        return response()->json($links);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, UrlNormalizerService $normalizer, ShortCodeGeneratorService $generator): JsonResponse
    {
        $validated = $request->validate([
            'long_url' => ['required', 'url', 'max:2048'],
            'title' => ['nullable', 'string', 'max:255'],
            'custom_alias' => [
                'nullable',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('links', 'short_code'),
            ],
            'custom_domain_id' => [
                'nullable',
                Rule::exists('custom_domains', 'id')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id)
                        ->when($request->user()->current_workspace_id, function ($q) use ($request) {
                            return $q->where('workspace_id', $request->user()->current_workspace_id);
                        });
                }),
            ],
        ]);

        $normalizedUrl = $normalizer->normalize($validated['long_url']);

        // Check for duplicate in the same scope
        $existingLink = $request->user()->links()
            ->where('original_url', $normalizedUrl)
            ->when($request->user()->current_workspace_id, function ($query) use ($request) {
                return $query->where('workspace_id', $request->user()->current_workspace_id);
            }, function ($query) {
                return $query->whereNull('workspace_id');
            })
            ->first();

        if ($existingLink && !$validated['custom_alias']) {
            return response()->json([
                'message' => 'Link already exists',
                'data' => $existingLink,
            ], 200);
        }

        $link = DB::transaction(function () use ($validated, $normalizedUrl, $generator, $request) {
            $link = Link::create([
                'user_id' => $request->user()->id,
                'workspace_id' => $request->user()->current_workspace_id,
                'custom_domain_id' => $validated['custom_domain_id'] ?? null,
                'original_url' => $normalizedUrl,
                'title' => $validated['title'] ?? null,
                'short_code' => $validated['custom_alias'] ?? 'tmp_'.substr(Str::uuid()->toString(), 0, 15), // Temporary placeholder
            ]);

            if (empty($validated['custom_alias'])) {
                $generator->generateForLink($link);
            }

            return $link;
        });

        return response()->json([
            'message' => 'Link created successfully',
            'data' => $link,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $link = $request->user()->links()
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['data' => $link]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $link = $request->user()->links()
            ->where('id', $id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $link->update($validated);

        return response()->json([
            'message' => 'Link updated successfully',
            'data' => $link,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $link = $request->user()->links()
            ->where('id', $id)
            ->firstOrFail();

        $link->delete();

        return response()->json(null, 204);
    }
}
