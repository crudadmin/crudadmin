<template>
    <div class="form-group radio-group">
        <label>{{ field_name }} <span v-if="required" class="required">*</span></label>
        <div class="radio" v-if="!required">
            <label>
                <input type="radio" :name="field_key" value="">
                {{ trans('no-option') }}
            </label>
        </div>

        <div class="radio" v-for="data in field.options">
            <label>
                <input type="radio" @change="changeValue" :name="field_key" :checked="hasValue(data[0], value)" :value="data[0]">

                {{ data[1] }}
            </label>
        </div>
        <small>{{ field.title }}</small>
    </div>
</template>

<script>
    export default {
        props: ['model', 'field_name', 'field_key', 'field', 'value', 'required', 'disabled'],

        computed : {
            isPassword(){
                return this.field.type == 'password';
            },
        },

        methods : {
            hasValue(key, value, multiple)
            {
              return (key || key == 0) && value && key == value;
            },
            changeValue(e){
                this.$parent.changeValue(e);
            },
            trans(key){
                return this.$root.trans(key);
            },
        },
    }
</script>