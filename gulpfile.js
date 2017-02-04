var elixir = require('laravel-elixir');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for our application, as well as publishing vendor resources.
 |
 */

require('laravel-elixir-vueify');

//Gulp watch for browserify
elixir.config.js.browserify.watchify.options.poll = true;
elixir.config.production = false;

elixir(function(mix) {

    mix.browserify('src/Resources/js/main.js', '../../../public/assets/admin/js', './')
       // .browserify('src/Resources/js/main.js', 'src/Resources/admin/js', './');

});