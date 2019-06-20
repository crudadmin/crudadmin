<template>
  <div>
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        Dashboard
        <small>{{ trans('welcome-in-admin') }}</small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> {{ trans('admin') }}</a></li>
        <li class="active">Dashboard</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content" v-show="user">
      <div class="row">
        <!-- left column -->
        <div class="col-md-12">

          <!-- Horizontal Form -->
          <div class="box box-info">


            <div class="box-body">
              <h2 v-if="!layout && user">{{ trans('welcome') }} {{ user.username }}</h2>
              <div v-html="layout"></div>
            </div>
          </div>
          <!-- /.box -->

        </div>
        <!--/.col (left) -->
      </div>
      <!-- /.row -->
    </section>
    <!-- /.content -->
  </div>
</template>

<script>
    export default {
      props : ['langid'],

      mounted(){

      },

      computed: {
        user(){
          return this.$root.user;
        },
        layout(){
          this.$nextTick(() => {
            this.$root.runInlineScripts(this.$root.dashboard)
          });

          return this.$root.dashboard;
        },
      },

      methods: {
        trans(key){
          return this.$root.trans(key);
        }
      }
    }
</script>