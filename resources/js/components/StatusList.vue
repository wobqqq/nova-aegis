<template>
    <ul class="aegis-status-list">
        <li v-for="result in results" :key="result.key" class="aegis-status-item">
            <span class="aegis-badge" :class="`aegis-badge--${result.status}`">{{
                labels[result.status] ?? result.status
            }}</span>
            <span class="aegis-status-text">
                <strong>{{ result.label }}</strong>
                <span>{{ result.message }}</span>
            </span>
        </li>
    </ul>
</template>

<script>
export default {
    props: {
        results: { type: Array, required: true },
    },

    data: () => ({
        labels: { pass: 'Pass', warn: 'Warning', fail: 'Fail', info: 'Info' },
    }),
}
</script>
