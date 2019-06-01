<template>
    <div>
        <p>You pressed button for row ID <strong>{{ pressedRows }}</strong></p>

        <label>How are you? This is my custom component.</label>
        <input type="text" v-model="request.mood" class="form-control">

        <h2>I have {{ request.mood||'good' }} mood.</h2>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['model', 'row', 'rows', 'request'],

    data(){
        return {

        }
    },

    mounted(){
        console.log('Your own layout is ready!!', this.model, this.row, this.rows, this.request);
    },

    computed: {
        pressedRows(){
            //If has been pressed one row
            if ( this.row )
                return this.row.id;

            //If has been pressed multiple rows
            if ( this.rows )
                return this.rows.map(item => item.id).join(', ');
        },
    },
}
</script>