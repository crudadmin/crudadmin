<template>
    <div class="gettext-table modal-open">

      <!-- MODAL -->
      <div class="example-modal">
        <div class="modal modal-default" style="display: block">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" @click="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">{{ trans('gettext-update') }} - {{ gettext_editor.name }}</h4>
              </div>
              <div class="modal-body">
                  <label>{{ trans('search') }}</label>
                  <input type="text" class="form-control" placeholder="{{ trans('gettext-search') }}" v-model="query">

                  <hr>
                  <p v-if="loaded && resultLength == 0">{{ trans('gettext-no-match') }}</p>

                  <p v-if="!loaded" class="loading"><i class="fa fa-refresh fa-spin"></i> {{ trans('gettext-loading') }}</p>

                  <table v-show="resultLength > 0" class="table data-table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th>{{ trans('gettext-source') }}</th>
                        <th>{{ trans('gettext-translation') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(key, value) in filtratedTranslates">
                        <td>{{ key }}</td>
                        <td class="input" :class="{ edited : hasChange(key, value) }">
                          <textarea :class="{ long : value.length > 80 }" :value="value" @keyup="changeText($event, key)" class="form-control"></textarea>
                        </td>
                      </tr>
                    </tbody>
                  </table>

                  <a @click="limit = false" v-if="limit != false && fullCount > limit" class="all-translates">{{ trans('gettext-count') }} ({{ fullCount }})</a>
              </div>
              <div class="modal-footer">
                <button type="button" @click="saveAndClose" class="btn btn-primary">{{ trans('gettext-save') }}</button>
              </div>
            </div>
            <!-- /.modal-content -->
          </div>
          <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
      </div>
    </div>
</template>

<script>
  export default {
      name : 'gettext-extension',

      props : ['gettext_editor'],

      data(){
        return {
          translates : {},
          query : null,
          changes : {},
          limit : 20,
          loaded : false,
        };
      },

      created() {

      },

      ready() {
        this.loadTranslations();
      },

      computed: {
        filtratedTranslates(){
          var obj = {},
              query = (this.query+'').toLowerCase(),
              i = 0;

          for ( var key in this.translates )
          {
            //If is under limit, and if has query match
            if (
              (this.limit == false || i < this.limit)
              && (!this.query || this.hasChange(key) || this.translates[key].toLowerCase().indexOf(query) > -1 || key.toLowerCase().indexOf(query) > -1 )
            ){
              obj[key] = this.translates[key];

              i++;
            }
          }

          return obj;
        },
        fullCount(){
          return Object.keys(this.translates).length;
        },
        resultLength(){
          return Object.keys(this.filtratedTranslates).length;
        }
      },

      methods: {
        trans(key){
          return this.$root.trans(key);
        },
        close(){
          this.gettext_editor = null;
        },
        loadTranslations(){
          this.$http.get(this.$root.requests.translations.replace('{id}', this.gettext_editor.id)).then(function(response){
            this.translates = response.data;
            this.loaded = true;
          });
        },
        changeText(e, src){
          this.translates[src] = e.target.value;
          this.changes[src] = e.target.value;
        },
        saveAndClose(){
          this.$http.post(this.$root.requests.update_translations.replace('{id}', this.gettext_editor.id), { changes : JSON.stringify(this.changes) }).then(function(){
            this.close();
          });
        },
        //Check if value has been changed
        hasChange(key, value){
          return key in this.changes;
        }
      },
  }
</script>