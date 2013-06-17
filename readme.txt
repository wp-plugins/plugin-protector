=== Plugin Protector ===
Contributors: UaMV
Donate link: http://wmpl.org/blogs/vandercar/give
Tags: plugins, protect, protector, protection, safe, lock, update, upgrade, delete, development
Requires at least: 3.1
Tested up to: 3.5
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Protects against inadvertant update and deletion of select plugins.

== Description ==

Plugin Protector is a light-weight admin tool that adds a layer of protection when updating and deleting plugins. If you've ever added custom code to a plugin and want to protect against accidentally updating and overwriting your customizations, this plugin will allow you to mark individual plugins as 'Protected'. When protected, update or deletion requests for a plugin will trigger an admin notice confirming your action.

Documentation is also outlined [here](http://wmpl.org/blogs/vandercar/wp/plugin-protector/ "Plugin Protector Documentation").

== Installation ==

1. Upload the `plugin-protector` directory to your `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Set Plugin Protections
2. Plugins Page & Single Update Notice
3. Bulk Update Notice
4. Delete Notice

== Frequently Asked Questions ==

= Does this plugin save customizations I have made to plugins? =

No. It only offers you additional protection against deleting or overwriting your customizations.

= Can I use Plugin Protector on a multisite install? =

Plugin Protector is not currently functional on a networked install.

== Changelog ==

= 0.1 =
* Initial Release

== Upgrade Notice ==

= 0.1 =
* Initial Release