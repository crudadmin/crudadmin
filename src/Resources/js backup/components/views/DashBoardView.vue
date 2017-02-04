<template>
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      Dashboard
      <small>Vitajte v administrácii</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Administrace</a></li>
      <li class="active">Dashboard</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <!-- left column -->
      <div class="col-md-12">

        <!-- Horizontal Form -->
        <div class="box box-info">


          <div class="box-body">
            <h2>Vitajte {{ user.username }}</h2>
          </div>


        </div>
        <!-- /.box -->

      </div>
      <!--/.col (left) -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</template>

<script>
    export default {
      props : ['langid'],
      data(){
        return {
          user : {},
        }
      },
      created() {
        this.user = this.$root.user;
      },
    }
</script>