import { vi } from 'vitest'

export function fakeNova(responses = {}) {
    const request = { get: vi.fn(), post: vi.fn(), put: vi.fn() }

    for (const method of ['get', 'post', 'put']) {
        request[method].mockImplementation((url) => {
            const answer = responses[`${method.toUpperCase()} ${url}`]

            if (answer instanceof Error) {
                return Promise.reject(answer)
            }

            return Promise.resolve({ data: typeof answer === 'function' ? answer() : answer })
        })
    }

    globalThis.Nova = { request: () => request, success: vi.fn(), error: vi.fn() }

    return request
}

export function httpError(status, data) {
    return Object.assign(new Error(`HTTP ${status}`), { response: { status, data } })
}

export const stubs = {
    Head: true,
    Heading: { template: '<h1><slot /></h1>' },
    Card: { template: '<section><slot /></section>' },
}
