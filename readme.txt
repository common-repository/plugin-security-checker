=== Plugin Security Checker ===
Contributors: whitefirdesign, pluginvulnerabilities
Tags: exploit, plugin security, plugin vulnerability, plugins, security, vuln, vulnerability, vulnerabilities
Requires at least: 4.4
Tested up to: 4.9
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Check if plugins have any security issues that can be detected by our Plugin Security Checker tool.

== Description ==

This plugin uses our [Plugin Security Checker](https://www.pluginvulnerabilities.com/plugin-security-checker/) to check if the current version of a plugin in the Plugin Directory is known to be vulnerable based on our data on disclosed vulnerabilities and also checks for indications that it may contain other security issues. The checked plugin may contain security issues that cannot be found by this tool.

It currently includes checks for the possibility of some instances of the following issues:

* PHP object injection
* Arbitrary file upload
* Arbitrary WordPress option (setting) updating and deletion
* Local file inclusion (LFI)
* SQL injection
* Usage of third-party libraries with known vulnerabilities
* Reflected cross-site scripting (XSS)
* Base64 obfuscation
* Incorrect usage of non-privileged AJAX registration

If you use our [Plugin Vulnerabilities service](https://www.pluginvulnerabilities.com) you can also check the security of installed plugins that are not in the Plugin Directory.

The results from checking plugins in the Plugin Directory may be logged and publicly disclosed. The results from checking uploaded plugins will not be logged. 

The results of the tool have lead to [identifying and getting fixed some serious vulnerabilities](https://www.pluginvulnerabilities.com/2017/11/22/our-wordpress-plugin-security-checker-identified-a-fairly-serious-vulnerability-in-a-plugin-by-mailchimp/) as well as [identifying plugins with that are in need of general security improvement](https://www.pluginvulnerabilities.com//2017/11/27/easy-to-spot-vulnerabilities-in-wordpress-plugins-can-be-an-indication-of-poor-development-practices-and-further-issues/).

== Screenshots ==

1. Results Page

2. Links to Check Plugins on Installed Plugins Page 


== Changelog ==

= 1.0 =
* Initial release