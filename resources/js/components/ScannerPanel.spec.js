import { describe, expect, it } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import ScannerPanel from './ScannerPanel.vue'
import { fakeNova, httpError, stubs } from '../testing'

const mountPanel = (targets = ['https://aegis.test']) =>
    mount(ScannerPanel, {
        props: { title: 'Sensitive files', kind: 'sensitive-files', field: 'url', targets },
        global: { stubs },
    })

describe('ScannerPanel', () => {
    it('runs a scan of a target and lists what is exposed', async () => {
        const request = fakeNova({
            'POST /nova-vendor/aegis/scans/sensitive-files': {
                exposed: 1,
                results: [
                    { target: 'https://aegis.test/.env', status: '200', exposed: true, detail: null },
                    { target: 'https://aegis.test:443', status: 'valid', exposed: false, detail: '2027-01-01' },
                ],
            },
        })
        const wrapper = mountPanel()

        await wrapper.find('button').trigger('click')
        await flushPromises()

        expect(request.post).toHaveBeenCalledWith('/nova-vendor/aegis/scans/sensitive-files', {
            url: 'https://aegis.test',
        })
        expect(wrapper.find('.aegis-exposed').text()).toContain('https://aegis.test/.env')
        expect(wrapper.text()).toContain('(2027-01-01)')
        expect(wrapper.text()).toContain('1 exposed.')
    })

    it('says when nothing is exposed', async () => {
        fakeNova({ 'POST /nova-vendor/aegis/scans/sensitive-files': { exposed: 0, results: [] } })
        const wrapper = mountPanel()

        await wrapper.find('button').trigger('click')
        await flushPromises()

        expect(wrapper.text()).toContain('Nothing exposed.')
    })

    it('reports a refused scan', async () => {
        fakeNova({ 'POST /nova-vendor/aegis/scans/sensitive-files': httpError(422, { message: 'Not listed.' }) })
        const wrapper = mountPanel()

        await wrapper.find('button').trigger('click')
        await flushPromises()

        expect(Nova.error).toHaveBeenCalledWith('Not listed.')
    })

    it('asks for targets when there are none', () => {
        expect(mountPanel([]).text()).toContain('No targets yet')
    })
})
