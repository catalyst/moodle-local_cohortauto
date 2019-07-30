# Auto-cohort local plugin for Moodle 3.5+

This local plugin automatically adds users into cohorts, with names that
derived from the users' profile fields.

This plugin requires Moodle 3.5 or greater (using PHP 7 or greater).

Auto-created cohorts are made in the top-level system context (CONTEXT_SYSTEM).


## Migrating cohorts from the `auth_mcae` plugin

This plugin is derived from, and intended to deprecate, the earlier `auth_mcae`
plugin by Andrew Kama. See the [auth_mcae plugin repository](https://github.com/danmarsden/moodle-auth_mcae)
for more information.

The two plugins could be installed side-by-side, but the `local_cohortauto`
plugin can conflict with user update event handling from the earlier plugin,
causing multiple cohorts to be created, each managed by its own plugin.

You can convert older cohorts to be managed by this plugin at:
`/local/cohortauto/convert.php`

The MCAE plugin can be uninstalled before conversion takes place, but be
sure to make a copy of any settings from the older plugin that you wish to keep.

## Installation

* Download the archive and install the files into: `your_moodle/local/cohortauto`
* Ensure files have been copied in with the same permissions as the rest of your
  Moodle.
* Visit the *Site administration - Notifications* page and follow the instructions

## Configuration and operation

Moodle uses the [Mustache](https://docs.moodle.org/dev/Templates) templating
language for rendering data into HTML for presentation. This plugin reuses that
functionality, by turning values from a user's profile into cohort names. This
allows a site administrator to make rules for the automatic creation of cohorts
based on user properties.

The process of creating automatic cohorts or adding users to them happens when
each user profile is created or updated, or when command line scripts are run.
(`cli/sync_user.php` for individuals, and `cli_sync_users.php` to sync across
all users).


### Main template (`mainrule_fld`)
One value per line.

In the template you may use any characters (except '{' and '}') and profile
field values. To insert a profile field value, use a `{{ field_name }}` tag.

By default, Moodle provides the following profile fields:

*{{ id }}, {{ auth }}, {{ confirmed }}, {{ policyagreed }}, {{ deleted }},
{{ suspended }}, {{ mnethostid }}, {{ username }}, {{ idnumber }},
{{ firstname }}, {{ lastname }}, {{ email.full }}, {{ email.username }},
{{ email.domain }}, {{ email.rootdomain }}, {{ emailstop }}, {{ icq }},
{{ skype }}, {{ yahoo }}, {{ aim }}, {{ msn }}, {{ phone1 }}, {{ phone2 }},
{{ institution }}, {{ department }}, {{ address }}, {{ city }}, {{ country }},
{{ lang }}, {{ calendartype }}, {{ theme }}, {{ timezone }}, {{ firstaccess }},
{{ lastaccess }}, {{ lastlogin }}, {{ currentlogin }}, {{ lastip }},
{{ secret }}, {{ picture }}, {{ url }}, {{ descriptionformat }},
{{ mailformat }}, {{ maildigest }}, {{ maildisplay }}, {{ autosubscribe }},
{{ trackforums }}, {{ timecreated }}, {{ timemodified }}, {{ trustbitmask }},
{{ imagealt }}, {{ lastnamephonetic }}, {{ firstnamephonetic }},
{{ middlename }}, {{ alternatename }}, {{ lastcourseaccess }},
{{ currentcourseaccess }}, {{ groupmember }}*

The email field has 4 variants:
* `{{ email.full }}` - full address (user@my.example.com)
* `{{ email.username }}` - only username (user)
* `{{ email.domain }}` - only domain (my.example.com)
* `{{ email.rootdomain }}` - root domain (example.com)

Additional tags become available if you have custom profile fields added.
For example, if you were to create the following custom profile fields:
* `checkboxtest` - type Checkbox
* `datetimetest` - type Date/Time
* `droptest` - type Dropdown menu
* `textareatest` - type Text area
* `textinputtext` - type Text input

You would be able to use these tags:

*{{ profile.checkboxtest }}, {{ profile.datetimetest }}, {{ profile.droptest }},
{{ profile.textinputtext }}, {{ profile_field_checkboxtest }},
{{ profile_field_datetimetest }}, {{ profile_field_droptest }},
{{ profile_field_textareatest.text }}, {{ profile_field_textareatest.format }},
{{ profile_field_textinputtext }}*

> **Note:** Profile field templates are case sensitive. `{{ username }}` and
`{{ UserName }}` are two different fields!

#### Split arguments
Mustache also allows you to split a single field into multiple values.

Synopsis: `%split(fieldname|delimiter)`

This will return multiple cohort names, formed by splitting the field
at boundaries specified by the delimiter.

* `fieldname` : Profile field name. The same as a tag, but without '{{' and '}}'
* `delimiter` : The boundary string. 1-5 characters.

> **Example:**
>
> User John fills the custom profile field "Known languages" with the value
> "English, Spanish, Chinese"
>
> The main template contains a line: `Language - %split(knownlanguage|, )`
>
> John will be enrolled in 3 cohorts:
> * *Language - English*
> * *Language - Spanish*
> * *Language - Chinese*


### Empty field text (`secondrule_fld`)
If profile field is empty, it will be replaced with this value.


### Replace array (`replace_arr`)
If the values in your profile are expected to be different from what you want
your cohort names to be, you can add replacement strings here.

One replacement per line, in the format: `old value|new value`

e.g.: `Yoyodyne Propulsion Systems Incorporated|Yoyodyne`

> **Note:** Names must not be longer than 100 characters, or they will be
> truncated.


### Ignore users (`donttouchusers`)

A list of users to ignore when updating or syncing profiles. Accepts
comma-separated usernames.

    admin,test,manager,teacher1,teacher2

### Enable automatic removal from managed cohorts (`enableunenrol`)

If this setting is checked, users will also be removed from automatic cohorts
that they no longer appear to be a part of, when their profiles are updated or a
sync occurs.


## Usage example

Say you have custom profile fields "status" (student, teacher or admin)
and "classcode", and you want to enrol users into cohorts using both of them.

You could set your main template to:

`{{ profile_field_classcode }} - {{ profile_field_status }}s`

And empty field text to: `none`

* A Y19A teacher would be added to a "Y19A - teachers" cohort
* A Y19B student would be added to a "Y19B - students" cohort
* An admin user with no classcode would be added to "none - admins"

To rename the "none - admins" cohort to something more readable, you could
add a value to the replace array field:

`none - admins|Administrators`

When an admin user syncs, they will now be enrolled in a cohort named
"Administrators" instead.
