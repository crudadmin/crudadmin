<template>
    <div>Vue component TableHeader {{ model.name }} {{ row.id ? ' id '+row.id : '' }}</div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows'],
}
</script>