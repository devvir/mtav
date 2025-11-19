import { _ } from '@/composables/useTranslations';
import type { MediaConfig } from './types';

export const getMediaConfig = (category: MediaCategory): MediaConfig => {
  switch (category) {
    case 'visual':
      return {
        supportedTypes: ['image/*', 'video/*'],
        validationMimeTypes: [
          'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
          'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov', 'video/quicktime'
        ],
        maxFileSize: 50 * 1024 * 1024, // 50MB for videos
        title: _('Upload Media'),
        subtitle: _('Share photos and videos with your community'),
        dropText: _('Drop images or videos here or click to browse'),
        supportText: _('Images and videos up to 50MB each'),
        buttonText: _('Choose Media'),
        submitText: _('Upload Media'),
        submittingText: _('Uploading media...'),
        validationMessage: _('Unsupported file type. Please use images or videos.')
      };

    case 'image':
      return {
        supportedTypes: ['image/*'],
        validationMimeTypes: [
          'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'
        ],
        maxFileSize: 10 * 1024 * 1024, // 10MB
        title: _('Upload Images'),
        subtitle: _('Share photos and visual content with your community'),
        dropText: _('Drop images here or click to browse'),
        supportText: _('JPG, PNG, GIF, WebP, SVG up to 10MB each'),
        buttonText: _('Choose Images'),
        submitText: _('Upload Images'),
        submittingText: _('Uploading images...'),
        validationMessage: _('Unsupported file type. Please use image files.')
      };

    case 'video':
      return {
        supportedTypes: ['video/*'],
        validationMimeTypes: [
          'video/mp4', 'video/webm', 'video/ogg', 'video/avi', 'video/mov', 'video/quicktime'
        ],
        maxFileSize: 100 * 1024 * 1024, // 100MB
        title: _('Upload Videos'),
        subtitle: _('Share video content with your community'),
        dropText: _('Drop videos here or click to browse'),
        supportText: _('MP4, WebM, AVI, MOV up to 100MB each'),
        buttonText: _('Choose Videos'),
        submitText: _('Upload Videos'),
        submittingText: _('Uploading videos...'),
        validationMessage: _('Unsupported file type. Please use video files.')
      };

    case 'audio':
      return {
        supportedTypes: ['audio/*'],
        validationMimeTypes: [
          'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg', 'audio/aac', 'audio/flac', 'audio/m4a'
        ],
        maxFileSize: 50 * 1024 * 1024, // 50MB
        title: _('Upload Audio'),
        subtitle: _('Share audio files and recordings with your community'),
        dropText: _('Drop audio files here or click to browse'),
        supportText: _('MP3, WAV, OGG, AAC, FLAC up to 50MB each'),
        buttonText: _('Choose Audio'),
        submitText: _('Upload Audio'),
        submittingText: _('Uploading audio...'),
        validationMessage: _('Unsupported file type. Please use audio files.')
      };

    case 'document':
      return {
        supportedTypes: [
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'application/vnd.ms-powerpoint',
          'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'application/vnd.oasis.opendocument.text',
          'application/vnd.oasis.opendocument.spreadsheet',
          'application/vnd.oasis.opendocument.presentation',
          'text/plain',
          'text/csv'
        ],
        validationMimeTypes: [
          'application/pdf',
          'application/msword',
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          'application/vnd.ms-excel',
          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
          'application/vnd.ms-powerpoint',
          'application/vnd.openxmlformats-officedocument.presentationml.presentation',
          'application/vnd.oasis.opendocument.text',
          'application/vnd.oasis.opendocument.spreadsheet',
          'application/vnd.oasis.opendocument.presentation',
          'text/plain',
          'text/csv'
        ],
        maxFileSize: 25 * 1024 * 1024, // 25MB
        title: _('Upload Documents'),
        subtitle: _('Share documents and files with your community'),
        dropText: _('Drop documents here or click to browse'),
        supportText: _('PDF, Word, Excel, PowerPoint, LibreOffice, text files up to 25MB each'),
        buttonText: _('Choose Documents'),
        submitText: _('Upload Documents'),
        submittingText: _('Uploading documents...'),
        validationMessage: _('Unsupported file type. Please use document files.')
      };

    case 'unknown':
    default:
      return {
        supportedTypes: ['*/*'],
        validationMimeTypes: [], // Backend will handle validation
        maxFileSize: 25 * 1024 * 1024, // 25MB
        title: _('Upload Files'),
        subtitle: _('Upload any file type'),
        dropText: _('Drop files here or click to browse'),
        supportText: _('Any file type up to 25MB each'),
        buttonText: _('Choose Files'),
        submitText: _('Upload Files'),
        submittingText: _('Uploading files...'),
        validationMessage: _('File validation will be performed server-side.')
      };
  }
};