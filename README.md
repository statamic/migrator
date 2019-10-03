# Statamic Migrator

Make migrating from v2 to v3 all the moar easier 🤘

- [Installation](#installation)
- [Getting started](#getting-started)
- [Using the site migrator](#using-the-site-migrator)
- [Using individual migrators](#using-individual-migrators)
    - [Fieldset to blueprint migrator](#fieldset-to-blueprint-migrator)
    - [Collection migrator](#collection-migrator)
    - [Pages migrator](#pages-migrator)
    - [Taxonomy migrator](#taxonomy-migrator)
    - [Asset container migrator](#asset-container-migrator)
    - [Globals migrator](#globals-migrator)
    - [Form migrator](#form-migrator)
    - [User migrator](#user-migrator)
    - [Settings migrator](#settings-migrator)
    - [Template migrator](#template-migrator)
- [After migration](#after-migration)
- [Reporting issues](#reporting-issues)

## Installation

Run the following command in your v3 project root:

```
composer require statamic/migrator --dev
```

## Getting started

All of the migrators assume the presence of your v2 project's `site` folder.  Be sure to copy this into your v3 project root before running any of the migrator commands.

## Using the site migrator

The site migrator is the recommended way to migrate your site.  To get started, run the following command:

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

In v3, [...].  To migrate an asset container:

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

### Form migrator

In v3, forms fields are now defined in a [blueprint](https://statamic.dev/blueprints).  To migrate a form and it's submissions:

```
php please migrate:form contact
```

In this example, `contact` is the form handle.

### User migrator

In v3, users are mostly plug-and-play.  The most notable change being that `email` now replaces `username` as the new file name and handle.  To migrate a user:

```
php please migrate:user hasselhoff
```

In this example, `hasselhoff` is the username handle.

### Settings migrator

In v3, site settings are now stored in a conventional Laravel [config directory](https://statamic.dev/configuration).  We cannot guarantee a complete migration, but this migration attempts to transfer core settings where possible.  To migrate your site settings:

```
php please migrate:settings
```

### Template migrator

In v3, [antlers templating](https://statamic.dev/antlers) has undergone a fair number of changes.  The most obvious change is that antlers now uses the `.antlers.html` file extension.  However, you'll also notice a few changes in the available [tags](https://statamic.dev/tags), [modifiers](https://statamic.dev/modifiers), and how variables [cascade]().  We cannot guarantee a complete migration, but this migration attempts to update the most obvious stuff.  To migrate a template:

```
php please migrate:template redwood/templates/about.html
```

Be sure to pass a path relative to your `site/themes` folder.

## After migration

Be sure to manually test your site, addressing all errors and warnings as you see fit.  When you are finished and happy with your migration, feel free to delete your `site` folder from your v3 project root, and then run the following command:

```
composer remove statamic/migrator
```

## Reporting issues

While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  For this reason, we still recommend getting familiar with the [upgrade guide](https://statamic.dev/upgrade-guide).  Though we can't migrate everything, hopefully you have found this package useful in your transition to v3.  If you come across a bug or issue that you think needs to be addressed, please [open an issue](https://github.com/statamic/migrator/issues/new).
