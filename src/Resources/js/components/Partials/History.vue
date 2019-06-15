<template>
    <div class="history-table">

      <!-- MODAL -->
      <div class="example-modal">
        <div class="modal modal-default" style="display: block">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" @click="closeHistory" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">{{ trans('history.changes') }}</h4>
              </div>
              <div class="modal-body">
                  <table class="table data-table table-bordered table-striped">
                    <thead>
                      <tr>
                        <th class="td-id">{{ trans('number') }}</th>
                        <th>{{ trans('history.who') }}</th>
                        <th>{{ trans('history.count') }}</th>
                        <th>{{ trans('history.date') }}</th>
                        <th class="th-history-buttons"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(item, $index) in sortedHistory" :data-history-id="item.id">
                        <td class="td-id">{{ history.rows.length - $index }}</td>
                        <td>{{ item.user ? item.user.username : trans('history.system') }}</td>
                        <td data-changes-length>
                          <span data-toggle="tooltip" title="" :data-original-title="changedFields(item)">{{ item.changed_fields.length }} <i class="fa fa-eye"></i></span>
                        </td>
                        <td>{{ date(item.created_at) }}</td>
                        <td>
                          <div><button type="button" v-on:click="applyChanges(item)" class="btn btn-sm btn-success" v-bind:class="{ 'enabled-history' : history.history_id == item.id }" data-toggle="tooltip" :title="trans('history.show')" :data-original-title="trans('history.show-changes')"><i class="fa fa-eye"></i></button></div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
              </div>
              <div class="modal-footer">
                <button type="button" @click="closeHistory" class="btn btn-primary">{{ trans('close') }}</button>
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
      props : ['history'],

      data(){
        return {

        };
      },

      computed: {
        sortedHistory(){
          return _.orderBy(this.history.rows, 'id', 'desc');
        }
      },

      methods: {
        applyChanges(item){
            this.$parent.history.fields = item.changed_fields;
            this.$parent.history.history_id = item.id;

            eventHub.$emit('selectHistoryRow', {
              table : this.$parent.model.slug,
              row_id : this.$parent.history.id,
              history_id : item.id,
              row : this.$parent.row,
            });
        },
        date(date){
            return moment(date).format('D.M.Y H:mm');
        },
        closeHistory(){
            this.$parent.closeHistory(true);
        },
        changedFields(items){
          var changes = [];

          for ( var k in items.changed_fields )
          {
            var key = items.changed_fields[k];

            if ( key in this.$parent.model.fields )
              changes.push(this.$parent.model.fields[key].name);
            else
              changes.push(key);
          }

          return changes.join(', ');
        },
        trans(key){
          return this.$root.trans(key);
        }
      },
  }
</script>