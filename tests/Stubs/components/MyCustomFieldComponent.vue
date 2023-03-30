<template>
    <div class="form-group">
        <label>{{ field.name }}</label>

        <input type="text" :name="field_key" :value="value" @keyup="onChange" placeholder="Wohoo,,, this is my first custom component field!!" class="form-control">

        <p>This is my first custom component for field <strong>{{ field.name }}</strong>, with <strong>{{ field.value || 'empty' }}</strong> value.</p>
        <p class="custom-field-row-event">This is value on event when row value of this field is changed: <strong>{{ rowChangedFromEvent||'no value' }}</strong></p>
        <p class="checkbox-field-row-event">original checkbox changed: <strong>{{ rowForCheckboxChangedFromEvent||'no value' }}</strong></p>
    </div>
</template>

<script type="text/javascript">
export default {
    props : ['field_key', 'field', 'row'],

    data(){
        return {
            rowChangedFromEvent : '',
            rowForCheckboxChangedFromEvent : '',
        }
    },

    mounted(){
        console.log('Your field component is mounted!!', this.field_key, this.row, this.field);

        //We want check, if row event triggers properly on value change
        this.$watch('row.' + this.field_key, function(value){
            this.rowChangedFromEvent = value;
        });

        //We also want check organic/original field on row change event
        this.$watch('row.checkbox', function(value){
            console.log('checkbox changed');
            this.rowForCheckboxChangedFromEvent = value;
        });
    },

    computed : {
        //Get input value
        value(){
            return this.field.value || this.field.default;
        },
    },

    methods : {
        //Update input value
        onChange(e){
            this.field.value = e.target.value;
        },
    }
}
</script>