# Software License Server for WooCommerce Client 

This is the class file required to be included with your plugin or theme to provide license and update checks. 

## Usage

First download the `class-slswc-client.php` into a convenient folder in your theme or plugin. You may include the file and initialize it in your plugin's main file or theme's functions.php file. In it's basic form, the license client can be initialized like this:

```SLSWC_Client::get_instance( $license_server_url, $base_file, $software_type );```

`$license_server_url` - Is the domain of the license server.
`$base_file` - Is the plugin main file for a plugin or the theme root folder for the a theme
`$software_type` - Specify if this is a `plugin` or `theme`, default is plugin.

### Example for plugins:
`SLSWC_Client::get_instance( 'http://example.test/', __FILE__ );`

### Example for themes:
`SLSWC_Client::get_instance( 'http://example.test/', WP_CONTENT_DIR . '/themes/theme-directory-name', 'theme' );`

## Advanced Usage

The client also searches for additional theme or plugin headers for information about the plugin, these headers are as follows:

* SLSWC - Specifies whether this is a `plugin` or `theme`.
* Documentation URL - Link to documentation for this plugin or theme
* Required WP - Minimum version of WordPress required
* Compatible To - Maximum compatible WordPress version

Here is a full example of how to use this in a plugin:

```php
/**
 * Plugin Name  : Test Plugin Name
 * Plugin URI   : https://example.com/plugins/plugin-name
 * Description  : Basic WordPress plugin to test Software License Server for WooCommerce
 * Text Domain  : text_domain
 * Author URI   : https://example.com
 * License      : https://www.gnu.org/licenses/gpl-2.0.html
 * Version      : 1.0.0
 * Author       : Author Name
 * Domain Path  : /languages
 * SLSWC        : plugin
 * Documentation URL: https://www.gnu.org/licenses/gpl-2.0.html
 * Required WP  : 5.1
 * Compatiple To: 5.1
 */
function test_slswc_client_for_plugin() {
    require_once 'includes/class-slswc-client.php';
    return SLSWC_Client::get_instance( 'http://example.com/', __FILE__ );
}

add_action( 'plugins_loaded', 'test_slswc_client_for_plugin', 11 );
```

And for a theme:

Put this in `functions.php`
```php
function theme_slswc_client() {
    require_once 'includes/class-slswc-client.php';
    return SLSWC_Client::get_instance( 'http://example.com', WP_CONTENT_DIR . '/themes/theme-folder-name', 'theme' );	
}
add_action( 'wp_loaded', 'theme_slswc_client', 11 );
```

Add this to `style.css`

```php
/*
Theme Name  : Theme Name
Theme URI   : https://example.test/themes/your-theme-name/
Author      : Author Name
Author URI  : https://example.test/
Description : Software License Server for WooCommerce Test Theme
Version     : 1.0
License     : GNU General Public License v2 or later
License URI : http://www.gnu.org/licenses/gpl-2.0.html
Tags        : blog, two-columns, left-sidebar
Text Domain : rigidtheme
SLSWC       : theme
Documentation URL: https://example.test/docs/rigid-theme
Tested WP   : 5.1
Requires WP : 5.1
*/
```

