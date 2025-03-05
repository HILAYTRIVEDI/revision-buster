# Revision Buster

- **Contributors**: hilayt24, sabbir1991
- **Requires at least**: 4.7
- **Tested up to**: 6.7.2
- **Stable tag**: 1.0.1
- **Requires PHP**: 7.4
- **License**: GPLv2 or later
- **License URI**: https://www.gnu.org/licenses/gpl-2.0.html

### Short Description

Boost your WordPress performance with Revision Buster—the ultimate plugin for managing and cleaning up post and page revisions. Optimize your database, improve site speed, and schedule automated cleanups effortlessly.

---

### Description

Revision Buster is a powerful WordPress plugin designed to enhance website performance by efficiently managing and cleaning up post and page revisions. By removing unnecessary revisions and offering customizable retention settings, it ensures your database remains optimized, reducing bloat and improving load times.

With Revision Buster, you can easily schedule automated cleanups, perform selective or global revision deletions, and configure retention rules tailored to your site’s needs. Built with scalability in mind, the plugin seamlessly handles large databases and is perfect for sites of any size.

---

## Features

- **Selective Revision Cleanup**: Delete revisions for specific posts or pages as needed.
- **Global Cleanup**: Clean up revisions for all posts and pages with a single click.
- **Revisions Retention**: Customize how many revisions to retain per post or page.
- **Scheduled Cleanup**: Automate cleanup tasks at intervals (hourly, daily, weekly, monthly, or yearly).
- **Cache Management**: Uses transients for efficient caching of posts and pages.
- **Custom Cron Intervals**: Adds monthly and yearly cleanup schedules.
- **Custom Post Type Support**: Handles revisions for all registered post types (future roadmap).

---

## Installation

1. Download the plugin and upload the folder to the `/wp-content/plugins/` directory.
2. Activate the plugin via the **Plugins** menu in WordPress.
3. Navigate to **Tools > Revision Buster** in the WordPress admin menu.

---

## Usage

### Admin Settings

- **Revision Cleanup Settings**: Manage revision retention rules and schedule automated cleanups.
- **Single Post/Page Cleanup**: Select individual posts or pages and delete their revisions.
- **Global Cleanup**: Remove revisions for all posts and pages to free up database space.

### Automated Cleanup

- Configure intervals for automated cleanup tasks (hourly, daily, weekly, monthly, yearly).
- Ensure your database stays optimized without manual intervention.

---

## Hooks and Filters

### Actions

- `revision_buster_run_revision_cleanup_cron`: Executes the scheduled revision cleanup.
- `save_post` & `delete_post`: Automatically invalidates cached posts when updates or deletions occur.

### Filters

- `cron_schedules`: Adds custom intervals (monthly, yearly) to WordPress cron schedules.

---

## Technical Details

### Cache Management

- Implements WordPress transients to improve performance when processing large datasets.
- Efficiently manages cached data to minimize load times during cleanup tasks.

### Cleanup Logic

- Deletes older revisions while retaining the configured number of most recent revisions.
- Utilizes batch processing for scalability on larger databases.

---

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher

---

## Roadmap

- Add support for custom post types.
- Enhanced UI with analytics and visual cleanup reports.
- Third-party plugin integration for backup compatibility.
- Multisite support for network-wide revision cleanup.

---

## Contribution

We welcome your contributions! Feel free to submit issues or pull requests via the [GitHub repository](https://github.com/HILAYTRIVEDI/revision-buster).

---

## License

This plugin is open-source and licensed under the GPLv2 or later. Learn more at [GPL License](https://www.gnu.org/licenses/gpl-2.0.html).

---

## Acknowledgments

Developed by [Hilay Trivedi](https://github.com/HILAYTRIVEDI).

Special thanks to the WordPress community for their continuous support and feedback.
