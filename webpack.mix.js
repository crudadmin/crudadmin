const mix = require('laravel-mix');

//Where sould be compiled assets
var config = require('./config.js');

mix.js('src/Resources/js/main.js', 'src/Resources/admin/js');

for ( key in config.paths )
    mix.copy('src/Resources/admin/js/main.js', config.paths[key] + '/main.js');