# Penny Black Woocommerce Plugin

This plugin integrates Woocommerce to Penny Black, to provide the data for, and optionally trigger personalised prints.

## Installation

* Download the latest release from the [releases page](https://github.com/pennyblack-io/woocommerce-pennyblack/releases)
* Extract the zip file and rename the folder to `woocommerce-pennyblack`, removing the version number component.
* Inside the folder run `composer install --no-dev`.
* Create a zip file from the `woocommerce-pennyblack` folder.
* Upload the folder to your WordPress site, either manually to the plugins directory, or via the admin interface on the plugins page.
* Install the plugin from the WordPress admin panel.
* Follow the settings link on the plugins page to configure the plugin:
  * Set the API key that received from Penny Black support
  * Enable order transmission
  * If you fulfil your own orders through Woocommerce then enable the order admin extensions.