# Media System - MTAV

**Last Updated:** 2025-12-25

This document describes how media (images, videos, audio, documents) works in MTAV, covering storage, backend architecture, frontend implementation, and user-facing concepts.

---

## Overview

The media system supports **4 main categories** plus a special grouping:

1. **IMAGE** - Photos, pictures (JPEG, PNG, GIF, WebP, SVG)
2. **VIDEO** - Video files (MP4, WebM, AVI, MOV)
3. **AUDIO** - Audio files (MP3, WAV, OGG) - seeded in development, not currently viewable in UI
4. **DOCUMENT** - Office documents & PDFs (Word, Excel, PowerPoint, PDF, text files)
5. **VISUAL** - Special query-time grouping that combines IMAGE + VIDEO (not a real database category)

### Current UI State

- **Gallery Page** (`/gallery` route): Shows VISUAL media (images + videos) in large card grid layout.
- **Documents Page**: Separate page showing documents in list layout with file metadata and action buttons.
- **Audio**: Seeded with audio samples but not currently viewable in UI.

---

## Storage Architecture

**Location**: Files stored in Laravel's public disk under `storage/app/public/media/`

**Naming Convention**: UUID-based filenames for uniqueness:
```
550e8400-e29b-41d4-a716-446655440000.jpg
```

**Thumbnails**: Every media file automatically gets a thumbnail via `MediaThumbnailService`:
- **Images**: Resized version
- **Videos**: Frame extraction via FFMpeg
- **Audio/Documents**: Generated placeholder SVG with category-specific color

**File Size Limits**:
- Images: 10MB
- Videos: 100MB
- Audio: 50MB
- Documents: 25MB

**Upload Validation**: Max 100MB per file, specific MIME types validated server-side in `StoreMediaRequest`

---

## Database Schema

**Migration**: `database/migrations/2025_11_14_172313_create_media_table.php`

```sql
media:
  - id
  - owner_id         # User who uploaded (foreign key to users)
  - project_id       # Project context (foreign key to projects, auto-scoped)
  - path             # Relative storage path (e.g., 'media/uuid.jpg')
  - thumbnail        # Generated thumbnail path
  - description      # Required, 2-255 chars
  - alt_text         # Optional, for accessibility
  - width            # Pixels, extracted for images/videos
  - height           # Pixels, extracted for images/videos
  - category         # Enum: image|video|audio|document|unknown|visual
  - mime_type        # Auto-detected from file
  - file_size        # Bytes
  - timestamps
  - soft_deletes     # Soft deletion support
```

**Indexes**: `category` column is indexed for efficient filtering.

---

## Backend Architecture

### Model: `App\Models\Media`

**File**: `app/Models/Media.php`

**Traits**:
- `ProjectScope` - Automatically scoped to current project
- `SoftDeletes` - Soft deletion support

**Relationships**:
```php
belongsTo(User::class, 'owner')    // Who uploaded it
belongsTo(Project::class)          // Which project
```

**Query Scopes**:
```php
->audios()          // WHERE category = 'audio'
->documents()       // WHERE category = 'document'
->images()          // WHERE category = 'image'
->videos()          // WHERE category = 'video'
->visual()          // WHERE category IN ('image', 'video')
->category(MediaCategory $cat)  // Dynamic scope
->search(string $q, bool $searchOwner = false)  // Search description and optionally owner name
```

**Helper Methods**:
```php
isImage(): bool
isVideo(): bool
isAudio(): bool
isDocument(): bool
isVisual(): bool    // Returns true for images OR videos
```

### Enum: `App\Enums\MediaCategory`

**File**: `app/Enums/MediaCategory.php`

```php
enum MediaCategory: string {
    case AUDIO = 'audio';
    case DOCUMENT = 'document';
    case IMAGE = 'image';
    case UNKNOWN = 'unknown';
    case VIDEO = 'video';
    case VISUAL = 'visual';  // Query-time grouping, not stored
}
```

**Methods**:
- `label()` - Returns translated label (e.g., "Media" for VISUAL)
- `labels()` - Returns all labels as array

### HasMedia Trait (Model Concern)

**File**: `app/Models/Concerns/HasMedia.php`

**Used by**: `Project`, `Family`, `User` models

