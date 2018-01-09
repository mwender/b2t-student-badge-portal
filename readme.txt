=== WordPress Plugin with GitHub Updates ===
Contributors: thewebist
Tags: github
Requires at least: 3.7
Tested up to: 4.7.5
Stable tag: 1.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin boiler plate with built-in updating from Github.

== Description ==

Whenever I code a plugin for my clients, and it's going to be hosted on Github, I use this boilerplate to build a plugin that will update via the built-in update system inside WordPress. Then, anytime I publish a new release to the Github repo for the plugin, this plugin will alert WordPress users to update it.

== Installation ==

1. Upload this plugin to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Changelog ==

= 1.0.2 =
* Updating plugin's name.
* Removing namespacing.
* Adding `GITHUB_ACCESS_TOKEN`.
* Setting plugin name in WordPress update overlay.

= 1.0.1 =
* Adding namespacing.

= 1.0.0 =
* Initial release.