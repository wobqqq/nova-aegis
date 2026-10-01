import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import FieldInput from './FieldInput.vue'

const mountField = (field, modelValue = null) =>
    mount(FieldInput, { props: { field: { label: field.name, ...field }, modelValue, section: 'hardening' } })

describe('FieldInput', () => {
    it('emits a toggle as a boolean', async () => {
        const wrapper = mountField({ name: 'enabled', type: 'toggle' }, false)

        await wrapper.find('input[type=checkbox]').setValue(true)

        expect(wrapper.emitted('update:modelValue')[0]).toEqual([true])
    })

    it('emits a number as a number and text as text', async () => {
        const number = mountField({ name: 'length', type: 'number' }, 12)
        await number.find('input').setValue('16')

        const text = mountField({ name: 'name', type: 'text', placeholder: 'x' }, '')
        await text.find('input').setValue('value')

        expect(number.emitted('update:modelValue')[0]).toEqual([16])
        expect(text.emitted('update:modelValue')[0]).toEqual(['value'])
    })

    it('offers the options of a select and the text of a textarea', async () => {
        const select = mountField(
            { name: 'same_site', type: 'select', options: { lax: 'Lax', strict: 'Strict' } },
            'lax',
        )
        await select.find('select').setValue('strict')

        const textarea = mountField({ name: 'notes', type: 'textarea' }, '')
        await textarea.find('textarea').setValue('notes')

        expect(select.findAll('option')).toHaveLength(2)
        expect(select.emitted('update:modelValue')[0]).toEqual(['strict'])
        expect(textarea.emitted('update:modelValue')[0]).toEqual(['notes'])
    })

    it('adds, edits and removes the rows of a table', async () => {
        const field = {
            name: 'targets',
            type: 'table',
            columns: [
                { name: 'host', label: 'Host' },
                { name: 'ports', label: 'Ports' },
            ],
        }
        const wrapper = mountField(field, [{ host: 'a.test', ports: '443' }])

        await wrapper.findAll('input')[0].setValue('b.test')
        await wrapper.findAll('button')[1].trigger('click')
        await wrapper.findAll('button')[0].trigger('click')

        const [edited, added, removed] = wrapper.emitted('update:modelValue').map(([value]) => value)
        expect(edited).toEqual([{ host: 'b.test', ports: '443' }])
        expect(added).toEqual([
            { host: 'a.test', ports: '443' },
            { host: '', ports: '' },
        ])
        expect(removed).toEqual([])
    })

    it('treats a missing table value as no rows, and shows the help and the errors', () => {
        const wrapper = mount(FieldInput, {
            props: {
                field: { name: 't', type: 'table', label: 'T', help: 'Help text' },
                section: 's',
                errors: ['Too long'],
            },
        })

        expect(wrapper.findAll('.aegis-table-row')).toHaveLength(0)
        expect(wrapper.text()).toContain('Help text')
        expect(wrapper.text()).toContain('Too long')
    })
})
