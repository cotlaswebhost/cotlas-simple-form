# Cotlas Simple Forms

Cotlas Simple Forms is a WordPress form builder plugin by Cotlas Web Solution. It provides a block-based form builder for contact forms, login forms, registration forms, payment forms, donation forms, and frontend post submission forms.

## Features

- Block-based form builder for WordPress.
- Form types: normal/contact, login, registration, payment, donation, and frontend post submission.
- Payment and donation redirect URL support after successful submission.
- AJAX form submissions with frontend response handling.
- Submission storage inside WordPress admin.
- File upload support.
- Email notifications and optional user confirmation emails.
- SMTP settings for plugin emails or global WordPress emails.
- Cloudflare Turnstile spam protection.
- Google Places support for location fields.
- Multi-step and conversational form layouts.
- Dashboard with tabs for forms, settings, submissions, email templates, and activity logs.
- GitHub auto-updater support from this repository.

## Installation

1. Download or clone this repository.
2. Upload the plugin folder to:
   `wp-content/plugins/cotlas-simple-forms`
3. Activate **Cotlas Simple Forms** from WordPress admin.
4. Go to **Cotlas Forms > Dashboard** to start creating forms.

## Shortcode

After creating a form, copy its shortcode from the form editor sidebar:

```text
[csf_form id="123"]
```

Place the shortcode on any page, post, or template where you want the form to appear.

## Form Types

Cotlas Simple Forms supports these form behaviors:

- **Normal**: Standard contact or enquiry form.
- **Login**: WordPress login form.
- **Registration**: WordPress user registration form.
- **Payment**: Saves the submission and redirects users to a payment URL.
- **Donation**: Saves the submission and redirects users to a donation URL.
- **Frontend Add Post Form**: Creates WordPress posts from frontend submissions.

For payment and donation forms, set the **Redirect URL after submission** in the form settings. This can be a payment gateway URL, donation checkout link, or thank-you page.

## Admin Dashboard

The plugin dashboard includes tabs for:

- Dashboard
- All Forms
- Add Form
- Settings
- Submissions
- Email Templates
- Activity Log

The sidebar menu is ordered the same way for quick access.

## GitHub Auto-Updater

This plugin includes a GitHub updater that checks the latest release from:

```text
https://api.github.com/repos/cotlaswebhost/cotlas-simple-form/releases/latest
```

To publish an update:

1. Update the plugin version in `cotlas-simple-forms.php`.
2. Commit and push the code to GitHub.
3. Create a GitHub release with a matching version tag, for example:
   `v1.1.3`

WordPress will show the update when the release version is newer than the installed plugin version.

For private repositories or higher GitHub API limits, define this in `wp-config.php`:

```php
define( 'COTLAS_GITHUB_TOKEN', 'your_github_token_here' );
```

## Development

Main plugin file:

```text
cotlas-simple-forms.php
```

Important folders:

```text
admin/       Admin page classes
assets/      CSS and JavaScript
classes/     Upgraded plugin services
inc/         GitHub updater
includes/    Legacy plugin classes
templates/   Admin templates
```

## Version

Current version: `1.1.6`

## Author

Cotlas
