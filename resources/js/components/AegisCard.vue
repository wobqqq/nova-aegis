<template>
    <Card class="aegis-card aegis-dashboard-card">
        <div class="aegis-card-header">
            <h3 class="aegis-section-title">Aegis</h3>
            <a :href="card.toolPath" class="aegis-link">Open</a>
        </div>

        <p v-if="loading" class="aegis-help">Checking…</p>
        <p v-else-if="error" class="aegis-error">{{ error }}</p>
        <template v-else>
            <p class="aegis-counts">
                <span class="aegis-badge aegis-badge--fail">{{ counts.fail }} failing</span>
                <span class="aegis-badge aegis-badge--warn">{{ counts.warn }} warnings</span>
                <span class="aegis-badge aegis-badge--pass">{{ counts.pass }} passing</span>
            </p>
            <StatusList v-if="problems.length" :results="problems" />
            <p v-else class="aegis-help">Every check passes.</p>
        </template>
    </Card>
</template>

<script>
import api, { errorMessage } from '../api'
import StatusList from './StatusList.vue'

export default {
    components: { StatusList },

    props: {
        card: { type: Object, required: true },
    },

    data: () => ({
        loading: true,
        error: null,
        results: [],
    }),

    computed: {
        counts() {
            const count = (status) => this.results.filter((result) => result.status === status).length

            return { fail: count('fail'), warn: count('warn'), pass: count('pass') }
        },

        problems() {
            return this.results.filter((result) => result.status === 'fail' || result.status === 'warn')
        },
    },

    async mounted() {
        try {
            const { checks, modules } = await api.overview()
            this.results = [...checks, ...modules]
        } catch (error) {
            this.error = errorMessage(error)
        } finally {
            this.loading = false
        }
    },
}
</script>
