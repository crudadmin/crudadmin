<template>
    <div>
        <h1>Test Component</h1>
    </div>
</template>

<script setup>
const props = defineProps({
    model: {
        type: Object,
        required: true,
    },
});

const adminStore = useAdminStore();

console.log('TestComponent', props.model, adminStore);
</script>

<style scoped></style>