**Provides relationships**:
```php
->media()          // morphMany(Media::class)
->audios()         // ->media()->audios()
->documents()      // ->media()->documents()
->images()         // ->media()->images()
->videos()         // ->media()->videos()
->visualMedia()    // ->media()->visual()
```

**Usage Example**:
```php
$project = Project::find(1);
$project->images; // All images for this project
$project->visualMedia; // All images + videos
```

### Services

#### MediaService

**File**: `app/Services/MediaService.php`

**Key Methods**:

```php
// Main upload handler - creates Media record + stores file + generates thumbnail
create(UploadedFile $file, array $attributes = []): Media

// Extract metadata from file (dimensions, category)
extractFileMetadata(UploadedFile $file): array

// Get image dimensions (width, height)
getImageDimensions(UploadedFile $file): array

// Get video dimensions via FFMpeg
getVideoDimensions(UploadedFile $file): array

// Map MIME type to MediaCategory enum
mime2category(string $mimeType): MediaCategory

// Force delete media record + file + thumbnail
forceDeleteMedia(Media $media): bool
```

**Workflow** (`create()` method):
1. Generate UUID-based filename
2. Extract metadata (category, dimensions)
3. Store file to public disk
4. Create Media record
5. Generate thumbnail via `MediaThumbnailService`
6. Save and return Media model

#### MediaThumbnailService

**File**: `app/Services/MediaThumbnailService.php`

Generates thumbnails for all media types (resizes images, extracts video frames, creates SVG placeholders for audio/documents).

### Controller: `App\Http\Controllers\Resources\MediaController`

**File**: `app/Http/Controllers/Resources/MediaController.php`

**Routes** (from `routes/web/project.php`):
```php
Route::get('gallery', [MediaController::class, 'index'])->name('gallery');
Route::resource('media', MediaController::class);
```

**Actions**:

| Method | Route | Action | Description |
|--------|-------|--------|-------------|
| GET | `/gallery?category=visual` | `index()` | Paginated gallery (30/page), filtered by category (default: VISUAL) |
| GET | `/media/{media}` | `show()` | Single media detail view |
| GET | `/media/create?category=visual` | `create()` | Upload form |
| POST | `/media` | `store()` | Multi-file upload handler |
| GET | `/media/{media}/edit` | `edit()` | Edit form (description, category) |
| PATCH | `/media/{media}` | `update()` | Update description/category |
| DELETE | `/media/{media}` | `destroy()` | Soft delete |
| POST | `/media/{media}/restore` | `restore()` | Restore soft-deleted |

**Index Query Pattern**:
```php
currentProject()->media()
    ->category(MediaCategory::from($category))  // Filter by category
    ->with('owner')                             // Eager load owner
    ->latest()                                   // Order by created_at DESC
    ->when($request->q, fn($q) => $q->search()) // Optional search
    ->paginate(30)
```

**Store Multi-File Upload**:
```php
$media = collect($request->file('files'))->map(
    fn(UploadedFile $file) => $mediaService->create(
        file: $file,
        attributes: [
            'description' => $request->description,  // Shared description
            'owner_id'    => $request->user()->id,
            'project_id'  => currentProject()->id,
        ]
    )
);
```

### Policy: `App\Policies\MediaPolicy`

**File**: `app/Policies/MediaPolicy.php`

| Action | Rule | Who Can |
|--------|------|---------|
| `viewAny()` | `true` | Everyone (within project scope) |
| `view()` | `true` | Everyone (within project scope) |
| `create()` | `true` | Everyone (members can upload) |
| `update()` | `$user->id === $media->owner_id` | Owner only |
| `delete()` | `$user->id === $media->owner_id \|\| $user->isAdmin()` | Owner OR Admin |
| `restore()` | `$user->id === $media->owner_id \|\| $user->isAdmin()` | Owner OR Admin |

**Note**: Superadmins bypass all policies via `Gate::before()` in `AppServiceProvider`.

### Form Requests

#### StoreMediaRequest

**File**: `app/Http/Requests/StoreMediaRequest.php`

**Validation Rules**:
```php
[
    'description' => 'required|string|between:2,255',
    'files'       => 'required|array|min:1',
    'files.*'     => [
        'file',
        'max:102400', // 100MB in kilobytes
        'mimes:jpeg,jpg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,mp3,wav,mp4,avi,mov,webm',
    ],
]
```

