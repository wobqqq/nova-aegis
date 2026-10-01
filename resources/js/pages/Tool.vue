<template>
    <div>
        <Head title="Aegis" />
        <Heading class="mb-6">Aegis</Heading>

        <div class="aegis-tabs" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="aegis-tab"
                :class="{ 'aegis-tab--active': current === tab.key }"
                :aria-selected="current === tab.key"
                @click="current = tab.key"
            >
                {{ tab.label }}
            </button>
        </div>

        <template v-if="current === 'overview'">
            <Card class="aegis-card">
                <div class="aegis-card-header">
                    <h2 class="aegis-section-title">Checks</h2>
                    <button type="button" class="aegis-link" @click="loadOverview">Refresh</button>
                </div>
                <p v-if="!overview" class="aegis-help">Checking…</p>
                <StatusList v-else :results="[...overview.modules, ...overview.checks]" />
            </Card>

            <Card class="aegis-card">
                <div class="aegis-card-header">
                    <h2 class="aegis-section-title">Dependency audit</h2>
                    <button type="button" class="aegis-link" :disabled="auditing" @click="runAudit">
                        {{ auditing ? 'Running…' : 'Run now' }}
                    </button>
                </div>
                <p v-if="!audit" class="aegis-help">composer audit has not run yet.</p>
                <template v-else>
                    <p class="aegis-help">Last run {{ audit.ran_at }}.</p>
                    <p v-if="audit.error" class="aegis-error">{{ audit.error }}</p>
                    <ul v-else-if="audit.advisories.length" class="aegis-status-list">
                        <li
                            v-for="advisory in audit.advisories"
                            :key="advisory.package + advisory.title"
                            class="aegis-status-item"
                        >
                            <span class="aegis-badge aegis-badge--fail">{{ advisory.package }}</span>
                            <span class="aegis-status-text">
                                <strong>{{ advisory.title }}</strong>
                                <span v-if="advisory.cve">{{ advisory.cve }}</span>
                            </span>
                        </li>
                    </ul>
                    <p v-else class="aegis-help">No security advisories.</p>
                </template>
            </Card>
        </template>

        <template v-else-if="current === 'settings'">
            <p v-if="!sections" class="aegis-help">Loading…</p>
            <SettingsSection v-for="section in sections" :key="section.key" :section="section" @saved="onSaved" />
        </template>

        <template v-else>
            <p v-if="!sections" class="aegis-help">Loading…</p>
            <template v-else>
                <ScannerPanel
                    title="Sensitive files"
                    kind="sensitive-files"
                    field="url"
                    :targets="column('sensitive_file_urls', 'url')"
                />
                <ScannerPanel
                    title="Open TCP ports"
                    kind="tcp-ports"
                    field="host"
                    :targets="column('tcp_targets', 'host')"
                />
                <ScannerPanel
                    title="TLS certificates"
                    kind="tls-certificates"
                    field="host"
                    :targets="column('tls_targets', 'host')"
                />
            </template>
        </template>
    </div>
</template>

<script>
import api, { errorMessage } from '../api'
import ScannerPanel from '../components/ScannerPanel.vue'
import SettingsSection from '../components/SettingsSection.vue'
import StatusList from '../components/StatusList.vue'

export default {
    components: { ScannerPanel, SettingsSection, StatusList },

    data: () => ({
        tabs: [
            { key: 'overview', label: 'Overview' },
            { key: 'settings', label: 'Settings' },
            { key: 'scanners', label: 'Scanners' },
        ],
        current: 'overview',
        overview: null,
        audit: null,
        auditing: false,
        sections: null,
    }),

    mounted() {
        this.loadOverview()
        this.loadSettings()
    },

    methods: {
        async loadOverview() {
            try {
                this.overview = await api.overview()
                this.audit = this.overview.audit
            } catch (error) {
                Nova.error(errorMessage(error))
            }
        },

        async loadSettings() {
            try {
                this.sections = (await api.settings()).sections
            } catch (error) {
                Nova.error(errorMessage(error))
            }
        },

        async runAudit() {
            this.auditing = true

            try {
                this.audit = (await api.audit()).audit
            } catch (error) {
                Nova.error(errorMessage(error))
            } finally {
                this.auditing = false
            }
        },

        onSaved(key, values) {
            this.sections = this.sections.map((section) => (section.key === key ? { ...section, values } : section))
            this.loadOverview()
        },

        column(name, column) {
            const scanners = (this.sections ?? []).find((section) => section.key === 'scanners')
            const rows = scanners?.values?.[name] ?? []

            return [...new Set(rows.map((row) => String(row[column] ?? '').trim()).filter((value) => value !== ''))]
        },
    },
}
</script>
