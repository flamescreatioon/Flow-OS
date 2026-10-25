import { ApiError } from '@/lib/api';

export interface FormErrors {
  form: string | null;
  fields: Record<string, string[]>;
}

export function toFormErrors(error: unknown): FormErrors {
  if (error instanceof ApiError) {
    return {
      form: error.isValidationError ? null : error.message,
      fields: error.fieldErrors,
    };
  }
  return { form: 'Something went wrong. Please try again.', fields: {} };
}

export function fieldError(errors: FormErrors, field: string): string | undefined {
  return errors.fields[field]?.[0];
}
