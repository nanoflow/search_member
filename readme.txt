====== Search Member ======

Version 3.0.0
Plugin for Admidio v6.0+ (uses the Admidio 6 plugin system)

==== Description ====
This plugin adds a widget to the overview page. It provides a search field to search for members of
the organisation. It is available to all logged in users. Roles that should be included in the search
can be restricted in the plugin preferences, otherwise it searches all members in the database. The
search results are shown as links to the user profile. You can specify if you want to show first
and/or last name or the login name.

If your organisation has members across multiple cities, you can include the city in the search.

{{:en:plugins:search_member_screenshot.png?400|}}

==== Download ====
The plugin is available at [[https://github.com/nanoflow/search_member]]

==== Installation ====
This plugin uses the Admidio 6 plugin system. Deploy this directory as
plugins/search_member/ below your Admidio installation, then install and enable it from
*Administration → Extensions*. All preferences, including the ones a version before 3.0.0 kept in
config.php, are then edited on the plugin's own preferences panel; a config.php from a previous
installation is picked up automatically once when the plugin is updated.

==== Contribution ====
Contributions and feature requests are welcome