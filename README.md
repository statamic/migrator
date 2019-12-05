# Statamic Migrator

![Statamic 3.0+](https://img.shields.io/badge/Statamic-3.0+-FF269E?style=for-the-badge&link=https://statamic.com)

🤘 Make migrating from v2 to v3 all the moar easier!

📺 See the migrator in action [in this screencast](https://youtu.be/OeXbaeuJrws).

- [Installation](#installation)
- [Getting started](#getting-started)
    - [File prep](#file-prep)
    - [Git state](#git-state)
    - [Idempotency](#idempotency)
    - [Upgrade guide](#upgrade-guide)
- [Using the site migrator](#using-the-site-migrator)
- [Using individual migrators](#using-individual-migrators)
    - [Fieldset to blueprint migrator](#fieldset-to-blueprint-migrator)
    - [Collection migrator](#collection-migrator)
    - [Pages migrator](#pages-migrator)
    - [Taxonomy migrator](#taxonomy-migrator)
    - [Asset container migrator](#asset-container-migrator)
    - [Globals migrator](#globals-migrator)
    - [User migrator](#user-migrator)
    - [Roles migrator](#roles-migrator)
    - [Groups migrator](#groups-migrator)
    - [Settings migrator](#settings-migrator)
    - [Theme migrator](#theme-migrator)
- [After migration](#after-migration)
- [Reporting issues](#reporting-issues)

## Installation

Run the following command in your v3 project root:

```
composer require statamic/migrator --dev
```

## Getting started

### File prep

All of the migrators assume the presence of your v2 project's `site` folder, as well as any local asset container folders.  Be sure to copy these into your v3 project root first.  Also, if you were running above webroot, be sure to copy your `public/themes` folder into `site/themes`.

### Git state

We recommend running these commands from a clean [git](https://git-scm.com/) state, so that you can see a diff of the changes made, and easily rollback if necessary.

### Idempotency

It's worth noting that these commands are generally idempotent, in that they can be run multiple times without negative side effects.  If you encounter any warnings or errors, fix what you need and re-run your migration command.  If necessary, you can force overwriting a particular migration using the `--force` option.

### Upgrade guide

While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  For this reason, we still recommend getting familiar with the [upgrade guide](https://statamic.dev/upgrade-guide).  Though we can't migrate everything, hopefully you have found this package useful in your transition to v3.

## Using the site migrator

The site migrator is the recommended way to migrate your site.  To get started...

1) We recommend creating a new [branch](https://git-scm.com/book/en/v2/Git-Branching-Branches-in-a-Nutshell), so that you can easily roll back a migration if necessary.

2) The site migrator requires the presence of your v2 project's `site` folder, as well as any local asset container folders.  Be sure to copy these into your v3 project root.  Also, if you were running above webroot, be sure to copy your `public/themes` folder into `site/themes`.

3) We recommend clearing your existing site, to ensure a blank slate for your migration:

    ```
    php please site:clear
    ```

4) We recommend [committing](https://git-scm.com/book/en/v2/Git-Basics-Recording-Changes-to-the-Repository) all your changes up to this point, so that you can view a diff of all the changes performed by the migrator.

5) Run the following command to initiate the migration:

    ```
    php please migrate:site
    ```

## Using individual migrators

If you prefer a more granular hands-on approach, you may also run the individual migrator commands.

### Fieldset to blueprint migrator

In v3, [blueprints](https://statamic.dev/blueprints) are the replacement to fieldsets.  It's worth noting that [fieldsets](https://statamic.dev/fieldsets) technically still exist, although they are are a now a smaller, companion feature to blueprints.  To migrate a fieldset to a blueprint:

```
php please migrate:fieldset post
```

In this example, `post` is the fieldset handle.

### Collection migrator

In v3, collections have a slightly different folder and config structure.  To migrate a collection:

```
php please migrate:collection blog
```

In this example, `blog` is the collection handle.

### Pages migrator

In v3, pages are now stored as a [collection](https://statamic.dev/collections-and-entries), with a separate [structure](https://statamic.dev/structures) to manage your page tree hierarchy.  To migrate your pages:

```
php please migrate:pages
```

### Taxonomy migrator

In v3, taxonomies are mostly plug-and-play, apart from a few minor changes to config structure.  To migrate a taxonomy:

```
php please migrate:taxonomy tags
```

In this example, `tags` is the taxonomy handle.

### Asset container migrator

In v3, assets and related meta data are now stored within a [Laravel filesystem](https://laravel.com/docs/6.x/filesystem).  To migrate a local asset container, you will need to copy your assets folder into your v3 project root, along side your `site` folder.  You can skip this step if migrating an S3 based container.  Once ready, run the following command:

```
php please migrate:asset-container main
```

In this example, `main` is the asset-container handle.

### Globals migrator

In v3, globals are mostly plug-and-play.  To migrate a global set:

```
php please migrate:global-set global
```

In this example, `global` is the global set handle.

### User migrator

In v3, users are mostly plug-and-play.  The most notable change being that `email` now replaces `username` as the new file name and handle.  To migrate a user:

```
php please migrate:user hasselhoff
```

In this example, `hasselhoff` is the username handle.

### Roles migrator

In v3, roles are mostly plug-and-play.  The most notable change being that roles are keyed by a slug handle, instead of by uuid.  It's worth noting that the user migrator takes care of this relationship on the user end as well.  To migrate your user roles:

```
php please migrate:roles
```

### Groups migrator

In v3, groups are mostly plug-and-play.  The most notable change being that groups are keyed by a slug handle, instead of by uuid.  Also, we've removed the `users` array from each group, in favor of storing a `groups` relationship on the user itself.  It's worth noting that the user migrator takes care of this new relationship on the user end.  To migrate your user groups:

```
php please migrate:groups
```

### Settings migrator

In v3, site settings are now stored in a conventional Laravel [config directory](https://statamic.dev/configuration).  We cannot guarantee a complete migration, but this migration attempts to transfer core settings where possible.  To migrate your site settings:

```
php please migrate:settings
```

### Theme migrator

In v3, [the concept of 'themes' is gone](https://statamic.dev/upgrade-guide#theming-and-views).  Your site just has the one, and it's in the `resources` directory.  When running a full site migration, we only attempt the migration of your active theme.  However, you can specify any theme handle when running this migrator individually:

```
php please migrate:theme redwood
```

In this example, `redwood` is the handle of a theme located in the `site/themes` folder.

It's worth noting that [antlers templating](https://statamic.dev/antlers) has undergone a fair number of changes.  The most obvious change is that antlers now uses the `.antlers.html` file extension.  You'll also notice changes in the available [tags](https://statamic.dev/tags), [modifiers](https://statamic.dev/modifiers), and how variables [cascade](), etc.

Due to the evolution of antlers templating, we cannot guarantee a complete migration of your theme, but we attempt to update the most obvious stuff for you.

## After migration

Be sure to manually test your site, addressing all errors and warnings as you see fit.  When you are finished and happy with your migration, feel free to delete your `site` and asset container folders from your v3 project root, and then run the following command:

```
composer remove statamic/migrator
```

## Reporting issues

While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  For this reason, we still recommend getting familiar with the [upgrade guide](https://statamic.dev/upgrade-guide).  Though we can't migrate everything, hopefully you have found this package useful in your transition to v3.  If you come across a bug or issue that you think needs to be addressed, please [open an issue](https://github.com/statamic/migrator/issues/new).
