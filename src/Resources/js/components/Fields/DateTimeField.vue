<template>
    <div class="form-group" :class="{ disabled : disabled, 'multiple-date' : isMultipleDatepicker }">
        <label>{{ field_name }} <span v-if="required" class="required">*</span></label>

        <input
            ref="input"
            type="text"
            class="form-control"
            :disabled="disabled"
            :name="isMultipleDatepicker ? '' : field_key"
            :value="value"
            :placeholder="field.placeholder || field_name"
            @keyup="changeValue">

        <input type="hidden" :name="field_key+'[]'" v-if="isMultipleDatepicker && getMultiDates.length == 0" value="">
        <input type="hidden" :name="field_key+'[]'" :value="getMultiDateValue(date)" v-if="isMultipleDatepicker" v-for="date in getMultiDates">
        <small>{{ field.title }}</small>
    </div>
</template>

<script>
    export default {
        props: ['model', 'field_name', 'field_key', 'field', 'value', 'required', 'disabled'],

        mounted(){
            this.bindDatepickers();

            eventHub.$on('updateField', data => {
                if ( data[0] != this.field_key )
                    return;

                this.bindDatepickers();
            });
        },

        computed : {
            isMultipleDatepicker() {
                return this.field.multiple == true;
            },
            getMultiDates(){
                var value = this.field.value||[];

                if ( ! $.isArray(value) )
                    value = [];

                //Check correct inputs values
                return _.cloneDeep(value).filter(item => {
                    return item.length == (this.field.type == 'time' ? 5 : 10);
                });
            },
        },

        methods : {
            getInput(){
                return $(this.$refs.input);
            },
            bindDatepickers(){
                var _this = this;

                this.getInput().datetimepicker({
                    lang: this.$root.locale,
                    format: this.field.date_format,
                    timepicker: this.field.type != 'date',
                    datepicker: this.field.type != 'time',
                    scrollInput: false,
                    timepickerScrollbar: false,
                    step : this.field.date_step||30,
                    scrollMonth: false,
                    scrollYear: false,
                    inline : this.isMultipleDatepicker,
                    onGenerate: function(ct){
                        _this.onGenerate(this, ct);
                    },
                    onChangeDateTime: this.onChangeDateTime,
                    //Also update object of single date values
                    onClose : (current_date_time) => {
                        var pickedDate = moment(current_date_time).format(this.$root.fromPHPFormatToMoment(this.field.date_format));

                        this.changeValue(null, pickedDate);
                    },
                });
            },
            onGenerate(el, ct){
                if ( ! this.isMultipleDatepicker )
                    return;

                el.addClass('multiple-dates');

                var values = this.getMultiDates;

                for ( var i = 0; i < values.length; i++ )
                {
                    var date = this.field.type == 'time' ? values[i].split(':') : new Date(values[i]);

                    var selector = this.field.type == 'time'
                                    ? 'div[data-hour="'+parseInt(date[0])+'"][data-minute="'+parseInt(date[1])+'"]'
                                    : 'td[data-date="'+date.getDate()+'"][data-month="'+date.getMonth()+'"][data-year="'+date.getFullYear()+'"]';

                    el.find(selector).addClass('multiple-selected');
                }
            },
            onChangeDateTime(current_date_time){
                if ( ! this.isMultipleDatepicker )
                    return;

                var pickedDate = moment(current_date_time).format(this.field.type == 'time' ? 'HH:mm' : 'YYYY-MM-DD');

                var value = this.getMultiDates,
                    index = value.indexOf(pickedDate);

                if ( index > -1 )
                    value.splice(index, 1);
                else
                    value.push(pickedDate);

                this.$parent.changeValue(null, value);
            },
            getMultiDateValue(time){
                if ( this.field.type == 'time' && time.length == 5 && time[2] == ':' )
                    return time;

                return moment(time).format('YYYY-MM-DD');
            },
            changeValue(e, date){
                this.$parent.changeValue(e, date);
            },
        },
    }
</script>