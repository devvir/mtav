<?php

namespace App\Http\Controllers\Resources;

use App\Enums\MediaCategory;
use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $category = $request->get('category', MediaCategory::VISUAL->value);

        $media = currentProject()->media()
            ->category(MediaCategory::from($category))
            ->with('owner')
            ->latest()
            ->when($request->q, fn ($q, $search) => $q->search($search, searchOwner: true));

        return inertia('Media/Index', [
            'category' => $category,
            'media'    => Inertia::deepMerge(fn () => $media->paginate(30)),
            'q'        => $request->q ?? '',
        ]);
    }

    public function show(Media $media): Response
    {
        return inertia('Media/Show', [
            'media' => $media->load('owner', 'project'),
        ]);
    }

    public function create(Request $request): Response
    {
        return inertia('Media/Create', [
            'category' => $request->get('category', MediaCategory::VISUAL),
        ]);
    }

    public function store(StoreMediaRequest $request, MediaService $mediaService): RedirectResponse
    {
        $media = collect($request->file('files'))->map(
            fn (UploadedFile $file) => $mediaService->storeFile(
                file: $file,
                attributes: [
                    'description' => $request->description,
                    'owner_id'    => $request->user()->id,
                    'project_id'  => currentProject()->id,
                ]
            )
        );

        $flashMessage = ($media->count() === 1)
            ? __('flash.media_uploaded')
            : __('flash.media_multiple_uploaded', ['count' => $media->count()]);

        return to_route($this->redirectRoute())
            ->with('success', $flashMessage);
    }

    public function edit(Media $media): Response
    {
        return inertia('Media/Edit', [
            'media' => $media,
        ]);
    }

    public function update(UpdateMediaRequest $request, Media $media): RedirectResponse
    {
        $media->update($request->validated());

        return to_route($this->redirectRoute($media), $media)
            ->with('success', __('flash.media_updated'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        $media->delete(); // Soft delete

        return to_route($this->redirectRoute())
            ->with('success', __('flash.media_deleted'));
    }

    public function restore(Media $media): RedirectResponse
    {
        $media->restore();

        return to_route($this->redirectRoute($media), $media)
            ->with('success', __('flash.media_restored'));
    }

    protected function redirectRoute(?Media $media = null): string
    {
        $action = $media ? 'show' : 'index';
        $category = $media?->category ?? request('category', MediaCategory::VISUAL);

        return match ($category) {
            MediaCategory::AUDIO    => "audios.$action",
            MediaCategory::DOCUMENT => "documents.$action",
            default                 => "media.$action",
        };
    }
}
