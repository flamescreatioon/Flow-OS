import { api, ApiError } from '@/lib/api';
import { config } from '@/config';

const fetchMock = jest.fn();

function jsonResponse(body: unknown, status = 200): Response {
  return {
    ok: status >= 200 && status < 300,
    status,
    statusText: 'OK',
    json: async () => body,
  } as unknown as Response;
}

function emptyResponse(status: number): Response {
  return {
    ok: true,
    status,
    statusText: 'OK',
    json: async () => {
      throw new Error('no body');
    },
  } as unknown as Response;
}

function setXsrfCookie(token: string | null): void {
  if (token) {
    document.cookie = `XSRF-TOKEN=${encodeURIComponent(token)}; path=/`;
  } else {
    document.cookie = 'XSRF-TOKEN=; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
  }
}

describe('ApiClient', () => {
  beforeEach(() => {
    fetchMock.mockReset();
    global.fetch = fetchMock as unknown as typeof fetch;
    setXsrfCookie(null);
  });

  it('performs GET requests without bootstrapping CSRF', async () => {
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: { id: 'u1' } }));

    await api.get('/auth/me');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(fetchMock).toHaveBeenCalledWith(
      `${config.api.baseUrl}/auth/me`,
      expect.objectContaining({ method: 'GET', credentials: 'include' }),
    );
  });

  it('bootstraps the CSRF cookie before the first mutation and sends the token header', async () => {
    fetchMock.mockImplementation(async (url: string) => {
      if (url === config.api.csrfCookieUrl) {
        setXsrfCookie('csrf-token-value');
        return emptyResponse(204);
      }
      return jsonResponse({ data: { id: 'u1' } }, 201);
    });

    await api.post('/auth/login', { email: 'a@b.co', password: 'secret' });

    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(fetchMock).toHaveBeenNthCalledWith(
      1,
      config.api.csrfCookieUrl,
      expect.objectContaining({ credentials: 'include' }),
    );
    const [url, init] = fetchMock.mock.calls[1];
    expect(url).toBe(`${config.api.baseUrl}/auth/login`);
    expect(init.headers).toMatchObject({
      'Content-Type': 'application/json',
      'X-XSRF-TOKEN': 'csrf-token-value',
    });
    expect(JSON.parse(init.body)).toEqual({
      email: 'a@b.co',
      password: 'secret',
    });
  });

  it('skips the CSRF bootstrap when the cookie already exists', async () => {
    setXsrfCookie('existing-token');
    fetchMock.mockResolvedValueOnce(jsonResponse({ data: { id: 'u1' } }));

    await api.post('/auth/logout');

    expect(fetchMock).toHaveBeenCalledTimes(1);
    expect(fetchMock.mock.calls[0][1].headers).toMatchObject({
      'X-XSRF-TOKEN': 'existing-token',
    });
  });

  it('raises an ApiError carrying the structured error envelope', async () => {
    setXsrfCookie('existing-token');
    fetchMock.mockResolvedValueOnce(
      jsonResponse(
        {
          error: {
            code: 'VALIDATION_ERROR',
            message: 'The given data was invalid.',
            details: { fields: { email: ['The provided credentials are incorrect.'] } },
          },
        },
        422,
      ),
    );

    let caught: unknown;
    try {
      await api.post('/auth/login', {});
      throw new Error('Expected the request to be rejected.');
    } catch (error) {
      caught = error;
    }

    expect(caught).toBeInstanceOf(ApiError);
    const apiError = caught as ApiError;
    expect(apiError.status).toBe(422);
    expect(apiError.code).toBe('VALIDATION_ERROR');
    expect(apiError.isValidationError).toBe(true);
    expect(apiError.fieldErrors).toEqual({
      email: ['The provided credentials are incorrect.'],
    });
  });

  it('resolves 204 responses to undefined without parsing a body', async () => {
    setXsrfCookie('existing-token');
    fetchMock.mockResolvedValueOnce(emptyResponse(204));

    const result = await api.post('/auth/logout');

    expect(result).toBeUndefined();
  });
});
