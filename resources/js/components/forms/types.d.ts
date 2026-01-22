export type ValueType = string | number | boolean | null | undefined;

export interface CommonElementSpecs {
  label: string;
  labelPrefix?: string;
  required?: boolean;
  disabled?: boolean;
}

export interface HiddenInputSpecs {
  element: 'input';
  type: 'hidden';
  value?: string | number | boolean | null;
}

export type InputTypes =
  | 'text'
  | 'email'
  | 'password'
  | 'number'
  | 'file'
  | 'radio'
  | 'checkbox'
  | 'hidden'
  | 'color'
  | 'date'
  | 'datetime-local'
  | 'time'
  | 'range';

export interface InputSpecs extends CommonElementSpecs {
  element: 'input';
  type?: InputTypes;
  placeholder?: string;
  autocomplete?: boolean;
  min?: number;
  max?: number;
  minlength?: number;
  maxLength?: number;
  value?: string | number | boolean | null;
}

export type SelectOptions = { [key: string | number]: string | number };
export type FilteredSelectOptions = { [parentId: string | number]: SelectOptions };

export interface SelectAddOption {
  target: string;
  legend: string;
}

export interface CommonSelectSpecs extends CommonElementSpecs {
  element: 'select';
  displayId?: boolean;
  create?: SelectAddOption;
  placeholder?: string;
  hidden?: boolean;
}

export interface SingleSelectSpecs extends CommonSelectSpecs {
  multiple?: false;
  selected?: string | number | null;
}

export interface MultipleSelectSpecs extends CommonSelectSpecs {
  multiple: true;
  selected?: string[] | number[] | [];
}

export interface UnfiteredOptionsSpecs {
  options: SelectOptions;
  filteredBy?: undefined | null;
}

export interface FilteredOptionsSpecs {
  options: FilteredSelectOptions;
  filteredBy: string;
}

export type SelectSpecs = (SingleSelectSpecs | MultipleSelectSpecs) &
  (FilteredOptionsSpecs | UnfiteredOptionsSpecs);

export type ElementSpecs = InputSpecs | HiddenInputSpecs | SelectSpecs;

export type FormSpecs = Record<string, ElementSpecs>;

export type FormType = 'create' | 'edit';

export type FormUpdateEvent = { field: string; value: ValueType | ValueType[] };

export interface FormServiceData {
  type: FormType;
  action: [string, number | string | null];
  title: string;
  specs: FormSpecs;
}
