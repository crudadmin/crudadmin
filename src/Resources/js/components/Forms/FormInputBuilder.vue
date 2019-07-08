<template>
    <div :data-field="field_key" :data-model="model.slug" :data-lang="langslug" :data-history-changed="isChangedFromHistory" class="field-wrapper" :class="{ 'is-changed-from-history' : isChangedFromHistory }">
        <string-field
            v-if="!hasComponent && (isString || isPassword)"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </string-field>

        <number-field
            v-if="!hasComponent && isNumber"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </number-field>

        <date-time-field
            v-if="!hasComponent && isDatepicker"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </date-time-field>

        <checkbox-field
            v-if="!hasComponent && isCheckbox"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </checkbox-field>

        <text-field
            v-if="!hasComponent && (isText || isEditor)"
            :id="getId"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </text-field>

        <file-field
            v-if="!hasComponent && isFile"
            :id="getId"
            :row="row"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </file-field>

        <select-field
            v-if="!hasComponent && isSelect"
            :id="getId"
            :row="row"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :inputlang="inputlang"
            :langid="langid"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </select-field>

        <radio-field
            v-if="!hasComponent && isRadio"
            :id="getId"
            :model="model"
            :field_name="getName"
            :field_key="getFieldName"
            :field="field"
            :value="getValueOrDefault"
            :inputlang="inputlang"
            :langid="langid"
            :required="isRequired"
            :disabled="isDisabled"
            :depth_level="depth_level">
        </radio-field>

        <!-- Row Confirmation -->
        <form-input-builder
            v-if="field.confirmed == true && !isConfirmation"
            :model="model"
            :history="history"
            :field="field"
            :index="index"
            :field_key="field_key + '_confirmation'"
            :row="row"
            :depth_level="depth_level"
            :confirmation="true"></form-input-builder>

        <component
            v-if="hasComponent"
            :model="model"
            :field="field"
            :history_changed="isChangedFromHistory"
            :row="row"
            :field_key="field_key"
            :is="componentName">
        </component>
    </div>
</template>

