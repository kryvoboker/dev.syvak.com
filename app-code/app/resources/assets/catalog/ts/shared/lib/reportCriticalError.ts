type FrontendErrorType = 'window_error' | 'unhandled_rejection' | 'manual';

interface FrontendErrorPayload {
    type: FrontendErrorType;
    message: string;
    stack?: string;
    url: string;
    filename?: string;
    line?: number;
    column?: number;
    user_agent: string;
    page_type?: string;
}

interface ErrorDetails {
    message: string;
    stack?: string;
}

interface ErrorLocation {
    filename?: string;
    line?: number;
    column?: number;
}

const MAX_MESSAGE_LENGTH: number = 2000;
const MAX_STACK_LENGTH: number = 10000;
const DEDUPLICATION_WINDOW_MS: number = 60000;
const reportedErrors: Map<string, number> = new Map();

const limitString = (value: string, maxLength: number): string => value.slice(0, maxLength);

const getSafePageUrl = (): string => {
    try {
        const url = new URL(window.location.href);
        url.search = '';
        url.hash = '';

        return url.toString();
    } catch {
        return window.location.origin;
    }
};

const getErrorDetails = (error: unknown): ErrorDetails | null => {
    if (error instanceof Error) {
        return {
            message: error.message || error.name || 'Unknown frontend error',
            stack: error.stack,
        };
    }

    if (typeof error === 'object' && error !== null) {
        const errorRecord = error as { message?: unknown; stack?: unknown };

        if (typeof errorRecord.message === 'string' && errorRecord.message !== '') {
            return {
                message: errorRecord.message,
                stack: typeof errorRecord.stack === 'string' ? errorRecord.stack : undefined,
            };
        }
    }

    return null;
};

const getErrorKey = (payload: FrontendErrorPayload): string =>
    [payload.type, payload.message, payload.stack ?? '', payload.filename ?? '', payload.line ?? ''].join('|');

const isDuplicate = (payload: FrontendErrorPayload): boolean => {
    const now = Date.now();
    const key = getErrorKey(payload);
    const previousReportTime = reportedErrors.get(key);

    if (previousReportTime !== undefined && now - previousReportTime < DEDUPLICATION_WINDOW_MS) {
        return true;
    }

    reportedErrors.set(key, now);

    if (reportedErrors.size > 100) {
        const oldestKey = reportedErrors.keys().next().value;

        if (oldestKey !== undefined) {
            reportedErrors.delete(oldestKey);
        }
    }

    return false;
};

const sendPayload = (payload: FrontendErrorPayload): void => {
    const reportUrl = window.app_params?.frontend_error_log_url;

    if (!reportUrl || isDuplicate(payload)) {
        return;
    }

    const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

    void fetch(reportUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            ...payload,
            _token: csrfToken,
        }),
        keepalive: true,
        credentials: 'same-origin',
    }).catch(() => undefined);
};

export const reportCriticalFrontendError = (
    error: unknown,
    type: FrontendErrorType = 'manual',
    location: ErrorLocation = {},
): void => {
    const details = getErrorDetails(error);

    if (details === null) {
        return;
    }

    sendPayload({
        type,
        message: limitString(details.message, MAX_MESSAGE_LENGTH),
        stack: details.stack ? limitString(details.stack, MAX_STACK_LENGTH) : undefined,
        url: getSafePageUrl(),
        filename: location.filename ? limitString(location.filename, 2048) : undefined,
        line: location.line,
        column: location.column,
        user_agent: limitString(navigator.userAgent, 1000),
        page_type: window.app_params?.page_type ?? undefined,
    });
};

export const handleCriticalErrorReporting = (): void => {
    window.addEventListener('error', (event: ErrorEvent): void => {
        reportCriticalFrontendError(event.error ?? new Error(event.message), 'window_error', {
            filename: event.filename,
            line: event.lineno,
            column: event.colno,
        });
    });

    window.addEventListener('unhandledrejection', (event: PromiseRejectionEvent): void => {
        reportCriticalFrontendError(event.reason, 'unhandled_rejection');
    });
};
