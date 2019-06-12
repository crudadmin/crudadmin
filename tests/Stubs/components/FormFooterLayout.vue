<template>
    <div>Vue component FormFooter {{ model.name }} {{ row.id ? ' id '+row.id : '' }}</div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows'],
}
</script>