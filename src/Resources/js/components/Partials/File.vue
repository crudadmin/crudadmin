<template>
  <a v-if="isImage(file) && image != true" href="{{ path }}" data-lightbox="gallery" title="">Zobraziť obrázok</a>
  <a v-if="isImage(file) && image == true" href="{{ path }}" data-lightbox="gallery" title=""><img v-bind:src="imagePath" alt=""></a>
  <a v-if="isPdf(file)" href="{{ path }}" target="_blank" title="">Zobraziť pdf</a>
  <a v-if="isZip(file)" href="{{ downloadPath }}" target="_blank" title="">Stiahnuť ZIP</a>
  <a v-if="isDoc(file)" href="{{ downloadPath }}" target="_blank" title="">Stiahnuť dokument</a>
  <a v-if="isOther(file)" href="{{ downloadPath }}" target="_blank" target="_blank" title="">Stiahnuť súbor</a>
</template>

<script>
    export default {
        props: ['file', 'field', 'model', 'image'],

        methods : {
            isExtension(path, types){
              var type = path.split('.').pop().toLowerCase();

              if (types.indexOf( type ) > -1)
                return true;
              else
                return false;
            },
            isImage(path)
            {
              return this.isExtension(path, ['jpg', 'jpeg', 'png', 'bmp', 'gif']);
            },
            isPdf(path)
            {
              return this.isExtension(path, ['pdf']);
            },
            isZip(path)
            {
              return this.isExtension(path, ['zip', 'rar', '7zip', 'gzip', '7z']);
            },
            isDoc(path)
            {
              return this.isExtension(path, ['doc', 'docx', 'ppt', 'pptx', 'xls', 'txt']);
            },
            isOther(path)
            {
              return !(this.isImage(path) || this.isPdf(path) || this.isZip(path) || this.isDoc(path));
            }
        },
        computed : {
          downloadPath(){
            return this.$root.requests.download + '?model=' + encodeURIComponent(this.model.slug) + '&field=' + encodeURIComponent(this.field) + '&file=' + encodeURIComponent(this.file);
          },
          imagePath(){
            return this.$root.$http.options.root + '/../uploads/cache/' + this.model.slug + '/' + this.field + '/admin-thumbnails/' + this.file;
          },
          path(){
            return this.$root.$http.options.root + '/../uploads/' + this.model.slug + '/' + this.field + '/' + this.file;
          }
        }
    }
</script>