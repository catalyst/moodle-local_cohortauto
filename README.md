# Auto-cohort local plugin for Moodle 3.5+

**Note:** This plugin is derived from and intended to deprecate the earlier `auth_mcae` plugin.
The plugins can be installed side-by-side, but the `local_cohortauto` plugin will override data from the earlier plugin.
The `auth_mcae` plugin should then be safely uninstalled.

This local plugin automatically adds users into cohorts, with a name that depends on the users' profile fields.

This plugin requires Moodle 3.5 or greater (using PHP 7 or greater).

Auto-created cohorts are made in the top-level system context (CONTEXT_SYSTEM).

See the [auth_mcae plugin repository](https://github.com/danmarsden/moodle-auth_mcae) for more (possibly out-of-date) information.
