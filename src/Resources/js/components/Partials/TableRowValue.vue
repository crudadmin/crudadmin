<template>
  <div v-if="fieldValue != null">

    <!-- File -->
    <div v-if="isFile(field)" class="filesList">
      <div v-for="file in getFiles(item, field)">
        <file :file="file" :field="field" :model="model" :image="image"></file> <span>, </span>
      </div>
    </div>

    <!-- Table value -->
    <span v-else data-toggle="{{ fieldValue.length > 20 ? 'tooltip' : '' }}" data-original-title="{{ fieldValue }}">{{{ fieldValue | stringLimit field | encodeValue field }}}</span>

  </div>
</template>

<script>
import File from '../Partials/File.vue';

export default {
    props : ['model', 'item', 'field', 'name', 'image'],

    components : { File },

    filters: {
      stringLimit(string, key){
        var limit = this.getFieldLimit(key, 20);

        if ( limit != 0 && string.length > limit )
          return string.substr(0, limit) + '...';

        return string;
      },
      encodeValue(string, key){
        var isReal = this.isRealField(key);

        //Check if column can be encoded
        if ( isReal || this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) == true )
        {
          string = $(document.createElement('div')).text(string).html();
        }

        if ( this.isRealField(key) && this.model.fields[key].type == 'text' && parseInt(this.model.fields[key].limit) === 0)
        {
          return string.replace(/\n/g, '<br>');
        }

        //Is phone number
        if ( this.isRealField(key) && this.model.fields[key].type == 'string' && ('phone' in this.model.fields[key]))
        {
          return '<a href="tel:'+string+'">'+string+'</a>';
        }

        return string;
      },
    },

    computed: {
      fieldValue()
      {
        var field = this.field,
            row = this.item;

        //Get select original value
        if ( ( field in this.model.fields ) )
        {
          if ( this.model.fields[field].type == 'select' )
          {
            if ( 'multiple' in this.model.fields[field] && this.model.fields[field].multiple == true && $.isArray(row[field]))
            {
              var values = [],
                  rows = row[field];

              for ( var i = 0; i < rows.length; i++ )
              {
                if ( rows[i] in this.getLanguageSelectOptions( this.model.fields[field].options ) )
                  values.push( this.getLanguageSelectOptions( this.model.fields[field].options )[ rows[i] ] );
              }

              return values.join(', ');
            } else {
              var options = this.getLanguageSelectOptions( this.model.fields[field].options );

              //Check if key exists in options
              if ( ! options )
                return row[field];

              for ( var i = 0; i < options.length; i++ )
              {
                if ( row[field] == options[i][0] )
                  return options[i][1];
              }
            }
          }

          if ( this.model.fields[field].type == 'checkbox' )
          {
            return row[field] == 1 ? 'Áno' : 'Nie';
          }
        }

        return row[field];
      },
    },

    methods: {
      getLanguageSelectOptions(array){
        return this.$parent.$parent.$parent.$options.filters.languageOptions(array, this.$root.language_id);
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
        if ( this.isRealField(key) )
        {
          var field = this.model.fields[key];

          return 'limit' in field ? field.limit : defaultLimit;
        } else {
          return this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.limit', defaultLimit);
        }
      }
    }
}
</script>