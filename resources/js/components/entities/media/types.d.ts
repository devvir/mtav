export interface MediaUpdateForm {
  description: string;
  category: MediaCategory;
}

export interface MediaUploadForm extends MediaUpdateForm {
  files: Record<string, File>;
}

interface MediaConfig {
  supportedTypes: string[];
  validationMimeTypes: string[];
  maxFileSize: number;
  title: string;
  subtitle: string;
  dropText: string;
  supportText: string;
  buttonText: string;
  submitText: string;
  submittingText: string;
  validationMessage: string;
}

interface UploadOptions {
  category?: MediaCategory;
  storeRoute: string;
  redirectRoute: string;
}