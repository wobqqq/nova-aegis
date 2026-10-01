<template>
    <Card class="aegis-card">
        <form @submit.prevent="save">
            <h2 class="aegis-section-title">{{ section.label }}</h2>
            <p v-if="section.description" class="aegis-help">{{ section.description }}</p>

            <FieldInput
                v-for="field in section.fields"
                :key="field.name"
                v-model="values[field.name]"
                :field="field"
                :section="section.key"
                :errors="fieldErrors(field.name)"
            />

            <div class="aegis-actions">
                <button
                    type="submit"
                    class="inline-flex h-9 items-center rounded-lg bg-primary-500 px-4 text-sm font-bold text-white"
                    :disabled="saving"
                >
                    {{ saving ? 'Saving…' : 'Save' }}
                </button>
            </div>
        </form>
    </Card>
</template>

<script>
import api, { errorMessage, validationErrors } from '../api'
import FieldInput from './FieldInput.vue'

export default {
    components: { FieldInput },

    props: {
        section: { type: Object, required: true },
    },

    emits: ['saved'],

    data() {
        return {
            values: JSON.parse(JSON.stringify(this.section.values ?? {})),
            errors: {},
            saving: false,
        }
    },

    methods: {
        fieldErrors(name) {
            return Object.entries(this.errors)
                .filter(([key]) => key === name || key.startsWith(`${name}.`))
                .flatMap(([, messages]) => messages)
        },

        async save() {
            this.saving = true
            this.errors = {}

            try {
                const { values } = await api.save(this.section.key, this.values)
                this.values = values
                Nova.success(`${this.section.label} saved.`)
                this.$emit('saved', this.section.key, values)
            } catch (error) {
                this.errors = validationErrors(error)
                Nova.error(errorMessage(error))
            } finally {
                this.saving = false
            }
        },
    },
}
</script>
