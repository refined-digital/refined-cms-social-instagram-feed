const mix = require('laravel-mix');

mix
  .disableNotifications()
  .js('resources/js/main.js', 'assets/js/main.js')
;
