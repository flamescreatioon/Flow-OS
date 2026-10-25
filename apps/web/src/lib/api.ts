import { config } from '@/config';

/**
 * Error thrown for every non-2xx API response. Carries the structured
 * error envelope fields ({ error: { code, message, details } }) that the
 * Laravel API returns for all /api/* routes.
 */
export class ApiError extends Error {
  readonly status: number;
  readonly code: string;
  readonly fieldErrors: Record<string, string[]>;

  constructor(
    status: number,
    code: string,
    message: string,
    fieldErrors: Record<string, string[]> = {},
  ) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.fieldErrors = fieldErrors;
  }

  get isValidationError(): boolean {
    return this.code === 'VALIDATION_ERROR';
  }

  get isUnauthenticated(): boolean {
    return this.status === 401;
  }
}

interface ErrorEnvelope {
  error?: {
    code?: string;
    message?: string;
    details?: { fields?: Record<string, string[]> };
  };
}

function readXsrfToken(): string | null {
  if (typeof document === 'undefined') return null;
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
  return match ? decodeURIComponent(match[1]) : null;
}

class ApiClient {
  constructor(
    private readonly baseUrl: string,
    private readonly csrfCookieUrl: string,
  ) {}

  /**
   * Laravel Sanctum requires the XSRF-TOKEN cookie to be present before any
   * stateful mutating request. Bootstrapping is skipped when the cookie
   * already exists so only the first mutation per session pays the cost.
   */
  private async ensureCsrfToken(): Promise<void> {
    if (readXsrfToken()) return;
    await fetch(this.csrfCookieUrl, { credentials: 'include' });
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {},
  ): Promise<T> {
    const method = (options.method ?? 'GET').toUpperCase();
    if (method !== 'GET' && method !== 'HEAD') {
      await this.ensureCsrfToken();
    }

    const headers: Record<string, string> = { Accept: 'application/json' };
    if (options.body !== undefined) {
      headers['Content-Type'] = 'application/json';
    }
    const xsrfToken = readXsrfToken();
    if (xsrfToken) {
      headers['X-XSRF-TOKEN'] = xsrfToken;
    }

    const response = await fetch(`${this.baseUrl}${endpoint}`, {
      ...options,
      credentials: 'include',
      headers: {
        ...headers,
        ...(options.headers as Record<string, string> | undefined),
      },
    });

    if (!response.ok) {
      let envelope: ErrorEnvelope = {};
      try {
        envelope = await response.json();
      } catch {
        // Non-JSON error body; fall back to status text below.
      }
      throw new ApiError(
        response.status,
        envelope.error?.code ?? 'UNKNOWN',
        envelope.error?.message ?? response.statusText,
        envelope.error?.details?.fields ?? {},
      );
    }

    if (response.status === 204) {
      return undefined as T;
    }
    return response.json();
  }

  get<T>(endpoint: string) {
    return this.request<T>(endpoint, { method: 'GET' });
  }

  post<T>(endpoint: string, data?: unknown) {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data === undefined ? undefined : JSON.stringify(data),
    });
  }

  put<T>(endpoint: string, data: unknown) {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: JSON.stringify(data),
    });
  }

  patch<T>(endpoint: string, data: unknown) {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: JSON.stringify(data),
    });
  }

  delete<T>(endpoint: string) {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }
}

export const api = new ApiClient(config.api.baseUrl, config.api.csrfCookieUrl);
