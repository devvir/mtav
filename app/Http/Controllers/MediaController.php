<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilteredIndexRequest;
use App\Http\Requests\StoreMediaRequest;
use App\Http\Requests\UpdateMediaRequest;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(FilteredIndexRequest $request): Response
    {
        $media = currentProject()->media()
            ->with('owner')
            ->latest()
            ->when($request->q, fn ($q, $search) => $q->search($search, searchOwner: true));

        return inertia('Media/Index', [
            'media' => Inertia::deepMerge(fn () => $media->paginate(30)),
            'q'     => $request->q ?? '',
        ]);
    }

    public function show(Media $media): Response
    {
        return inertia('Media/Show', [
            'media' => $media->load('owner', 'project'),
        ]);
    }

    public function create(): Response
    {
        return inertia('Media/Create');
    }

    public function store(StoreMediaRequest $request, MediaService $mediaService): RedirectResponse
    {
        $media = $mediaService->storeFile(
            file: $request->file('file'),
            attributes: [
                'description' => $request->description,
                'owner_id'    => Auth::id(),
                'project_id'  => currentProject()->id,
            ]
        );

        return to_route('media.show', $media)
            ->with('success', __('flash.media_uploaded'));
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

        return to_route('media.show', $media)
            ->with('success', __('flash.media_updated'));
    }

    public function destroy(Media $media): RedirectResponse
    {
        $media->delete(); // Soft delete

        return to_route('media.index')
            ->with('success', __('flash.media_deleted'));
    }

    public function restore(Media $media): RedirectResponse
    {
        $media->restore();

        return to_route('media.show', $media)
            ->with('success', __('flash.media_restored'));
    }
}
