=== User Notices ===
Contributors: apos37
Tags: notices, user, popup, banner, notifications
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display targeted notices to your website visitors based on various criteria like login status, user roles, specific users, and more.

== Description ==

**User Notices** empowers administrators to create and display targeted notices on the front-end of their WordPress site. These notices can be customized based on various user conditions, offering a flexible way to communicate important information to specific audience segments.

**Features:**

- **Targeted Display:** Show notices to all visitors, logged-in members only, logged-out visitors only, users with specific roles, or even a specific user by ID, email, or IP address.
- **Flexible Placement:** Display notices at the top or bottom of the screen, above or below content, as a popup modal or alert, in a shortcode, or even in the developer console.
- **Page Targeting:** Show notices on the homepage, all pages, specific post types, or a specific page.
- **Scheduled Display:** Schedule notices to appear and disappear at specific times.
- **Predefined Templates:** Use one of the predefined notice templates for quick setup.
- **Merge Tags:** Use merge tags to dynamically populate notices with user data (e.g., name).
- **Track Clicks:** See how many people click on a notice button and how many dismiss the notices.
- **Developer Hooks:** Leverage hooks for further customization and integration with other plugins.

This plugin is ideal for announcements, promotions, important updates, or any other message you need to deliver to your users effectively. This plugin does not send email notifications.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/user-notices/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Notices > Add New to create a new notice.

== Frequently Asked Questions ==

= How do I target specific users? =
You can target specific logged-in users by their User ID or email address. For logged-out users, you can target them by IP address.

= Can I schedule notices? =
Yes, you can set a start and end date/time for each notice.

= How do I use merge tags? =
Merge tags are placeholders that are replaced with dynamic content. For instance, you can use `{user:first_name}` to populate the user's first name.

= Are there hooks for developers? =
Yes, User Notices provides hooks for developers to customize the plugin's functionality.  Consult the plugin documentation for details.

== Screenshots ==

1. A floating notice at the bottom of the page.
2. A floating notice at the top of the page.
3. A notice above the content.
4. A notice below the content.
5. A pop-up modal notice using a form.
6. A shortcode notice.
7. A plain text JavaScript alert notice.
8. A notice in the developer console.
9. The notice edit page 1 of 2.
10. The notice edit page 2 of 2.
11. The settings area with example notices you can quickly install.
12. User interactions.
13. Report page.

== Changelog ==

= 1.2.1 =
* Update: Removed the license requirement. The plugin works without a license ID.
* Update: Replaced the old license-based updater with a drop-in updater that checks PluginRx.com for new versions.
* Update: Added the Update URI header so WordPress only checks PluginRx.com for updates to this plugin.
* Update: Changed the license to GPLv2 or later.

= 1.1.1 =
* Compatibility: Increased minimum required WordPress version to 6.0
* Compatibility: Increased minimum required PHP version to 8.0
* Compatibility: Tested with WordPress 7.0

= 1.1.0 =
* Update: New support links

= 1.0.2 =
* Update: Updated author name and website per WordPress trademark policy

= 1.0.1 =
* Initial Release