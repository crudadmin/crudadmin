<template>
  <div v-if="fieldValue != null">

    <!-- File -->
    <div v-if="isFile(field)" class="filesList">
      <div v-for="file in getFiles(item, field)">
        <file :file="file" :field="field" :model="model" :image="image"></file> <span>, </span>
      </div>
    </div>

    <!-- Table value -->
    <span v-else data-toggle="{{ fieldValue.length > 20 ? 'tooltip' : '' }}" data-original-title="{{ fieldValue | encodedTitle field }}">{{{ fieldValue | stringLimit field | encodeValue field }}}</span>

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

        if ( limit != 0 && string.length > limit )
          return string.substr(0, limit) + '...';

        return string;
      },
      encodeValue(string, key){
        var isReal = this.isRealField(key);

        //Check if column can be encoded
        if ( isReal && this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) == true )
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
      encodedTitle(string, key){
        if ( this.$root.getModelProperty(this.model, 'settings.columns.'+key+'.encode', true) === false )
          return '';

        return string;
      }
    },

    computed: {
      fieldValue()
      {
        var field = this.field,
            row = this.item;

        //Get select original value
        if ( ( field in this.model.fields ) )
        {
          var isRadio = this.model.fields[field].type == 'radio';

          if ( this.model.fields[field].type == 'select' || isRadio )
          {
            if ( 'multiple' in this.model.fields[field] && this.model.fields[field].multiple == true && $.isArray(row[field]) && !isRadio )
            {
              var values = [],
                  rows = row[field],
                  options = this.getLanguageSelectOptions( this.model.fields[field].options );

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
              var options = isRadio ? this.model.fields[field].options : this.getLanguageSelectOptions( this.model.fields[field].options );

              //Check if key exists in options
              if ( ! options )
                return row[field];

              for ( var i = 0; i < options.length; i++ )
              {
                if ( row[field] == options[i][0] )
                  return options[i][1];
              }
            }
          } else if ( this.model.fields[field].type == 'checkbox' )
          {
            return row[field] == 1 ? this.trans('yes') : this.trans('no');
          }
        }

        var add_before = this.$root.getModelProperty(this.model, 'settings.columns.'+this.field+'.add_before'),
            add_after = this.$root.getModelProperty(this.model, 'settings.columns.'+this.field+'.add_after');

        return row[field] || row[field] == 0 ? ((add_before||'') + row[field] + (add_after||'')) : null;
      },
    },

    methods: {
      getLanguageSelectOptions(array){
        return this.$root.languageOptions(array, this.model.fields[this.field], this.model.localization ? {
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