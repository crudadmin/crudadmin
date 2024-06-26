<template></template>

<script type="text/javascript">
export default {
    props : ['model', 'data'],

    mounted(){
        this.onRowRemove(this.data.removedIds);
    },

    methods : {
        onRowRemove(ids){
            this.model.fire(['onDelete', 'delete'], ids);

            //Reset opened form which has been deleted
            let row = this.model.getRow();
            if ( row && ids.includes(row.id) ) {
                this.model.resetForm();
            }
        }
    }
}
</script>