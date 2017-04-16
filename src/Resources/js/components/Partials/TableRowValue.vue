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
        if ( !( key in this.model.fields ) )
          return string;

        var field = this.model.fields[key],
            limit = 'limit' in field ? field.limit : 20;

        if ( limit != 0 && string.length > limit )
          return string.substr(0, limit) + '...';

        return string;
      },
      encodeValue(string, key){
        if ( !( key in this.model.fields ) )
          return string;

        var string = $(document.createElement('div')).text(string).html();

        if ( this.model.fields[key].type == 'text' && parseInt(this.model.fields[key].limit) === 0)
        {
          return string.replace(/\n/g, '<br>');
        }

        //Is phone number
        if ( this.model.fields[key].type == 'string' && ('phone' in this.model.fields[key]))
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
              if ( row[field] in this.getLanguageSelectOptions( this.model.fields[field].options ) )
                return this.getLanguageSelectOptions( this.model.fields[field].options )[ row[field] ];
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

        //Checks if values are devided by language
        var localization = false;

        for ( var key in array )
        {
          if (array[key] !== null && typeof array[key] === 'object')
          {
            localization = true;
            break;
          }
        }

        return localization ? array[ this.$root.language_id ] : array;
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
    }
}
</script>