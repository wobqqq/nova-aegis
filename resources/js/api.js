const base = '/nova-vendor/aegis'

const data = (response) => response.data

export default {
    overview: () => Nova.request().get(`${base}/overview`).then(data),
    audit: () => Nova.request().post(`${base}/audit`).then(data),
    settings: () => Nova.request().get(`${base}/settings`).then(data),
    save: (section, values) =>
        Nova.request()
            .put(`${base}/settings/${encodeURIComponent(section)}`, { values })
            .then(data),
    scan: (kind, payload) => Nova.request().post(`${base}/scans/${kind}`, payload).then(data),
}

export const validationErrors = (error) => error?.response?.data?.errors ?? {}

export const errorMessage = (error) => error?.response?.data?.message ?? 'Something went wrong.'
