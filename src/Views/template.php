<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="<?php echo csrf_token() ?>">
  <meta name="root" content="<?php echo asset('admin') ?>">
  <title><?php echo config('admin.name') ?> - Administrácia</title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.5 -->
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/bootstrap/css/bootstrap.min.css') ?>">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/plugins/lightbox/lightbox.min.css') ?>">
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/plugins/datatables/dataTables.bootstrap.css') ?>">
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/plugins/datepicker/datepicker3.css') ?>">
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/dist/css/AdminLTE.css') ?>">
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/css/style.css') ?>">
  <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
        page. However, you can choose any other skin. Make sure you
        apply the skin class to the body tag so the changes take effect.
  -->
  <link rel="stylesheet" href="<?php echo asset('/assets/admin/dist/css/skins/skin-blue.min.css') ?>">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>

<body class="hold-transition skin-blue sidebar-mini" id="app">
    <div class="wrapper">

      <!-- Main Header -->
      <header class="main-header">

        <!-- Logo -->
        <a href="#" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini"><?php echo config('admin.name') ?></span>
          <!-- logo for regular state and mobile devices -->
          <span class="logo-lg"><?php echo config('admin.name') ?></span>
        </a>

        <!-- Header Navbar -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>
          <!-- Navbar Right Menu -->
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">

              <!-- User Account Menu -->
              <li class="dropdown user user-menu">
                <!-- Menu Toggle Button -->
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                  <!-- The user image in the navbar-->
                  <img v-bind:src="getAvatar" class="user-image" alt="User Image">
                  <!-- hidden-xs hides the username on small devices so only the image appears. -->
                  <span class="hidden-xs">{{ user.username }}</span>
                </a>
                <ul class="dropdown-menu">
                  <!-- The user image in the menu -->
                  <li class="user-header">
                    <img v-bind:src="getAvatar" class="img-circle" alt="User Image">

                    <p>
                      {{ user.username }} - Administrátor
                    </p>
                  </li>
                  <!-- Menu Body -->
                  <li class="user-body">

                  </li>
                  <!-- Menu Footer-->
                  <li class="user-footer">
                    <div class="pull-right">
                      <a href="<?php echo action('\Gogol\Admin\Controllers\Auth\LoginController@logout'); ?>" class="btn btn-default btn-flat">Odhlásiť</a>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <!-- Left side column. contains the logo and sidebar -->
      <aside class="main-sidebar">

        <!-- sidebar: style can be found in sidebar.less -->
        <section class="sidebar">

          <!-- Sidebar user panel (optional) -->
          <div class="user-panel">
            <div class="pull-left image">
              <img v-bind:src="getAvatar" class="img-circle" alt="User Image">
            </div>
            <div class="pull-left info">
              <p>{{ user.username }}</p>
              <!-- Status -->
              <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
          </div>

          <sidebar :rows="models" :languages="languages" :langid.sync="language_id"></sidebar>
      </aside>

      <!-- MODAL -->
      <div class="example-modal" v-if="canShowAlert">
        <div class="modal modal-{{ alert.type }}" v-bind:style="{ display : canShowAlert ? 'block' : 'none' }">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" v-on:click="closeAlert( alert.close )" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">×</span>
                </button>
                <h4 class="modal-title">{{ alert.title }}</h4>
              </div>
              <div class="modal-body">
                <p>{{{ alert.message }}}</p>
              </div>
              <div class="modal-footer">
                <button type="button" v-on:click="closeAlert( alert.close )" v-if="alert.close" v-bind:class="{ 'btn' : true, 'btn-outline' : true, 'pull-left' : alert.success }" data-dismiss="modal">Zrušiť</button>
                <button type="button" v-on:click="closeAlert( alert.success )" v-if="alert.success" class="btn btn-outline">Potvrdiť</button>
              </div>
            </div>
            <!-- /.modal-content -->
          </div>
          <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
      </div>

      <!-- Your Page Content Here -->
      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <router-view :langid="language_id"></router-view>
      </div>
      <!-- END CONTENT -->

      <!-- Main Footer -->
      <footer class="main-footer">
        <!-- To the right -->
        <div class="pull-right hidden-xs">
          Enjoy it!
        </div>
        <!-- Default to the left -->
        <strong>Copyright &copy; <?php echo date('Y') > 2016 ? '2016 - '.date('Y') : date('Y') ?> <a href="http://marekgogol.sk" target="_blank">Marek Gogoľ</a>.</strong> All rights reserved.
      </footer>

      <div class="control-sidebar-bg"></div>
    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED JS SCRIPTS -->
    <div id="loader" v-bind:class="{ hidenloader : true }">
        <div class="spinner">
            <h2><strong>Super Admin</strong> <span>&copy;</span> <?php echo date('Y') > 2016 ? '2016 - '.date('Y') : date('Y') ?> by Marek Gogoľ</h2>
            <div class="bounce1"></div>
            <div class="bounce2"></div>
            <div class="bounce3"></div>
        </div>
    </div>

    <!-- jQuery 2.1.4 -->
    <script src="<?php echo asset('/assets/admin/plugins/jQuery/jQuery-2.1.4.min.js') ?>"></script>
    <script src="<?php echo asset('/assets/admin/plugins/jQueryUI/jquery-ui.min.js') ?>"></script>

    <!-- Bootstrap 3.3.5 -->
    <script src="<?php echo asset('/assets/admin/bootstrap/js/bootstrap.min.js') ?>"></script>

    <!-- AdminLTE App -->
    <script src="<?php echo asset('/assets/admin/plugins/datepicker/bootstrap-datepicker.js') ?>"></script>
    <script src="<?php echo asset('/assets/admin/plugins/lightbox/lightbox.min.js') ?>"></script>
    <script src="<?php echo asset('/assets/admin/plugins/ckeditor/ckeditor.js') ?>"></script>
    <script src="<?php echo asset('/assets/admin/dist/js/app.min.js') ?>"></script>
    <script src="<?php echo asset('/assets/admin/dist/js/main.js') ?>"></script>

    <!-- APP JS -->
    <script src="<?php echo asset('/assets/admin/js/main.js?') ?>"></script>
  </body>
</html>