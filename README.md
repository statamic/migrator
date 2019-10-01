# Statamic Migrator

Make migrating from v2 to v3 all the moar easier 🤘

- [Installation](#installation)
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
    - [Config migrator](#config-migrator)
    - [Template migrator](#template-migrator)
- [After migration](#after-migration)
- [Reporting issues](#reporting-issues)

## Installation

Run the following command in your v3 project root:

```
composer require statamic/migrator --dev
```

## Using the site migrator

The site migrator is the recommended way to migrate your site.  To get started...

1. Copy your v2 project's `site` folder into your v3 project root.
2. Run `php please migrate:site`.
3. Manually test and address errors and warnings as you see fit.

## Using individual migrators

If you prefer a more granular hands-on approach, you may also copy individual content files into their proper locations and run the individual migrator commands.

### Fieldset to blueprint migrator

In v3, _'blueprints'_ are the replacement to fieldsets.  Fieldsets technically still exist, although they are are a now a smaller, companion feature to blueprints.  To migrate a fieldset to a blueprint...

1. Copy your v2 fieldset file into your v3 project.
    - ie. `site/settings/fieldsets/post.yaml` -> `resources/blueprints/post.yaml`
2. Run `php please migrate:fieldset post`.
3. Manually test and address errors and warnings as you see fit.

### Collection migrator

In v3, collections have a slightly different folder and config structure.  To migrate a collection...

1. Copy your collection folder into your v3 project.
    - ie. `site/content/collections/blog` -> `content/collections/blog`
2. Run `php please migrate:collection blog`.
3. Manually test and address errors and warnings as you see fit.

_**Note:** In v2, your collection's route was configured externally in `site/settings/routes.yaml`.  When running this migrator on an individual collection, we generate a new route for your collection.  If you wish to preserve your original route, copy your old `site` folder into your v3 project root before running this migration._

### Pages migrator

In v3, pages are now stored as a collection, with a separate _'structure'_ to manage your page tree hierarchy.  To migrate your pages...

1. Copy your v2 pages folder into your v3 project's collections folder.
    - ie. `site/content/pages` -> `content/collections/pages`
2. Run `php please migrate:pages`.
3. Manually test and address errors and warnings as you see fit.

_**Note:** In v2, when creating a page, we displayed available fieldsets that weren't hidden via `hide: true`.  When running this migrator on your individual pages collection, we generate a new list of blueprints based on which were used throughout your various pages.  If you wish to preserve your configuration of available fieldsets, copy your old `site` folder into your v3 project root before running this migration._

### Taxonomy migrator

In v3, taxonomies are mostly plug-and-play, apart from a few minor changes to config structure.  To migrate a taxonomy...

1. Copy your v2 taxononomy config file (and terms folder, if applicable) into your v3 project.
    - ie. `site/content/taxonomies/tags.yaml` => `content/taxonomies/tags.yaml`
    - ie. `site/content/taxonomies/tags` => `content/taxonomies/tags`
2. Run `php please migrate:taxonomy tags`.
3. Manually test and address errors and warnings as you see fit.

_**Note:** In v2, your taxonomy's route was configured externally in `site/settings/routes.yaml`.  When running this migrator on an individual taxonomy, we generate a new route for your taxonomy.  If you wish to preserve your original route, copy your old `site` folder into your v3 project root before running this migration._

### User migrator

In v3, users are mostly plug-and-play.  The most notable change being that `email` now replaces `username`, and is used as the new file name.  To migrate a user...

1. Copy your v2 user into your v3 project.
    - ie. `site/users/hasselhoff.yaml` -> `users/hasselhoff.yaml`
2. Run `php please migrate:user hasselhoff`.
3. Manually test and address errors and warnings as you see fit.

_**Note:** This migrator currently only handles file users.  If you are setup using eloquent users, you will need to migrate them manually._

## After migration

When you are finished and happy with your migration, feel free to delete your `site` folder from your v3 project root, and then run the following command:

```
composer remove statamic/migrator
```

## Reporting issues

While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  For this reason, we still recommend getting familiar with the [upgrade guide](https://statamic.dev/upgrade-guide).  Though we can't migrate everything, hopefully you have found this package useful in your transition to v3.  If you come across a bug or issue that you think needs to be addressed, please [open an issue](https://github.com/statamic/migrator/issues/new).
