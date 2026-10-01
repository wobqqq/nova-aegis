import { describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import SettingsSection from './SettingsSection.vue'
import { fakeNova, httpError, stubs } from '../testing'

const section = {
    key: 'hardening',
    label: 'Hardening',
    description: 'Session and password policy',
    fields: [
        { name: 'enabled', type: 'toggle', label: 'Enabled' },
        { name: 'targets', type: 'table', label: 'Targets', columns: [{ name: 'host', label: 'Host' }] },
    ],
    values: { enabled: false, targets: [] },
}

const mountSection = () => mount(SettingsSection, { props: { section }, global: { stubs } })

describe('SettingsSection', () => {
    it('saves the values and tells the page', async () => {
        const request = fakeNova({
            'PUT /nova-vendor/aegis/settings/hardening': { values: { enabled: true, targets: [] } },
        })
        const wrapper = mountSection()

        await wrapper.find('input[type=checkbox]').setValue(true)
        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(request.put).toHaveBeenCalledWith('/nova-vendor/aegis/settings/hardening', {
            values: { enabled: true, targets: [] },
        })
        expect(Nova.success).toHaveBeenCalledWith('Hardening saved.')
        expect(wrapper.emitted('saved')[0]).toEqual(['hardening', { enabled: true, targets: [] }])
    })

    it('shows the validation errors under their field, the rows included', async () => {
        fakeNova({
            'PUT /nova-vendor/aegis/settings/hardening': httpError(422, {
                message: 'The given data was invalid.',
                errors: { enabled: ['Must be true or false.'], 'targets.0.host': ['Invalid host.'] },
            }),
        })
        const wrapper = mountSection()

        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(wrapper.text()).toContain('Must be true or false.')
        expect(wrapper.text()).toContain('Invalid host.')
        expect(Nova.error).toHaveBeenCalledWith('The given data was invalid.')
    })

    it('falls back to a generic message without a response', async () => {
        fakeNova({ 'PUT /nova-vendor/aegis/settings/hardening': new Error('offline') })
        const wrapper = mountSection()

        await wrapper.find('form').trigger('submit')
        await flushPromises()

        expect(Nova.error).toHaveBeenCalledWith('Something went wrong.')
    })
})
