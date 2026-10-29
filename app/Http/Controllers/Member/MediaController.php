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
        $category = MediaCategory::tryFrom((string) $request->query('category')) ?? MediaCategory::MEDIA;

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
            // What may be shared, for the share dialog (?share=1) of those who may share
            'upload' => fn () => $request->user()->can('create', [Media::class, $project]) ? [
                'accept'    => MediaService::MIME_TYPES,
                'maxFileKb' => MediaService::maxFileKb($project),
                'maxFiles'  => MediaRequest::MAX_FILES,
            ] : null,
            // A file's details, shown over the page (?file=12)
            'file' => fn () => $request->integer('file') && ($file = $project->media()->find($request->integer('file')))
                ? MediaResource::make($file->load('owner'))
                : null,
        ]);
    }

    /** A file's details open over the media page: its address leads there */
    public function show(Project $project, Media $media): RedirectResponse
    {
        return to_route('media.index', [...$this->tab($media), 'file' => $media->id]);
    }

    public function store(MediaRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [Media::class, $project]);

        $stored = $this->media->store($project, $request->user(), $request->file('files'), $request->validated('description'));
        $category = count($stored) === 1 && $stored[0]->category === MediaCategory::DOCUMENT ? MediaCategory::DOCUMENT->value : null;

        return to_route('media.index', array_filter(['category' => $category]))
            ->with('success', trans_choice('flash.media_uploaded', count($stored), ['count' => count($stored)]));
    }

    public function update(MediaRequest $request, Project $project, Media $media): RedirectResponse
    {
        $this->authorize('update', $media);

        $media->update($request->safe()->only('description', 'alt_text'));

        return to_route('media.index', [...$this->tab($media), 'file' => $media->id])
            ->with('success', __('flash.media_updated'));
    }

    public function destroy(Project $project, Media $media): RedirectResponse
    {
        $this->authorize('delete', $media);

        $category = $media->category === MediaCategory::DOCUMENT ? MediaCategory::DOCUMENT->value : null;

        $this->media->delete($media);

        return to_route('media.index', array_filter(['category' => $category]))
            ->with('success', __('flash.media_deleted'));
    }

    /** The tab a file is listed under (documents apart) */
    private function tab(Media $media): array
    {
        return $media->category === MediaCategory::DOCUMENT ? ['category' => MediaCategory::DOCUMENT->value] : [];
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
