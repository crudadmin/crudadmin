<!DOCTYPE html>
<!--
This is a starter template page. Use this page to start your new project from
scratch. This page gets rid of all links and provides the needed markup only.
-->
<html lang="<?php echo app()->getLocale() ?>">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="csrf-token" content="<?php echo csrf_token() ?>">
  <meta name="root" content="<?php echo asset('admin') ?>">
  <title><?php echo config('admin.name') ?> - <?php echo trans('admin::admin.admin') ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.5 -->
  <link rel="stylesheet" href="<?php echo admin_asset('/bootstrap/css/bootstrap.min.css') ?>">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?php echo admin_asset('/plugins/font-awesome/css/font-awesome.min.css') ?>">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?php echo admin_asset('/plugins/lightbox/lightbox.min.css') ?>">
  <link rel="stylesheet" href="<?php echo admin_asset('/plugins/datatables/dataTables.bootstrap.css') ?>">
  <link rel="stylesheet" href="<?php echo admin_asset('/plugins/chosen/chosen.css?v=').Admin::getAssetsVersion() ?>">
  <link rel="stylesheet" href="<?php echo admin_asset('/plugins/datetimepicker/jquery.datetimepicker.css?v=').Admin::getAssetsVersion() ?>">
  <link rel="stylesheet" href="<?php echo admin_asset('/dist/css/AdminLTE.css?v=').Admin::getAssetsVersion() ?>">
  <link rel="stylesheet" href="<?php echo admin_asset('/css/style.css?v=').Admin::getAssetsVersion() ?>">
  <!-- AdminLTE Skins. We have chosen the skin-blue for this starter
        page. However, you can choose any other skin. Make sure you
        apply the skin class to the body tag so the changes take effect.
  -->
  <link rel="stylesheet" href="<?php echo admin_asset('/dist/css/skins/skin-blue.min.css') ?>">

  <?php foreach(array_merge((array)config('admin.styles', []), ((($customCssPath = public_path('/assets/admin/css/custom.css')) && file_exists($customCssPath)) ? [ asset('/assets/admin/css/custom.css') ] : [])) as $css){ ?>
  <link rel="stylesheet" type="text/css" href="<?php echo admin_asset($css, true) ?>">
  <?php } ?>
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<body class="hold-transition skin-blue sidebar-mini">
    <div id="app">
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
                          {{ user.username }} - {{ getPermissions }}
                        </p>
                      </li>
                      <!-- Menu Body -->
                      <li class="user-body">

                      </li>
                      <!-- Menu Footer-->
                      <li class="user-footer">
                        <div class="pull-right">
                          <a href="<?php echo action('\Gogol\Admin\Controllers\Auth\LoginController@logout'); ?>" class="btn btn-default btn-flat"><?php echo trans('admin::admin.logout') ?></a>
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
          <div class="example-modal message-modal" v-if="canShowAlert">
            <div class="modal" :class="'modal-'+alert.type" v-bind:style="{ display : canShowAlert ? 'block' : 'none' }">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <button type="button" v-on:click="closeAlert( alert.close )" class="close" data-dismiss="modal" aria-label="Close">
                      <span aria-hidden="true">×</span>
                    </button>
                    <h4 class="modal-title">{{ alert.title }}</h4>
                  </div>
                  <div class="modal-body">
                    <p v-if="alert.message" v-html="alert.message"></p>
                    <component
                      v-if="alert.component"
                      :model="alert.component.model"
                      :rows="alert.component.rows"
                      :row="alert.component.row"
                      :request="alert.component.request"
                      :data="alert.component.data"
                      :is="getComponentName(alert.component.name)">
                  </div>
                  <div class="modal-footer">
                    <button type="button" v-on:click="closeAlert( alert.close )" v-if="alert.close || alert.type=='success' && !alert.close || !alert.close && !alert.success" v-bind:class="{ 'btn' : true, 'btn-outline' : true, 'pull-left' : alert.success }" data-dismiss="modal"><?php echo trans('admin::admin.close') ?></button>
                    <button type="button" v-on:click="closeAlert( alert.success )" v-if="alert.success" class="btn btn-outline"><?php echo trans('admin::admin.accept') ?></button>
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
            <license></license>
            <check-assets-version></check-assets-version>

            <router-view :langid="language_id"></router-view>
          </div>
          <!-- END CONTENT -->

          <!-- Main Footer -->
          <footer class="main-footer">
            <!-- To the right -->
            <div class="pull-right hidden-xs">
              Version <a target="_blank" v-bind:href="'https://packagist.org/packages/marekgogol/crudadmin#'+version">{{ version }}</a>
            </div>
            <!-- Default to the left -->
            <strong>
              &copy; <?php echo date('Y') > config('admin.author.since', 2016) ? config('admin.author.since', 2016) . ' - '.date('Y') : date('Y') ?> <a href="<?php echo config('admin.author.url', 'http://marekgogol.sk') ?>" target="_blank"><?php echo config('admin.author.name', 'CrudAdmin') ?></a>
              <?php if ( config('admin.author', true) !== false ){ ?>
              system by <a href="<?php echo config('admin.author.url', 'http://marekgogol.sk') ?>" target="_blank"><?php echo config('admin.author.copyright', 'Marek Gogoľ') ?></a>.
              <?php } ?>
            </strong>
          </footer>

          <div class="control-sidebar-bg"></div>
        </div>
        <!-- ./wrapper -->

        <!-- REQUIRED JS SCRIPTS -->
        <div id="loader" v-bind:class="{ hidenloader : true }">
            <div class="spinner">
                <h2><strong><?php echo config('admin.author.name', 'CrudAdmin') ?></strong> <span>&copy;</span> <?php echo date('Y') > config('admin.author.since', 2016) ? config('admin.author.since', 2016) . ' - '.date('Y') : date('Y') ?>
                <?php if ( config('admin.author', true) !== false ){ ?>
                by <?php echo config('admin.author.copyright', 'Marek Gogoľ') ?>
                <?php } ?></h2>
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
        </div>
    </div>

    <!-- Admin variables -->
    <script type="text/javascript">
      window.crudadmin = {
        path : '<?php echo Admin::getAdminAssetsPath() ?>',
        dev : <?php echo env('APP_DEBUG') ? 'true' : 'false' ?>
      };
    </script>

    <!-- jQuery 2.1.4 -->
    <script src="<?php echo admin_asset('/plugins/jQuery/jQuery-2.1.4.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/plugins/jQueryUI/jquery-ui.min.js') ?>"></script>

    <!-- Bootstrap 3.3.5 -->
    <script src="<?php echo admin_asset('/bootstrap/js/bootstrap.min.js') ?>"></script>

    <!-- AdminLTE App -->
    <script src="<?php echo admin_asset('/plugins/datetimepicker/jquery.datetimepicker.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/plugins/lightbox/lightbox.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/plugins/ckeditor/ckeditor.js') ?>"></script>
    <script src="<?php echo admin_asset('/plugins/chosen/chosen.jquery.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/plugins/chosen-order/chosen.order.jquery.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/dist/js/app.min.js') ?>"></script>
    <script src="<?php echo admin_asset('/dist/js/main.js?v=').Admin::getAssetsVersion() ?>">"></script>

    <?php foreach((array)config('admin.scripts', []) as $script){ ?>
    <script type="text/javascript" src="<?php echo admin_asset($script) ?>"></script>
    <?php } ?>

    <!-- APP JS -->
    <script src="<?php echo admin_asset('/js/main.js?v=' . (Admin::getVersion() == 'dev-master' ? rand(00000, 99999) : Admin::getAssetsVersion() ) ) ?>"></script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-42935841-6"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());

      gtag('config', 'UA-42935841-6');
    </script>

    <?php
    //Slot into template
    if ( file_exists($path = resource_path('views/vendor/crudadmin/slot.php')) )
      include_once($path);
    ?>

  </body>
</html>