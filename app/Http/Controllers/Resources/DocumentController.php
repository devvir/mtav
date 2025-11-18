<?php

namespace App\Http\Controllers\Resources;

use App\Enums\MediaCategory;
use App\Models\Media;
use Illuminate\Http\Request;

class DocumentController extends MediaController
{
    /**
     * Replace (not extend) the Resources\Controller constructor, to use
     * the right policy (MediaPolicy) and not an assumed DocumentPolicy.
     *
     * Documents are just a filtered view of the Media entity. They do
     * not implement their own policies, views or controller actions.
     */
    public function __construct(Request $request)
    {
        $this->authorizeResource(Media::class, 'media');

        $request->merge([
            'category' => MediaCategory::DOCUMENT->value,
        ]);
    }
}
