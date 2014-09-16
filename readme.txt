=== Plugin Protector ===
Contributors: UaMV
Donate link: http://vandercar.net/wp
Tags: plugins, protect, protector, protection, safe, lock, update, upgrade, delete, development
Requires at least: 3.1
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Protects against inadvertant update and deletion of select plugins.

== Description ==

A light-weight admin tool adding a layer of protection when updating and deleting plugins. If you've ever added custom code to a plugin and want to protect against accidentally updating and overwriting the customizations, this plugin will allow you to mark individual plugins as 'Protected'. When protected, update or deletion requests for a plugin will trigger an admin notice confirming your action.

Plugin protection is available on both single-site installs and the network admin of multisite installs.

Documentation is also outlined [here](http://vandercar.net/wp/plugin-protector/ "Plugin Protector Documentation").

== Installation ==

1. Upload the `plugin-protector` directory to your `/wp-content/plugins/`
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Set Plugin Protections
2. Plugins Page & Single Update Notice
3. Update Page & Bulk Update Notice
4. Overridden Updates
5. Delete Notice
6. Edit Plugin Notice

== Frequently Asked Questions ==

= Does this plugin save customizations I have made to plugins? =

No. It only offers you additional protection against deleting or overwriting your customizations. It does allow you to add notes as to why a plugin has been protected.

= Can I use Plugin Protector on a multisite install? =

Yes, as of Version 0.4, Plugin Protector allows for protection in the network admin of a multisite install.

= Does Plugin Protector give me a final line of defense when updating a file from the plugin editor? =

It does display a warning when you are editing a protected plugin, but does not enact any safeguards after having clicked 'Update File'.

== Changelog ==

= 1.4 =
* Bug fix for side notice class

= 1.3 =
* Fix conflicts with Press Permit

= 1.2 =
* Reverted capability for allowing/disallowing protection from 'edit_plugins' to 'activate_plugins'
* Wrapped code in class
* CSS tweaks

= 1.1 =
* Added class_exists to prevent conflict

= 1.0 =
* Changed capability for allowing/disallowing protection from 'activate_plugins' to 'edit_plugins'
* Add informational notices
* Tweaked table display with use of dashicons
* Updated compatability to 3.8

= 0.5 =
* Added notices in the Plugin Editor

= 0.4 =
* Multisite support

= 0.3 =
* Removed undefined index notice | readme edits

= 0.2 =
* Initial release

== Upgrade Notice ==

= 1.3 =
* Fix conflicts with Press Permit

= 1.2 =
* Reverted capability for allowing/disallowing protection from 'edit_plugins' to 'activate_plugins'
* Wrapped code in class
* CSS tweaks

= 1.1 =
* Added class_exists to prevent conflict

= 1.0 =
* Changed capability for allowing/disallowing protection from 'activate_plugins' to 'edit_plugins'
* Add informational notices
* Tweaked table display with use of dashicons
* Updated compatability to 3.8

= 0.5 =
* Added notices in the Plugin Editor

= 0.4 =
* Multisite support

= 0.3 =
* Removed undefined index notice | readme edits

= 0.2 =
* Initial release
