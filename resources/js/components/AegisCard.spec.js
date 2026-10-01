import { describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import AegisCard from './AegisCard.vue'
import { fakeNova, httpError, stubs } from '../testing'

const mountCard = () => mount(AegisCard, { props: { card: { toolPath: '/nova/aegis' } }, global: { stubs } })

describe('AegisCard', () => {
    it('counts the results and lists the problems', async () => {
        fakeNova({
            'GET /nova-vendor/aegis/overview': {
                checks: [
                    { key: 'debug', label: 'Debug', status: 'fail', message: 'On' },
                    { key: 'env', label: 'Env', status: 'pass', message: 'Production' },
                ],
                modules: [{ key: 'hardening', label: 'Hardening', status: 'warn', message: 'Off' }],
            },
        })
        const wrapper = mountCard()
        await flushPromises()

        expect(wrapper.text()).toContain('1 failing')
        expect(wrapper.text()).toContain('1 warnings')
        expect(wrapper.text()).toContain('1 passing')
        expect(wrapper.findAll('.aegis-status-item')).toHaveLength(2)
        expect(wrapper.find('a').attributes('href')).toBe('/nova/aegis')
    })

    it('says when every check passes', async () => {
        fakeNova({ 'GET /nova-vendor/aegis/overview': { checks: [], modules: [] } })
        const wrapper = mountCard()
        await flushPromises()

        expect(wrapper.text()).toContain('Every check passes.')
    })

    it('says why when the overview fails', async () => {
        fakeNova({ 'GET /nova-vendor/aegis/overview': httpError(403, { message: 'This action is unauthorized.' }) })
        const wrapper = mountCard()
        await flushPromises()

        expect(wrapper.text()).not.toContain('Checking…')
        expect(wrapper.find('.aegis-error').text()).toBe('This action is unauthorized.')
    })
})