**Custom Messages** (from `lang/en/validation.php`):
- `media_file_required` - "Please select a file to upload."
- `media_file_too_large` - "The file must not be larger than 10MB."
- `media_invalid_file_type` - "The file must be a valid image or document type."

#### UpdateMediaRequest

**File**: `app/Http/Requests/UpdateMediaRequest.php`

Validates description and category changes only (no file re-upload).

### Resource: `App\Http\Resources\MediaResource`

**File**: `app/Http/Resources/MediaResource.php`

**Auto-transformation**: Media models automatically convert to MediaResource when sent to frontend (via `ConvertsToJsonResource` trait on base `Model` class).

**Frontend payload**:
```typescript
{
  // Common resource data (id, created_at, created_ago, updated_at, deleted_at, can{})
  path: string,           // Relative storage path
  url: string,            // Full URL (mock for dev files)
  thumbnail: string,      // Thumbnail URL (mock for dev files)
  description: string,
  alt_text: string | null,
  width: number | null,   // Pixels
  height: number | null,  // Pixels
  category: string,       // 'image' | 'video' | 'audio' | 'document'
  category_label: string, // Translated label
  mime_type: string,
  file_size: number,      // Bytes
  is_image: boolean,

  owner: { id: number } | ApiResource<User>,
  project: { id: number } | ApiResource<Project>,
}
```

**Dev File Handling**: Files with `dev-` prefix (from seeders) get mock images:
- Visual media: Picsum Photos placeholder
- Others: SVG with category-specific color

### HasMedia Resource Concern

**File**: `app/Http/Resources/Concerns/HasMedia.php`

**Used by**: Resources that have media (ProjectResource, FamilyResource, UserResource)

**Provides**:
```php
sharedMediaData(): array // Returns all media relations + counts
deriveSubMediaRelations() // Auto-derives category-specific relations from 'media'
```

**Frontend payload additions**:
```typescript
{
  media?: ApiResource<Media>[],
  media_count?: number,

  audios?: ApiResource<Media>[],
  audios_count?: number,

  documents?: ApiResource<Media>[],
  documents_count?: number,

  images?: ApiResource<Media>[],
  images_count?: number,

  videos?: ApiResource<Media>[],
  videos_count?: number,

  visual_media?: ApiResource<Media>[],
  visual_media_count?: number,
}
```

**Auto-derivation**: If `media` relation is loaded, category-specific relations are automatically derived without additional queries:
```php
// In resource:
$this->resource->load('media'); // Loads all media

// Frontend automatically gets:
// - images (filtered from media)
// - videos (filtered from media)
// - audios (filtered from media)
// - documents (filtered from media)
// - visual_media (filtered from media)
```

---

## Frontend Architecture

### Pages

**Location**: `resources/js/pages/Media/`

#### Index.vue (Gallery Page)

**Route**: `/gallery`

**Current behavior**: Shows media in grid layout, 30 items per page.

**Implementation**: Simple 10-line component that passes props to `IndexPage` with `cardSize="xl"` for large thumbnails.

```vue
<template>
  <IndexPage :pageTitle="_('Gallery')" entity="media" :resources="media" cardSize="xl" />
</template>
```

#### Show.vue (Detail View)

**Route**: `/media/{media}`

**Implementation**: Uses reusable `ShowPage` component, renders in modal overlay with blurred backdrop.

**Modal sizing**:
- Images: Dynamically sized based on aspect ratio
- Videos: Fixed layout with header
- Others: Fallback display

#### Create.vue (Upload Page)

**Route**: `/media/create?category=visual`

**Implementation**: Modal with `UploadForm` component, supports drag & drop + file picker, multi-file upload.

#### Edit.vue (Edit Page)

**Route**: `/media/{media}/edit`

**Implementation**: Modal with `EditForm` component, allows editing description and category (no file re-upload).

### Components

**Location**: `resources/js/components/entities/media/`

#### Display Components

**IndexCard.vue** - Media card delegator:
- Conditionally renders `DocumentIndexCard` or `VisualIndexCard` based on `media.category`
- No logic about pages or UI structure - just delegates based on the media item itself

**VisualIndexCard.vue** - Visual media (images/videos) card:
- Fixed aspect-ratio container
- Thumbnail image with hover scale effect
- Bottom gradient overlay with description
- Owner name shown on hover
- Truncates description when not hovering

