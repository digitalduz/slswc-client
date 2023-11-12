import gulp from 'gulp';
import notify from 'gulp-notify';
import beeper from 'beeper';
import plumber from 'gulp-plumber';
import sourcemaps from 'gulp-sourcemaps';
import dSass from 'sass';
import gulpSass from 'gulp-sass';
import postcss from 'gulp-postcss';
import autoprefixer from 'autoprefixer';
import mqpacker from 'css-mqpacker';
import browserSync from 'browser-sync';
import csso from 'gulp-csso';
import wpPot from 'gulp-wp-pot';
import sort from 'gulp-sort';
import uglify from 'gulp-uglify';
import zip from 'gulp-zip';
import del from 'del';
import rename from 'gulp-rename';
import cache from 'gulp-cached';
import yaml from 'js-yaml';
import fs from 'fs';
import dotenv from 'dotenv';
import path from 'path';

import * as pkg from './package.json';
const sass = gulpSass(dSass);
const _paths = {
  scss: ['./**/src/**/*.scss'],
  css: ['./**/src/**/*.css'],
  js: ['./**/src/**/*.js'],
  distJs: ['./**/assets/js/**/*.js'],
  php: ['./**/*.php'],
};
const paths = {};
for (let key in _paths)
  paths[key] = [
    ..._paths[key],
    '!./vendor/**/*',
    '!./releases/**/*',
    '!./tmp/**/*',
    '!node_modules/**/*',
    '!**/*.min.*',
    '!Gulpfile.babel.js',
    '!**/lib/**/*',
  ];

/**
 * Handle errors and alert the user.
 */
const handleErrors = (err) => {
  notify.onError({
    title: 'Task Failed [<%= error.message %>',
    message: 'See console.',
    sound: 'Sosumi', // See: https://github.com/mikaelbr/node-notifier#all-notification-options-with-their-defaults
  })(err);

  beeper(); // Beep 'sosumi' again.

  // Prevent the 'watch' task from stopping.
  //this.emit('end');
};

/**
 * Compile Sass and run stylesheet through PostCSS.
 *
 * https://www.npmjs.com/package/gulp-sass
 * https://www.npmjs.com/package/gulp-postcss
 * https://www.npmjs.com/package/gulp-autoprefixer
 * https://www.npmjs.com/package/css-mqpacker
 */
export function compileStyles() {
  return gulp
    .src(paths.scss)
    .pipe(cache('styles'))
    .pipe(plumber({ errorHandler: handleErrors }))
    .pipe(sourcemaps.init())
    .pipe(
      sass({
        includePaths: [],
        errLogToConsole: true,
        outputStyle: 'expanded', // Options: nested, expanded, compact, compressed
      })
    )
    .pipe(
      postcss([
        autoprefixer(),
        mqpacker({
          sort: true,
        }),
      ])
    )
    .pipe(
      sourcemaps.mapSources(function (sourcePath) {
        return `${path.basename(__dirname)}/${sourcePath}`;
      })
    )
    .pipe(
      sourcemaps.write('.', {
        includeContent: false,
        sourceRoot: '.',
      })
    )
    .pipe(
      rename((path) => {
        path.dirname = path.dirname.replace('src/', '');
      })
    )
    .pipe(gulp.dest('.'))
    .pipe(browserSync.stream());
}

/**
 * Minify and optimize style.css.
 *
 * https://www.npmjs.com/package/gulp-csso
 */
export function minifyStyles() {
  return gulp
    .src(paths.css)
    .pipe(cache('styles-min'))
    .pipe(plumber({ errorHandler: handleErrors }))
    .pipe(csso())
    .pipe(
      rename((path) => {
        path.basename += '.min';
      })
    )
    .pipe(gulp.dest('.'));
}

/**
 * Transform ES6+ to browser JS
 *
 * @returns {*}
 */
export function compileScripts() {
  return gulp
    .src(paths.js)
    .pipe(sourcemaps.init())
    .pipe(cache('scripts'))
    .pipe(
      rename((path) => {
        path.dirname = path.dirname.replace('src/', '');
      })
    )
    .pipe(gulp.dest('.'));
}

export function compileScriptsProduction() {
  return gulp
    .src(paths.js)
    .pipe(cache('scripts'))
    .pipe(
      rename((path) => {
        path.dirname = path.dirname.replace('src/', '');
      })
    )
    .pipe(gulp.dest('.'));
}

