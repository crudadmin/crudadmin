<template>
  <div v-if="fieldValue != null">

    <!-- File -->
    <div v-if="isFile(field)" class="filesList">
      <div v-for="file in getFiles(item, field)">
        <file :file="file" :field="field" :model="model" :image="image"></file> <span>, </span>
      </div>
    </div>

    <!-- Table value -->
    <span v-else :data-toggle="fieldValue.length > 20 ? 'tooltip' : ''" :data-original-title="fieldValue | encodedTitle field true">{{{ fieldValue | stringLimit field | encodeValue field }}}</span>

  </div>
</template>

<script>
import File from '../Partials/File.vue';

export default {
    props : ['model', 'item', 'field', 'name', 'image'],

    components : { File },

    filters: {
      stringLimit(string, key){
        var limit = this.getFieldLimit(key, Object.keys(this.$parent.columns).length < 5 ? 40 : 20);

        if ( limit != 0 && string.length > limit && this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) !== false )
          return string.substr(0, limit) + '...';

        return string;
      },
      encodeValue(string, key, is_title){
        var isReal = this.isRealField(key);

        //Check if column can be encoded
        if ( isReal && this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) == true )
        {
          string = $(document.createElement('div')).text(string).html();
        }

        if ( is_title && this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) === false )
          return '';

        if ( this.isRealField(key) && this.model.fields[key].type == 'text' && parseInt(this.model.fields[key].limit) === 0)
        {
          return string.replace(/\n/g, '<br>');
        }

        //Is phone number
        if ( this.isRealField(key) && this.model.fields[key].type == 'string' && ('phone' in this.model.fields[key] || 'phone_link' in this.model.fields[key]) )
        {
          return '<a href="tel:'+string+'">'+string+'</a>';
        }

        return string;
      },
      encodedTitle(string, key){
        if ( this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) === false )
          return '';

        return string;
      }
    },

    computed: {
      fieldValue()
      {
        var field = this.field in this.model.fields ? this.model.fields[this.field] : null,
            row = this.item,
            rowValue = this.field in row ? this.getMutatedValue(row[this.field], field) : '';

        //Get select original value
        if ( field )
        {
          var isRadio = field.type == 'radio';

          if ( field.type == 'select' || isRadio )
          {
            if ( 'multiple' in field && field.multiple == true && $.isArray(rowValue) && !isRadio )
            {
              var values = [],
                  rows = rowValue,
                  related_table = this.getRelatedModelTable(field),
                  options = ! related_table ? field.options : this.getLanguageSelectOptions( field.options, this.getRelatedModelTable(field) );

              for ( var i = 0; i < rows.length; i++ )
              {
                var searched = options.filter(function(item){
                  return item[0] == rows[i];
                }.bind(this));

                if ( searched.length > 0 )
                  values.push( searched[0][1] );
              }

              return values.join(', ');
            } else {
              var related_table = this.getRelatedModelTable(field),
                  options = isRadio || !related_table ? field.options : this.getLanguageSelectOptions( field.options, related_table );

              //Check if key exists in options
              if ( ! options )
                return rowValue;

              for ( var i = 0; i < options.length; i++ )
              {
                if ( rowValue == options[i][0] )
                  return options[i][1];
              }
            }
          }

          else if ( field.type == 'checkbox' )
          {
            return rowValue == 1 ? this.trans('yes') : this.trans('no');
          }

          //Multi date format values
          else if ( field.type == 'date' && field.multiple === true ) {
            rowValue = (rowValue||[]).map(item => {
              var date = moment(item);

              return date._isValid ? date.format('DD.MM.YYYY') : item;
            }).join(', ');
          }

          //Multi time format values
          else if ( field.type == 'time' && field.multiple === true ) {
            rowValue = (rowValue||[]).join(', ');
          }
        }

        var add_before = this.$root.getModelProperty(this.model, 'settings.columns.'+this.field+'.add_before'),
            add_after = this.$root.getModelProperty(this.model, 'settings.columns.'+this.field+'.add_after');

        //If is object
        if ( typeof rowValue == 'object' )
          return rowValue;

        return (rowValue || rowValue == 0) ? ((add_before||'') + rowValue + (add_after||'')) : null;
      },
    },

    methods: {
      getRelatedModelTable(field){
        var table = field.belongsTo||field.belongsToMany;

        if ( ! table )
          return false;

        return table.split(',')[0];
      },
      getMutatedValue(value, field){
        if ( field && 'locale' in field )
        {
          //Get default language
          var dslug = this.$root.languages[0].slug;

          if ( value && typeof value === 'object' )
          {
            //Get default language value
            if ( dslug in value && (value[dslug] || value[dslug] == 0) ){
              value = value[dslug];
            }

            //Get other available language
            else for ( var key in value ) {
              if ( value[key] || value[key] === 0 )
              {
                value = value[key]
                break;
              }
            }

            if ( typeof value == 'object' )
              value = '';
          }
        }

        //Return correct zero value
        if ( value === 0 )
          return 0;

        return value||'';
      },
      getLanguageSelectOptions(array, model){
        model = this.$root.models_list[model];

        return this.$root.languageOptions(array, this.model.fields[this.field], model && model.localization ? {
          language_id : this.$root.language_id,
        } : {});
      },
      isFile(field){

        if ( !(field in this.model.fields) )
          return false;

        if ( this.model.fields[field].type == 'file' )
          return true;

        return false;

      },
      getFiles(row, field){
        var value = this.fieldValue;

        if ( ! value )
          return [];

        if ( $.isArray(value) )
          return value;

        return [ value ];
      },
      isRealField(key){
        return key in this.model.fields;
      },
      getFieldLimit(key, defaultLimit){
        if ( this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) === false )
          return 0;

        if ( this.isRealField(key) )
        {
          var field = this.model.fields[key];

          return 'limit' in field ? field.limit : defaultLimit;
        } else {
          return this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.limit', defaultLimit);
        }
      },
      trans(key){
        return this.$root.trans(key);
      }
    }
}
</script>