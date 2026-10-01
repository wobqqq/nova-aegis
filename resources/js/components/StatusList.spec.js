import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import StatusList from './StatusList.vue'

describe('StatusList', () => {
    it('draws each result with its status as text, escaped', () => {
        const wrapper = mount(StatusList, {
            props: {
                results: [
                    { key: 'debug', label: 'Debug mode', status: 'fail', message: '<script>alert(1)</script>' },
                    { key: 'x', label: 'Custom', status: 'unknown', message: 'Odd' },
                ],
            },
        })

        expect(wrapper.text()).toContain('Fail')
        expect(wrapper.text()).toContain('unknown')
        expect(wrapper.html()).not.toContain('<script>')
        expect(wrapper.find('.aegis-badge--fail').exists()).toBe(true)
    })
})
