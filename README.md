# Software License Server for WooCommerce Client 

This is the class file required to be included with your plugin or theme to provide license and update checks. 

## Usage

### Install using composer
`composer require madvault/slswc-client`

### Clone or download from the repository
Include the `class-slswc-client.php` file into a convenient folder in your theme or plugin. You may include the file and initialize it in your plugin's main file or theme's functions.php file. In it's basic form, the license client can be initialized like this:

```SLSWC_Client::get_instance( $license_server_url, $base_file, $software_type );```

`$license_server_url` - Is the domain of the license server.
`$base_file` - Is the plugin main file for a plugin or the theme root folder for the a theme
`$software_type` - Specify if this is a `plugin` or `theme`, default is plugin.

### Example for plugins:
`SLSWC_Client::get_instance( 'http://example.test/', __FILE__ );`

### Example for themes:
`SLSWC_Client::get_instance( 'http://example.test/', WP_CONTENT_DIR . '/themes/theme-directory-name', 'theme' );`

### Required plugin and theme headers.
With the basic integration, the license client will look for specific plugin or theme headers that are required for the license client to work properly. The following two, are the required headers:

* SLSWC - This is used to specify the type of product this is, a plugin or theme. This is also used to detect plugins or themes whose updates are managed by this client.
* SLSWC Slug - The slug of the software as specified in the License panel of the product in the License Server for WooCommerce. The slug is used to query the product's public information. The slug must be the same as the product's slug. If a slug is not specified, the plugin or theme's text domain will be used as the slug.

**Example usage for a plugin:**

```/**
 * Plugin Name : Test Plugin
 * SLSWC	   : plugin
 * SLSWC Slug  : test-plugin
 */
```

**Example usage for a theme**
```/**
 * Theme Name : Test Theme
 * SLSWC      : theme
 * SLSWC Slug : test-theme
 */
```

### Additional Headers

The client also searches for additional theme or plugin headers for information about the plugin, these headers are as follows:

* SLSWC - Specifies whether this is a `plugin` or `theme`.
* SLSWC Slug - The slug of the theme or plugin. Used when checking the license or reading product information.
* SLSWC Documentation URL - Link to documentation for this plugin or theme
* Required WP - Minimum version of WordPress required
* SLSWC Compatible To - Maximum compatible WordPress version
* SLSWC Updated - The date on which the plugin/theme was last updated.

Additionally, the following WordPress plugin/theme headers are used for additional information if available.

* Author - The plugin author name
* Requires at least - The minimum required WordPress version supported by the theme or plugin.

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
 * SLSWC Documentation URL: https://www.gnu.org/licenses/gpl-2.0.html
 * Required WP  : 5.8
 * Compatible To: 5.8.1
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
Text Domain : test-theme
SLSWC       : theme
Documentation URL: https://example.test/docs/test-theme
Tested WP   : 5.8
Requires WP : 5.8.1
*/
```

