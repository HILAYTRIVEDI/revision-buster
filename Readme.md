# Revision Buster

- Contributors: hilayt24,sabbir1991
- Requires at least: 4.7
- Tested up to: 6.7
- Stable tag: 1.0.0
- Requires PHP: 7.0 or higher
- License: GPLv2 or later
- License URI: https://www.gnu.org/licenses/gpl-2.0.html


### Description

Revision Buster is a WordPress plugin designed to optimize your database by managing and cleaning up post and page revisions efficiently. By limiting unnecessary revisions, it helps improve site performance and maintain a lean database.

---

## Features

- **Selective Revision Cleanup**: Choose specific posts or pages to delete revisions for.
- **Global Cleanup**: Remove revisions for all posts and pages in one click.
- **Revisions Retention**: Configure the number of revisions to retain for each post or page.
- **Scheduled Cleanup**: Set automated cleanup intervals (hourly, daily, weekly, monthly, or yearly).
- **Cache Management**: Efficiently caches posts and pages to optimize performance.
- **Custom Cron Intervals**: Supports monthly and yearly cron schedules.

---

## Installation

1. Download the plugin and upload the folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Navigate to the "Revision Cleanup" page in the WordPress admin menu.

---

## Usage

### Admin Page
- **Revision Cleanup Settings**: Manage the number of revisions to retain and schedule automated cleanups.
- **Single Post/Page Cleanup**: Select a specific post or page and delete its revisions.
- **Global Cleanup**: Delete revisions for all posts and pages.

### Automated Cleanup
- Schedule cleanup tasks at intervals (hourly, daily, weekly, monthly, or yearly) to ensure your database remains optimized.

---

## Hooks and Filters

### Actions
- `revision_buster_run_revision_cleanup_cron`: Executes the revision cleanup process.
- `save_post` & `delete_post`: Invalidates cached posts when a post or page is updated or deleted.

### Filters
- `cron_schedules`: Adds custom intervals (monthly, yearly) to the WordPress cron schedules.

---

## Technical Details

### Cache Management
The plugin uses WordPress transients to cache all posts and pages, improving performance when processing large numbers of posts.

### Cleanup Logic
- Deletes all revisions for a post while retaining the specified number of recent revisions.
- Supports batch processing for scalability.

---

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

---

## Roadmap

- Add support for custom post types.
- Enhanced UI for managing cleanup settings.
- Integration with third-party backup plugins.

---

## Contribution

Feel free to submit issues or pull requests on the [GitHub repository](https://github.com/HILAYTRIVEDI/revision-buster).

---

## License

This plugin is open-source and licensed under the MIT License.

---

## Acknowledgments

Developed by [Hilay Trivedi](https://github.com/HILAYTRIVEDI).
