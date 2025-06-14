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

console.log('TestComponent', props.model);
</script>

<style scoped></style>