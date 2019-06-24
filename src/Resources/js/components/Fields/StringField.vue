<template>
    <div class="form-group" :class="{ disabled : disabled }">
        <label>
            {{ field.name }}
            <span v-if="required" class="required">*</span>
        </label>
        <input
            class="form-control"
            :type="isPassword ? 'password' : 'text'"
            :name="field_key"
            :value="value"
            :maxlength="field.max"
            :placeholder="field.placeholder || field.name"
            :disabled="disabled"
            @keyup="changeValue">
        <small>{{ field.title }}</small>
    </div>
</template>

<script>
    export default {
        props: ['model', 'field_key', 'field', 'value', 'required', 'disabled'],

        computed : {
            isPassword(){
                return this.field.type == 'password';
            },
        },

        methods : {
            changeValue(e){
                this.$parent.changeValue(e);
            },
        },
    }
</script>