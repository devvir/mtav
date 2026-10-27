<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Enums\MediaCategory;
use App\Http\Requests\MediaRequest;
use App\Http\Resources\MediaResource;
use App\Models\Media;
use App\Models\Project;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Files shared in a project: the gallery (images and videos), audio and documents.
 */
class MediaController extends Controller
{
    public function __construct(private MediaService $media)
    {
    }

    public function index(Request $request, Project $project): Response
    {
        $category = MediaCategory::tryFrom((string) $request->query('category')) ?? MediaCategory::VISUAL;

        $media = $project->media()
            ->category($category)
            ->with('owner:id,firstname,lastname')
            ->when($request->q, fn ($q, $search) => $q->search($search))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return inertia('Member/Media/Index', [
            'media'    => MediaResource::collection($media),
            'category' => $category->value,
            'filters'  => ['q' => $request->q],
            'storage'  => $this->storage($project),
        ]);
    }

    public function show(Project $project, Media $media): Response
    {
        return inertia('Member/Media/Show', ['media' => MediaResource::make($media->load('owner'))]);
    }

    public function create(Request $request, Project $project): Response
    {
        $this->authorize('create', [Media::class, $project]);

        return inertia('Member/Media/Create', [
            'category'  => $request->query('category'),
            'accept'    => MediaService::MIME_TYPES,
            'maxFileKb' => MediaService::maxFileKb($project),
            'maxFiles'  => MediaRequest::MAX_FILES,
            'storage'   => $this->storage($project),
        ]);
    }

    public function store(MediaRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Media::class, $project]);

        $stored = $this->media->store($project, $request->user(), $request->file('files'), $request->validated('description'));
        $category = count($stored) === 1 && ! $stored[0]->isVisual() ? $stored[0]->category->value : null;

        return to_route('media.index', array_filter(['category' => $category]))
            ->with('success', trans_choice('flash.media_uploaded', count($stored), ['count' => count($stored)]));
    }

    public function edit(Project $project, Media $media): Response
    {
        $this->authorize('update', $media);

        return inertia('Member/Media/Edit', ['media' => MediaResource::make($media)]);
    }

    public function update(MediaRequest $request, Project $project, Media $media): RedirectResponse
    {
        $this->authorize('update', $media);

        $media->update($request->safe()->only('description', 'alt_text'));

        return to_route('media.show', $media)->with('success', __('flash.media_updated'));
    }

    public function destroy(Project $project, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $category = $media->isVisual() ? null : $media->category->value;

        $this->media->delete($media);

        return to_route('media.index', array_filter(['category' => $category]))
            ->with('success', __('flash.media_deleted'));
    }

    /**
     * How much of its storage an unapproved project uses (null: no limit).
     */
    private function storage(Project $project): ?array
    {
        $limit = $project->limit('media_storage_mb');

        return $limit === null ? null : [
            'used_mb'  => round($project->media()->sum('file_size') / 1024 / 1024, 1),
            'limit_mb' => $limit,
        ];
    }
}
