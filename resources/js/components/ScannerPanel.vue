<template>
    <Card class="aegis-card">
        <h2 class="aegis-section-title">{{ title }}</h2>
        <p v-if="targets.length === 0" class="aegis-help">No targets yet: add them in the Scanners settings.</p>

        <div v-for="target in targets" :key="target" class="aegis-scan-target">
            <span class="aegis-scan-name">{{ target }}</span>
            <button
                type="button"
                class="inline-flex h-8 items-center rounded-lg bg-primary-500 px-3 text-xs font-bold text-white"
                :disabled="running === target"
                @click="run(target)"
            >
                {{ running === target ? 'Scanning…' : 'Run' }}
            </button>
        </div>

        <table v-if="results.length" class="aegis-results">
            <thead>
                <tr>
                    <th>Target</th>
                    <th>Result</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="result in results" :key="result.target" :class="{ 'aegis-exposed': result.exposed }">
                    <td>{{ result.target }}</td>
                    <td>
                        {{ result.status }}<template v-if="result.detail"> ({{ result.detail }})</template>
                    </td>
                </tr>
            </tbody>
        </table>
        <p v-if="summary" class="aegis-help">{{ summary }}</p>
    </Card>
</template>

<script>
import api, { errorMessage } from '../api'

export default {
    props: {
        title: { type: String, required: true },
        kind: { type: String, required: true },
        field: { type: String, required: true },
        targets: { type: Array, required: true },
    },

    data: () => ({
        running: null,
        results: [],
        summary: '',
    }),

    methods: {
        async run(target) {
            this.running = target
            this.summary = ''

            try {
                const { results, exposed } = await api.scan(this.kind, { [this.field]: target })
                this.results = results
                this.summary = exposed === 0 ? 'Nothing exposed.' : `${exposed} exposed.`
            } catch (error) {
                this.results = []
                Nova.error(errorMessage(error))
            } finally {
                this.running = null
            }
        },
    },
}
</script>
