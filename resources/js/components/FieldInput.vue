<template>
    <div class="aegis-field">
        <label v-if="field.type === 'toggle'" class="aegis-toggle">
            <input :id="id" type="checkbox" :checked="Boolean(modelValue)" @change="emit($event.target.checked)" />
            <span>{{ field.label }}</span>
        </label>

        <template v-else>
            <label :for="id" class="aegis-label">{{ field.label }}</label>

            <select
                v-if="field.type === 'select'"
                :id="id"
                class="form-control form-select form-select-bordered w-full"
                :value="modelValue"
                @change="emit($event.target.value)"
            >
                <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
            </select>

            <textarea
                v-else-if="field.type === 'textarea'"
                :id="id"
                class="form-control form-input form-input-bordered w-full"
                rows="3"
                :value="modelValue"
                @input="emit($event.target.value)"
            />

            <div v-else-if="field.type === 'table'" class="aegis-table">
                <div v-for="(row, index) in rows" :key="index" class="aegis-table-row">
                    <input
                        v-for="column in field.columns"
                        :key="column.name"
                        class="form-control form-input form-input-bordered"
                        type="text"
                        :aria-label="column.label"
                        :placeholder="column.placeholder ?? column.label"
                        :value="row[column.name] ?? ''"
                        @input="updateCell(index, column.name, $event.target.value)"
                    />
                    <button type="button" class="aegis-link aegis-link--danger" @click="removeRow(index)">
                        Remove
                    </button>
                </div>
                <button type="button" class="aegis-link" @click="addRow">Add a row</button>
            </div>

            <input
                v-else
                :id="id"
                class="form-control form-input form-input-bordered w-full"
                :type="field.type === 'number' ? 'number' : 'text'"
                :placeholder="field.placeholder ?? ''"
                :value="modelValue"
                @input="emit(field.type === 'number' ? Number($event.target.value) : $event.target.value)"
            />
        </template>

        <p v-if="field.help" class="aegis-help">{{ field.help }}</p>
        <p v-for="message in errors" :key="message" class="aegis-error">{{ message }}</p>
    </div>
</template>

<script>
export default {
    props: {
        field: { type: Object, required: true },
        modelValue: { type: [String, Number, Boolean, Array], default: null },
        errors: { type: Array, default: () => [] },
        section: { type: String, required: true },
    },

    emits: ['update:modelValue'],

    computed: {
        id() {
            return `aegis-${this.section}-${this.field.name}`
        },

        rows() {
            return Array.isArray(this.modelValue) ? this.modelValue : []
        },
    },

    methods: {
        emit(value) {
            this.$emit('update:modelValue', value)
        },

        addRow() {
            const empty = Object.fromEntries((this.field.columns ?? []).map((column) => [column.name, '']))
            this.emit([...this.rows, empty])
        },

        removeRow(index) {
            this.emit(this.rows.filter((_, position) => position !== index))
        },

        updateCell(index, name, value) {
            this.emit(this.rows.map((row, position) => (position === index ? { ...row, [name]: value } : row)))
        },
    },
}
</script>
