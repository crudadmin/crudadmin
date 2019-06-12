const mix = require('laravel-mix');

//Where sould be compiled assets
var config = require('./config.js');

mix.js('src/Resources/js/app.js', 'src/Resources/admin/js')
   .extract([
        'vue', 'jquery', 'axios', 'lodash', 'js-md5', 'moment', 'vue-router', 'vue-fragment',
        'vue-resource', 'vuedraggable', 'jquery-datetimepicker', 'bootstrap-sass'
    ])

for ( key in config.paths )
{
    mix.copy('src/Resources/admin/js/manifest.js', config.paths[key] + '/manifest.js')
       .copy('src/Resources/admin/js/vendor.js', config.paths[key] + '/vendor.js')
       .copy('src/Resources/admin/js/app.js', config.paths[key] + '/app.js');
}