<script>
    import StringField from '../Fields/StringField';
    import NumberField from '../Fields/NumberField';
    import DateTimeField from '../Fields/DateTimeField';
    import CheckboxField from '../Fields/CheckboxField';
    import TextField from '../Fields/TextField';
    import FileField from '../Fields/FileField';
    import SelectField from '../Fields/SelectField';
    import RadioField from '../Fields/RadioField';

    export default {
        name: 'form-input-builder',
        props: ['model', 'field', 'field_key', 'index', 'row', 'confirmation', 'history', 'langid', 'inputlang', 'hasparentmodel', 'langslug', 'depth_level'],

        components: { StringField, NumberField, DateTimeField, CheckboxField, TextField, FileField, SelectField, RadioField },

        created(){
            this.registerComponents();
        },

        mounted()
        {
            //If this field has own component
            this.syncFieldsValueWithRow();
        },
        methods : {
            trans(key){
                return this.$root.trans(key);
            },
            parseArrayValue(value){
                if ( $.isArray(value) )
                {
                    for ( var key in value )
                    {
                        if ( $.isNumeric( value[key] ) )
                            value[key] = parseInt( value[key] );
                    }
                }

                return value;
            },
            getLocalizedValue(value, defaultValue){
                if ( ! this.hasLocale )
                    return value||null;

                if ( value && this.langslug in value )
                    return value[this.langslug];

                return defaultValue||null;
            },
            //We need reset empty values because of infinity loop in setter
            //when is NaN setted
            resetEmptyValue(value){
                if ( value === undefined || _.isNaN(value) )
                    return null;

                return value;
            },
            syncFieldsValueWithRow(){
                this.$watch('row.'+this.field_key, value => {
                    this.field.value = this.resetEmptyValue(value);
                });

                this.$watch('field.value', value => {
                    this.row[this.field_key] = this.resetEmptyValue(value);
                });
            },
            registerComponents(){
                if ( !('component' in this.field) )
                    return;

                var components = this.field.component.split(','),
                        component = null;

                for ( var i = 0; i < components.length; i++ )
                {
                    var name = components[i].toLowerCase(),
                        data = this.model.components[name],
                        obj;

                    try {
                        obj = this.$root.getComponentObject(data);
                    } catch(error){
                        console.error('Syntax error in component ' + component[i] + '.Vue' + "\n", error);
                        continue;
                    }

                    if ( ! component )
                        component = obj;
                    else {
                        if ( !('components' in component) )
                            component.components = {};

                        component.components[components[i]] = obj;
                    }
                }

                if ( component )
                    Vue.component(this.componentName, component);
            },
            defaultFieldValue(field){
                var default_value = field.value||field.default;

                if (
                    ! default_value
                    || (['number', 'string', 'boolean'].indexOf(typeof default_value) === -1 && !this.isMultipleField(field))
                ) {
                    return '';
                }

                //If is current date value in datepicker
                if ( field.default && this.isDatepickerField(field) && field.default.toUpperCase() == 'CURRENT_TIMESTAMP' )
                    default_value = moment().format(this.$root.fromPHPFormatToMoment(field.date_format));

                //Get value by other table
                if ( field.default )
                {
                    var default_parts = field.default.split('.');

                    if ( default_parts.length == 2 )
                    {
                        var model = this.getModelBuilder(default_parts[0]);

                        if ( model && (default_parts[1] in model.row) )
                            return model.row[default_parts[1]];
                    }
                }

                return default_value||'';
            },
            /*
             * Apply event on changed value
             */
            changeValue(e, value, no_field){
                var value = e ? e.target.value : value;

                if ( this.field.type == 'checkbox' )
                    value = e ? e.target.checked : value;

                //Update specific language field
                if ( this.hasLocale ){
                    var obj_value = typeof this.field.value === 'object' ? this.field.value||{} : {};
                        obj_value[this.langslug] = value;

                    //Update specific row language value
                    this.$set(this.row, this.field_key, obj_value);
                    return;
                }

                //Update field values
                if ( no_field != true )
                    this.field.value = value;

                this.$set(this.row, this.field_key, value);
            },
            //Get parent model builder
            getModelBuilder(slug, except){
                var modelBuilder = this.$parent,
                    except = slug === '$parent' ? this.model.slug : null,
                    slug = slug === '$parent' ? null : slug;

                while(modelBuilder && (
                    modelBuilder.$options.name != 'model-builder'
                    || (slug && modelBuilder.model.slug != slug)
                    || (except && modelBuilder.model.slug === except)
                ))
                    modelBuilder = modelBuilder.$parent;

                if ( slug && (!modelBuilder || modelBuilder.model.slug != slug) ){
                    console.error('Model with table name "' + slug + '" does not exists in parents tree of models');

                    return null;
                }

                return modelBuilder;
            },
            isMultipleField(field){
                return field.multiple && field.multiple === true || ('belongsToMany' in field);
            },
            isDatepickerField(field){
                return ['date', 'datetime', 'time'].indexOf(field.type) > -1;
            }
        },

        computed : {
            isOpenedRow(){
                return this.row && 'id' in this.row;
            },
            getId()
            {
                //Get parent model builder
                var modelBuilder = this.getModelBuilder();

                parent = modelBuilder.getParentTableName(this.model.withoutParent == true);

                return 'id-' + this.model.slug + this.field_key + '-' + this.depth_level + '-' + parent + '-' + this.index + '-' + this.langslug;
            },
            getFieldKey()
            {
                return this.model.slug + '-' + this.field_key;
            },
            getFieldName()
            {
                if ( this.hasLocale )
                    return this.field_key+'['+this.langslug+']';

                return this.field_key;
            },
            getName()
            {
                //Return confirmation name
                if ( this.isConfirmation )
                    return this.field.name + ' ('+this.trans('confirmation')+')';

                return this.field.name;
            },
            isString()
            {
                return this.field.type == 'string';
            },
            isNumber()
            {
                return ['integer', 'decimal'].indexOf(this.field.type) > -1;
            },
            isText()
            {
                return this.field.type == 'text' || this.field.type == 'longtext';
            },
            isEditor()
            {
                return this.field.type == 'editor'  || this.field.type == 'longeditor';
            },
            isFile()
            {
                return this.field.type == 'file';
            },
            isPassword()
            {
                return this.field.type == 'password';
            },
            isSelect()
            {
                return this && this.field.type == 'select';
            },
            isRadio()
            {
                return this.field.type == 'radio';
            },
            isConfirmation()
            {
                return this.confirmation == true;
            },
            isDatepicker()
            {
                return this.isDatepickerField(this.field);
            },
            isCheckbox()
            {
                return this.field.type == 'checkbox';
            },
            isDisabled()
            {
                return this.field.disabled == true;
            },
            isMultiple()
            {
                return this.isMultipleField(this.field);
            },
            hasComponent(){
                return 'component' in this.field && this.field.component;
            },
            componentName(){
                if ( ! this.hasComponent )
                    return;

                return this.field.component.split(',')[0].toLowerCase();
            },
            getValueOrDefault()
            {
                //If is password, return none value
                if ( this.isPassword ){
                    return this.field.value;
                }

                var value = this.parseArrayValue(this.field.value);

                if ( this.isMultipleDatepicker )
                    return JSON.stringify(value||[]);

                //Localization field
                if ( this.hasLocale )
                    return this.getLocalizedValue(value, this.defaultFieldValue(this.field));

                //If row is not opened, then return default field value
                if ( ! this.isOpenedRow ){
                    return this.defaultFieldValue(this.field);
                }

                return value;
            },
            isRequired(){
                if ( this.isOpenedRow && this.field.type == 'password' )
                    return false;

                //Basic required attribute
                if ( 'required' in this.field && this.field.required == true )
                    return true;

                //Required if attribute
                if ( this.field.required_if )
                {
                    var parts = this.field.required_if.split(','),
                            value = this.row[parts[0]];

                    if (value && parts.slice(1).indexOf(value) > -1)
                        return true;
                }

                //Required without attribute
                if ( this.field.required_without )
                {
                    var parts = this.field.required_without.split(',');

                    for ( var i = 0; i < parts.length; i++ )
                    {
                        if ( ! this.row[parts[i]] )
                            return true;
                    }
                }

                //Required without attribute
                if ( this.field.required_with )
                {
                    var parts = this.field.required_with.split(',');

                    for ( var i = 0; i < parts.length; i++ )
                    {
                        if ( this.row[parts[i]] )
                            return true;
                    }
                }

                return false;
            },
            hasLocale(){
                return 'locale' in this.field;
            },
            isChangedFromHistory(){
                if ( ! this.history )
                    return false;

                return this.history.fields.indexOf(this.field_key) > -1;
            },
        },
    }
</script>