**DocumentIndexCard.vue** - Document media card:
- Horizontal list layout with icon/thumbnail
- File metadata (category, size, owner)
- Preview/download action buttons
- Text preview for supported formats (txt, csv, md)

**ShowCard.vue** - Full media viewer:
- Routes to appropriate display component (ImageDisplay, VideoPlayer, MediaFallback)
- CSS custom properties for responsive sizing based on media dimensions
- Action buttons (edit, delete) in top-left for images
- Video header with metadata

**image/ImageDisplay.vue** - Full-size image display with zoom support

**video/VideoPlayer.vue** - HTML5 video player with controls

**MediaFallback.vue** - Placeholder for audio/documents (non-visual media)

**MediaDescription.vue** - Description overlay component for media viewer

#### Upload Components

**Location**: `resources/js/components/entities/media/upload/`

**UploadForm.vue** - Main upload form:
```vue
<template>
  <UploadHeader :category />
  <FileUpload v-model="form.files" :category />
  <Description v-model="form.description" />
  <ProgressBar :form />
  <FormErrors :form />
  <FormActions :form @submit="uploadFiles" @cancel="emit('cancel')" />
  <FileList v-model="form.files" :form />
</template>
```

**FileUpload.vue** - Drag & drop zone:
- Accepts multiple files
- File type validation (client-side)
- Size validation (client-side)
- Shows drop zone highlight when dragging
- File dialog fallback

**FileList.vue** - Selected files display:
- Shows all selected files before upload
- Individual file remove button
- Upload progress per file

**FileItem.vue** - Individual file preview:
- File name, size, type
- Thumbnail preview if image
- Progress bar during upload
- Error state display

**ProgressBar.vue** - Overall upload progress indicator

#### Edit Components

**Location**: `resources/js/components/entities/media/update/`

**EditForm.vue** - Edit description/category form

**EditPreview.vue** - Shows current media while editing

#### Shared Components

**Location**: `resources/js/components/entities/media/shared/`

Reusable form components:
- `Description.vue` - Description textarea field
- `FormActions.vue` - Submit/Cancel buttons
- `FormErrors.vue` - Error message display
- `FormHeader.vue` - Form title/subtitle
- `MediaDescription.vue` - Description overlay on media
- `MediaFallback.vue` - Non-visual media placeholder

### Composable: useMedia

**File**: `resources/js/components/entities/media/useMedia.ts`

**Export**: `getMediaConfig(category: MediaCategory): MediaConfig`

Returns category-specific configuration for upload forms:

```typescript
interface MediaConfig {
  supportedTypes: string[];          // File input accept types
  validationMimeTypes: string[];     // Client-side validation
  maxFileSize: number;               // Bytes
  title: string;                     // Form title
  subtitle: string;                  // Form subtitle
  dropText: string;                  // Drag & drop prompt
  supportText: string;               // Supported formats text
  buttonText: string;                // File picker button
  submitText: string;                // Submit button
  submittingText: string;            // Submit button (processing)
  validationMessage: string;         // Error message for invalid type
}
```

**Example** (VISUAL category):
```typescript
{
  supportedTypes: ['image/*', 'video/*'],
  validationMimeTypes: [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov', 'video/quicktime'
  ],
  maxFileSize: 50 * 1024 * 1024, // 50MB
  title: _('Upload Media'),
  subtitle: _('Share photos and videos with your community'),
  dropText: _('Drop images or videos here or click to browse'),
  supportText: _('Images and videos up to 50MB each'),
  buttonText: _('Choose Media'),
  submitText: _('Upload Media'),
  submittingText: _('Uploading media...'),
  validationMessage: _('Unsupported file type. Please use images or videos.')
}
```

### TypeScript Types

**File**: `resources/js/types/index.d.ts`

