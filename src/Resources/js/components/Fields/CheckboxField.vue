<template>
    <div class="form-group" :class="{ disabled : disabled }">
        <label class="checkbox">
            {{ field_name }} <span v-if="field.placeholder">{{ field.placeholder }}</span>
            <input
                type="checkbox"
                class="ios-switch green"
                value="1"
                @change="changeValue"
                :disabled="disabled"
                :checked="value == 1"
                :name="field_key">
            <div><div></div></div>
        </label>
        <small>{{ field.title }}</small>
    </div>

</template>

<script>
    export default {
        props: ['model', 'field_name', 'field_key', 'field', 'value', 'required', 'disabled'],

        methods : {
            changeValue(e){
                this.$parent.changeValue(e);
            },
        },
    }
</script>