/**
 * Minify script files using UglifyJS
 * @returns {*}
 */
export function minifyScripts() {
  return gulp
    .src(paths.distJs)
    .pipe(cache('scripts-min'))
    .pipe(plumber({ errorHandler: handleErrors }))
    .pipe(uglify())
    .pipe(
      rename((path) => {
        path.basename += '.min';
      })
    )
    .pipe(gulp.dest('.'));
}

/**
 * Copy image from src/images to images. Tend to be used in development.
 *
 * @returns {*}
 */
export function copyImages() {
  return gulp
    .src(paths.images, { since: gulp.lastRun(copyImages) })
    .pipe(plumber({ errorHandler: handleErrors }))
    .pipe(
      rename((path) => {
        path.dirname = path.dirname.replace('src/', '');
      })
    )
    .pipe(gulp.dest('.'))
    .pipe(browserSync.stream());
}

/**
 * Scan the theme and create a POT file.
 *
 * https://www.npmjs.com/package/gulp-wp-pot
 */
export function i18n() {
  const domainName = pkg.name;
  const packageName = pkg.title;

  return gulp
    .src(paths.php)
    .pipe(plumber({ errorHandler: handleErrors }))
    .pipe(sort())
    .pipe(
      wpPot({
        domain: domainName,
        package: packageName,
      })
    )
    .pipe(gulp.dest('./languages/' + domainName + '.pot'));
}

function getProxyUrl() {
  let config;

  try {
    config = dotenv.parse(fs.readFileSync('.env'));
    if ('WP_DEV_URL' in config) return config.WP_DEV_URL;
  } catch (e) {
    // eslint-disable-next-line
    console.log('Local environment variable file not found! Use chassis URL.');
  }

  try {
    config = yaml.safeLoad(
      fs.readFileSync('../../../config.local.yaml', 'utf8')
    );
    if (config.hosts.length > 0) return config.hosts[0];
  } catch (e) {
    // eslint-disable-next-line
    console.log(
      'Chassis local config not found! Use `localhost` as proxy URL.'
    );
  }

  return 'localhost';
}

/**
 * Process tasks and reload browsers on file changes.
 *
 * https://www.npmjs.com/package/browser-sync
 */
export function watch() {
  // Kick off BrowserSync.
  browserSync({
    open: false,
    injectChanges: true,
    proxy: getProxyUrl(),
    watchOptions: {
      debounceDelay: 1000, // Wait 1 second before injecting.
    },
  });

  // Run tasks when files change.
  gulp.watch(paths.scss, compileStyles);
  gulp.watch(paths.css, minifyStyles);
  gulp.watch(paths.js, compileScripts);
  gulp.watch(paths.distJs, minifyScripts);
}

/**
 * Build dist files for release
 */
export const build = gulp.parallel(
  gulp.series(compileStyles, minifyStyles),
  gulp.series(compileScriptsProduction, minifyScripts),
  i18n
);

/**
 * Copy build files to tmp folder for creating archive
 * @returns {*}
 */
export function copyBuild() {
  return gulp
    .src(
      [
        './assets/**/*',
        './languages/*',
        './partials/**/*',
        './src/**/*.php',
        './slswc-client.php',
        './changelog.txt',
        './vendor/**/*',
        './changelog.txt',
        '!**/*.map',
        '!./assets/src',
        '!./assets/src/**/*',
        '!**/package.json',
        '!**/Gruntfile.js',
        '!**/bower.json',
        '!**/bower_components/**/*',
        '!**/bower_components',
        '!**/node_modules/**/*',
        '!**/node_modules',
        '!**/*.log',
        '!**/*.swp',
        '!**/.DS_Store',
      ],
      { base: '.' }
    )
    .pipe(gulp.dest(`./tmp/${pkg.slug}`));
}

/**
 * Zip the build
 * @returns {*}
 */
export function zipBuild() {
  return gulp
    .src('./tmp/**/*')
    .pipe(zip(`${pkg.slug}-${pkg.version}.zip`))
    .pipe(gulp.dest('./releases'));
}

/**
 * Delete tmp folder
 * @returns {*}
 */
export function cleanBuild() {
  return del(['./tmp']);
}

/**
 * Combine three tasks above to make the release.
 */
export const release = gulp.series(copyBuild, zipBuild, cleanBuild);

export default gulp.parallel(
  i18n,
  gulp.series(compileStyles, minifyStyles),
  gulp.series(compileScripts, minifyScripts)
);