```typescript
interface Media extends Resource {
  path: string;
  url: string;
  description: string;
  alt_text: string | null;
  width: number | null;
  height: number | null;
  category: string;          // 'image' | 'video' | 'audio' | 'document'
  category_label: string;    // Translated label
  mime_type: string;
  file_size: number;
  is_image: boolean;

  owner: { id: number } | ApiResource<User>;
  project: { id: number } | ApiResource<Project>;
}

interface HasMedia {
  media?: ApiResource<Media>[];
  media_count?: number;

  audios?: ApiResource<Media>[];
  audios_count?: number;

  documents?: ApiResource<Media>[];
  documents_count?: number;

  images?: ApiResource<Media>[];
  images_count?: number;

  videos?: ApiResource<Media>[];
  videos_count?: number;

  visual_media?: ApiResource<Media>[];
  visual_media_count?: number;
}

// Form types
interface MediaUpdateForm {
  description: string;
  category: MediaCategory;
}

interface MediaUploadForm extends MediaUpdateForm {
  files: Record<string, File>; // Object-based for reactivity
}
```

---

## User-Facing Concept

**From user perspective** (based on member guide):

### Gallery (Visual Media)

- Main navigation shows "Gallery" link
- Displays photos and videos in grid layout
- Users can:
  - Upload photos/videos via drag & drop or file picker
  - Add shared description for all files in one upload
  - View full-size media in modal
  - Edit description (if owner)
  - Delete media (if owner or admin)

**Use cases** (from guide):
- Construction progress photos
- Community event photos/videos
- Cooperative activity documentation

**Guidelines** (from guide):
- Only upload appropriate content related to project
- Be respectful of community members in photos/videos
- Ensure adequate quality for viewing

### Documents

- Separate "Documents" page (not in Gallery)
- **Current issues** (to be fixed):
  - Icons too large
  - Needs preview text where appropriate
  - Should force-download on click (not navigate)

### Audio

- Not currently viewable in UI
- Low priority for implementation

---

## Key Design Patterns

### 1. Automatic Resource Transformation

Media models auto-convert to MediaResource when sent to frontend:
```php
// Controller
return inertia('Media/Show', [
    'media' => $media, // Automatically becomes MediaResource
]);
```

**Never use**: `JsonResource::make()`, `JsonResource::collection()`, `$model->toResource()` - this is automatic.

### 2. Project-Based Scoping

All media queries automatically scoped to current project:
```php
Media::all(); // Only media for currentProject()
```

**Global scope applied via**: `ProjectScope` trait on Media model.

### 3. Multi-File Upload with Shared Description

Single form submission uploads multiple files, each becoming separate Media record with same description:
```php
// Single POST request with:
{
  description: "Construction progress - Week 5",
  files: [file1, file2, file3] // All get same description
}
```

### 4. Category Abstraction: VISUAL

**Backend**: Individual categories (IMAGE, VIDEO) stored in database

**Frontend**: Can query VISUAL grouping (IMAGE + VIDEO together) for gallery view

**Why**: Simplifies most common use case (photo/video gallery) while maintaining granular categorization

**Implementation**:
```php
// Query scope combines categories
public function scopeVisual(Builder $query): void {
    $query->whereIn('category', [MediaCategory::IMAGE, MediaCategory::VIDEO]);
}
```

### 5. Auto-Derived Relations

Resources automatically derive category-specific relations from loaded `media` relation without additional queries:
```php
// Load once
$project->load('media');

// Frontend automatically gets:
// - images (filtered client-side from media)
// - videos (filtered client-side from media)
// - documents (filtered client-side from media)
// Without N+1 queries
```

### 6. Dev File Handling

Files with `dev-` prefix (from seeders) automatically get mock images from Picsum Photos:
```php
// In MediaResource
protected function path2url(string $path): string {
    if (str_starts_with(basename($path), 'dev-')) {
        return $this->getMockImageUrl(); // Picsum Photos
    }
    return Storage::url($path);
}
```

**Why**: Development/testing doesn't require actual image files stored in repo.

### 7. Responsive Media Display

ShowCard uses CSS custom properties for responsive sizing based on media dimensions:
```vue
<CardContent
  :style="[
    `--m-ratio: min(calc(90vh/${media.height}),calc(90vw/${media.width}))`,
    `--m-width: calc(var(--m-ratio) * ${media.width})`,
    `--m-height: calc(var(--m-ratio) * ${media.height})`,
  ]"
  class="w-[var(--m-width)] h-[var(--m-height)]"
>
```

**Result**: Media fits within viewport while maintaining aspect ratio.

---

## Known Issues & TODO

### Gallery

- [ ] **Add category filtering**: Add tabs to filter by IMAGE vs VIDEO
- [ ] **File upload reactivity**: Files added don't show immediately in UI (Vue reactivity issue with File objects)

