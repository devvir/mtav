# Copilot - Pending review
# Media Seeding Samples

This directory contains sample media files used for database seeding in development.

## Setup

Before running the seeder, download the sample files:

```bash
./scripts/download-media-samples.sh
```

This will download:
- **40 images** from Picsum Photos (various dimensions)
- **3 video samples** (MP4, WebM formats)
- **5 document samples** (PDF, TXT, CSV, Markdown)
- **4 audio samples** (MP3, WAV formats - generated with ffmpeg)

## Structure

```
storage/seeders/media/
├── images/       # JPG images from Picsum
├── videos/       # video files
├── documents/    # misc document files
└── audio/        # audio files
```

## How Seeding Works

- MediaSeeder copies all samples from `storage/seeders/media/` to `storage/app/public/media/`
- For each project, randomly picks 15-50 media files from the available samples
- Creates Media models with correct attributes (path, dimensions, mime type, etc.)

## Important Notes

- The same sample files are reused across multiple Media records (different projects/descriptions)
- Files are copied to public storage during seeding, making them accessible via the web
