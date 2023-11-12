# Software License Server for WooCommerce Client 

This is the class file required to be included with your plugin or theme to provide license and update checks. 

## Usage

Install the SDK using the composer package manager.

`composer require digitalduz/slswc-client`

Then use the client as follows for a plugin:


```require __DIR__ . 'vendor/autoload.php';

$license_server_url = 'http://example.com';
$license_details = array(
    'license_key' => 'LICENSE_KEY', // Required
    'domain' => 'THE_CURRENT_DOMAIN', // Optional: will default to WordPress site url.
    'slug' => 'plugin-slug', // optional. Will use plugin text domain. Must be the product slug on license server.
);

$plugin = Plugin::get_instance( $license_server_url, __FILE__, $license_details );
$plugin->init_hooks();
```

`$license_server_url` - Is the domain of the license server.
`$base_file` - Is the plugin main file for a plugin or the theme root folder for the theme
`$license_details` - Is an array of license details. May include plugin details if not set in plugin headers.

### Example for plugins:
```
$plugin = Plugin::get_instance( 'http://example.test/', __FILE__, $license_details );
$plugin->init_hooks();
```

### Example for themes:
```
$license_details = array(
    'license_key' => 'THE_LICENSE_KEY'
);

$theme =  Theme::get_instance( 'http://example.test/', WP_CONTENT_DIR . '/themes/theme-directory-name', $license_details );
$theme->init_hooks();
```

### Activating the license

This is an example of how to activate the license. You can hook this to a form save after saving the license key.

```
// Example of how to update the plugin. Run this on a hook.
if ( $plugin->license->get_license_status() !== 'active' ) {
	$plugin->license->validate_license();
}
```

## Plugin headers.

The client also searches for additional theme or plugin headers for information about the plugin, these headers are as follows:

* SLSWC - Specifies whether this is a `plugin` or `theme` and it is also used to distinguish SLSWC plugins/themes from others.
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
 * Required WP  : 5.8
 * Compatible To: 5.8.1
 */

require __DIR__ . '/vendor/autoload.php';
use Digitalduz\Slswc\Client\Plugin;

function test_slswc_client_for_plugin() {
    $license_server_url = 'http://example.com';
    $license_details = array(
        'license_key' => 'LICENSE_KEY', // Required
        'domain' => 'THE_CURRENT_DOMAIN', // Optional: will default to WordPress site url.
        'slug' => 'plugin-slug', // optional. Will use plugin text domain. Must be the product slug on license server.
    );

    $plugin = Plugin::get_instance( $license_server_url, __FILE__, $license_details );
    $plugin->init_hooks();
}

add_action( 'plugins_loaded', 'test_slswc_client_for_plugin', 11 );
```

And for a theme:

Put this in `functions.php`
```php
require __DIR__ . '/vendor/autoload.php';

use Digitalduz\Slswc\Client\Theme;

function theme_slswc_client() {
    $license_details = array(
        'license_key' => 'LICENSE_KEY',        // Required
        'domain'      => 'THE_CURRENT_DOMAIN', // Optional: will default to WordPress site url.
        'slug'        => 'plugin-slug',        // optional. Will use plugin text domain. Must be the product slug on license server.
    );
    $theme = Theme::get_instance(
        'http://example.com',
        WP_CONTENT_DIR . '/themes/theme-folder-name',
        $license_details
    );
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