### Documents Page

- [x] ~~Reduce icon size~~ - Fixed with DocumentIndexCard
- [x] ~~Add preview text~~ - Fixed with text preview for supported formats
- [x] ~~Force download~~ - Fixed with download button

### Avatar Upload (High Priority)

- [ ] **Fix upload functionality**: Profile avatar upload failing
- [ ] **Fix UI shift**: Avatar component shifts when error displayed

### Audio (Low Priority)

- [ ] Not currently viewable in UI (seeded but no display component)
- [ ] Create audio player component when needed

---

## Testing Patterns

### Factory: MediaFactory

**TODO**: Document media factory patterns when created.

### Seeder: MediaSeeder

**File**: `database/seeders/MediaSeeder.php`

**Workflow**:
1. Copies sample files from `storage/seeders/media/` to `storage/app/public/media/`
2. Loads file metadata (dimensions, mime types, file sizes)
3. Creates 15-50 random media records per project
4. Generates thumbnails for each media item

**Sample Media Location**: `storage/seeders/media/`
- `images/` - 40 sample images (various dimensions)
- `videos/` - 10 sample videos (MP4, WebM formats)
- `documents/` - 6 sample documents (PDF, TXT, CSV, MD)
- `audio/` - 4 sample audio files (MP3, WAV with sine wave tones)

**Download Script**: `scripts/download-media-samples.sh`

**Usage**: Run `./scripts/download-media-samples.sh` from project root

**Features**:
- Downloads 40 images from Picsum Photos (various dimensions)
- Downloads 4 video samples from test-videos.co.uk
- Splits 1 long video into 6 additional 10-second clips using ffmpeg
- Generates 4 audio files with sine wave tones (440Hz, 523Hz, 330Hz, 220Hz)
- Creates 6 sample documents (PDF, TXT, CSV, MD)
- Only downloads/generates missing files (checks existence first)
- Shows summary of downloaded files

**Audio Generation**: Uses ffmpeg to generate MP3/WAV files with audible sine wave tones at different frequencies (not silent audio).

### Universe Fixture

**TODO**: Document media test data in `tests/Fixtures/universe.sql` when added.

---

## Common Tasks

### Add Media to Model

1. Add `HasMedia` trait to model:
```php
use App\Models\Concerns\HasMedia;

class Event extends Model {
    use HasMedia;

    public function media(): MorphMany {
        return $this->morphMany(Media::class, 'mediable');
    }
}
```

2. Add `HasMedia` resource concern to resource:
```php
use App\Http\Resources\Concerns\HasMedia;

class EventResource extends JsonResource {
    use HasMedia;

    public function toArray(Request $_): array {
        return [
            // ...
            ...$this->sharedMediaData(),
        ];
    }
}
```

### Query Media

```php
// All media for current project
Media::all();

// Only images
Media::images()->get();

// Visual media (images + videos)
Media::visual()->get();

// Search media
Media::search('construction')->get();

// With owner
Media::with('owner')->images()->get();
```

### Upload Media Programmatically

```php
use App\Services\MediaService;

$mediaService = app(MediaService::class);

$media = $mediaService->create(
    file: $uploadedFile,
    attributes: [
        'description' => 'Progress photo',
        'owner_id'    => auth()->id(),
        'project_id'  => currentProject()->id,
    ]
);
```

### Get Media URLs in Blade

```php
// Full URL
{{ Storage::url($media->path) }}

// Thumbnail URL
{{ Storage::url($media->thumbnail) }}
```

### Frontend: Display Media

```vue
<template>
  <img
    :src="media.url"
    :alt="media.alt_text || media.description"
    :width="media.width"
    :height="media.height"
  />
</template>
```

### Frontend: Upload Media

```vue
<script setup>
import { UploadForm } from '@/components/entities/media';

const category = 'visual'; // or 'image', 'video', 'audio', 'document'
</script>

<template>
  <UploadForm
    :category
    @submit="handleUploadComplete"
    @cancel="handleCancel"
  />
</template>
```

---

## Related Documentation

- **KNOWLEDGE_BASE.md** - Core architectural principles, resource transformation
- **UI.md** - Frontend architecture, component patterns
- **resources-reference.md** - Resource transformation patterns
- **policies-reference.md** - Authorization patterns
