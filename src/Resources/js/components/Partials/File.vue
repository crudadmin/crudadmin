<template>
  <a v-if="isImage(uploadpath)" href="{{ path }}" data-lightbox="gallery" title="">Zobraziť fotografiu</a>
  <a v-if="isPdf(uploadpath)" href="{{ path }}" target="_blank" title="">Zobraziť pdf</a>
  <a v-if="isZip(uploadpath)" href="{{ downloadPath }}" target="_blank" title="">Stiahnuť ZIP</a>
  <a v-if="isDoc(uploadpath)" href="{{ downloadPath }}" target="_blank" title="">Stiahnuť dokument</a>
  <a v-if="isOther(uploadpath)" href="{{ downloadPath }}" target="_blank" target="_blank" title="">Stiahnuť súbor</a>
</template>

<script>
    export default {
        props: ['uploadpath'],
        methods : {
            isExtension(path, types){
              var type = path.split('.').pop();

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
            return this.$root.requests.download + '?file=' + encodeURIComponent(this.uploadpath);
          },
          path(){
            return this.$root.$http.options.root + '/../uploads/' + this.uploadpath;
          }
        }
    }
</script>