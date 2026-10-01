import { describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import Tool from './Tool.vue'
import { fakeNova, httpError, stubs } from '../testing'

const overview = {
    checks: [{ key: 'debug', label: 'Debug', status: 'pass', message: 'Off' }],
    modules: [{ key: 'hardening', label: 'Hardening', status: 'warn', message: 'Off' }],
    audit: { ran_at: '2026-10-01', advisories: [{ package: 'acme/lib', title: 'RCE', cve: 'CVE-1' }], error: null },
}

const sections = {
    sections: [
        { key: 'hardening', label: 'Hardening', fields: [], values: { enabled: false } },
        {
            key: 'scanners',
            label: 'Scanners',
            fields: [],
            values: {
                sensitive_file_urls: [{ url: 'https://aegis.test' }, { url: 'https://aegis.test' }],
                tcp_targets: [
                    { host: '', ports: '22' },
                    { host: '203.0.113.1', ports: '22' },
                ],
                tls_targets: [{ host: 'aegis.test', ports: '443' }],
            },
        },
    ],
}

const mountTool = () => mount(Tool, { global: { stubs } })

describe('Tool', () => {
    it('shows the checks, the modules and the advisories', async () => {
        fakeNova({ 'GET /nova-vendor/aegis/overview': overview, 'GET /nova-vendor/aegis/settings': sections })
        const wrapper = mountTool()
        await flushPromises()

        expect(wrapper.text()).toContain('Hardening')
        expect(wrapper.text()).toContain('acme/lib')
        expect(wrapper.text()).toContain('CVE-1')
    })

    it('reloads the checks on refresh', async () => {
        const request = fakeNova({
            'GET /nova-vendor/aegis/overview': overview,
            'GET /nova-vendor/aegis/settings': sections,
        })
        const wrapper = mountTool()
        await flushPromises()

        await wrapper.findAll('.aegis-card-header button')[0].trigger('click')
        await flushPromises()

        expect(request.get).toHaveBeenCalledTimes(3)
    })

    it('runs the audit on request', async () => {
        const request = fakeNova({
            'GET /nova-vendor/aegis/overview': { ...overview, audit: null },
            'GET /nova-vendor/aegis/settings': sections,
            'POST /nova-vendor/aegis/audit': { audit: { ran_at: 'now', advisories: [], error: null } },
        })
        const wrapper = mountTool()
        await flushPromises()

        expect(wrapper.text()).toContain('has not run yet')

        await wrapper.findAll('.aegis-card-header button')[1].trigger('click')
        await flushPromises()

        expect(request.post).toHaveBeenCalledWith('/nova-vendor/aegis/audit')
        expect(wrapper.text()).toContain('No security advisories.')
    })

    it('shows an audit that could not run', async () => {
        fakeNova({
            'GET /nova-vendor/aegis/overview': {
                ...overview,
                audit: { ran_at: 'now', advisories: [], error: 'composer missing' },
            },
            'GET /nova-vendor/aegis/settings': sections,
        })
        const wrapper = mountTool()
        await flushPromises()

        expect(wrapper.text()).toContain('composer missing')
    })

    it('lists one scanner target per configured value', async () => {
        fakeNova({ 'GET /nova-vendor/aegis/overview': overview, 'GET /nova-vendor/aegis/settings': sections })
        const wrapper = mountTool()
        await flushPromises()

        await wrapper.findAll('[role=tab]')[2].trigger('click')

        expect(wrapper.findAll('.aegis-scan-target').map((target) => target.text())).toEqual([
            'https://aegis.testRun',
            '203.0.113.1Run',
            'aegis.testRun',
        ])
    })

    it('draws the settings and refreshes after a save', async () => {
        const request = fakeNova({
            'GET /nova-vendor/aegis/overview': overview,
            'GET /nova-vendor/aegis/settings': sections,
        })
        const wrapper = mountTool()
        await flushPromises()

        await wrapper.findAll('[role=tab]')[1].trigger('click')
        wrapper.findComponent({ name: 'SettingsSection' }).vm.$emit('saved', 'hardening', { enabled: true })
        await flushPromises()

        expect(wrapper.vm.sections[0].values).toEqual({ enabled: true })
        expect(request.get).toHaveBeenCalledTimes(3)
    })

    it('reports the failures it meets', async () => {
        fakeNova({
            'GET /nova-vendor/aegis/overview': httpError(500, { message: 'Overview failed' }),
            'GET /nova-vendor/aegis/settings': httpError(500, { message: 'Settings failed' }),
            'POST /nova-vendor/aegis/audit': httpError(429, { message: 'Too many' }),
        })
        const wrapper = mountTool()
        await flushPromises()

        await wrapper.findAll('.aegis-card-header button')[1].trigger('click')
        await flushPromises()

        expect(Nova.error).toHaveBeenCalledWith('Overview failed')
        expect(Nova.error).toHaveBeenCalledWith('Settings failed')
        expect(Nova.error).toHaveBeenCalledWith('Too many')
    })
})
