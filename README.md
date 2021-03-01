# Statamic Migrator

![Statamic 3.0+](https://img.shields.io/badge/Statamic-3.0+-FF269E?style=for-the-badge&link=https://statamic.com)

🤘 Make migrating from v2 to v3 all the moar easier!

📺 See the migrator in action [in this screencast](https://youtu.be/OeXbaeuJrws).

- [Using the site migrator](#using-the-site-migrator)
- [Using individual migrators](#using-individual-migrators)
    - [Fieldset to blueprint migrator](#fieldset-to-blueprint-migrator)
    - [Fieldset partial migrator](#fieldset-partial-migrator)
    - [Collection migrator](#collection-migrator)
    - [Pages migrator](#pages-migrator)
    - [Taxonomy migrator](#taxonomy-migrator)
    - [Asset container migrator](#asset-container-migrator)
    - [Globals migrator](#globals-migrator)
    - [Form migrator](#form-migrator)
    - [User migrator](#user-migrator)
    - [Roles migrator](#roles-migrator)
    - [Groups migrator](#groups-migrator)
    - [Settings migrator](#settings-migrator)
    - [Theme migrator](#theme-migrator)
- [Reporting issues](#reporting-issues)

## Using the site migrator

The site migrator is the recommended way to migrate your site.  To get started...

1) Install a fresh instance of [Statamic v3](https://statamic.dev/installation) in a new location, and require the migrator:

    ```
    composer require statamic/migrator --dev
    ```

2) Clear your new site to ensure all default content is removed prior to migration:

    ```
    php please site:clear
    ```

3) Ensure you are running the latest version of Statamic in your v2 project.

4) Copy your v2 project's `site` folder, as well as any local asset container folders, into the root of your v3 project.

    - If you were running above webroot, be sure to copy your `public/themes` folder into `site/themes` as well.

5) Commit all your changes up to this point, so that you can view a diff of all the changes performed by the migrator, and easily rollback if necessary.

6) Run the following command to initiate the migration:

    ```
    php please migrate:site
    ```

7) Address any errors and warnings, and re-run `migrate:site` until there are no remaining issues.

    - Use the `--force` flag if you would like to overwrite previously migrated files.

    - While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  Checkout the [upgrade guide](https://statamic.dev/upgrade-guide) for more info on breaking changes.

8) When you are finished and happy, feel free to delete your `site` and asset container folders from your v3 project root, and then run the following command:

    ```
    composer remove statamic/migrator --dev
    ```

9) Order pizza! 🍕 🤘 😎

---

## Using individual migrators

If you require a more granular approach, you may also run the individual migrator commands.  Please read [using the site migrator](#using-the-site-migrator) before starting, to ensure everything is properly prepared for migration.

### Fieldset to blueprint migrator

In v3, [blueprints](https://statamic.dev/blueprints) are the replacement to fieldsets.  It's worth noting that [fieldsets](https://statamic.dev/fieldsets) technically still exist, although they are a now a smaller, companion feature to blueprints.  To migrate a fieldset to a blueprint:

```
php please migrate:fieldset post
```

In this example, `post` is the fieldset handle.

### Fieldset partial migrator

In v3, [reusable fields](https://statamic.dev/blueprints#reusable-fields) are stored in [fieldsets](https://statamic.dev/blueprints#importing-fieldsets) instead of blueprints.  If you have a fieldset that was previously imported using the `partial` fieldtype, use this migrator to ensure it is migrated to an importable fieldset, as well as a standalone blueprint.  It's worth noting that the [site migrator](#using-the-site-migrator) automatically detects the use of partials, and will run the appropriate migration for you.  To migrate a fieldset partial:

```
php please migrate:fieldset-partial address
```

In this example, `address` is the fieldset handle.

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

## Reporting issues

While we hope to automate most of the common tedious stuff for you, anything more custom may need to be manually migrated.  For this reason, we recommend getting familiar with the [upgrade guide](https://statamic.dev/upgrade-guide).  Though we can't automate everything, hopefully you have found this package useful in your transition to v3.  If you come across a bug or issue that you think needs to be addressed, please [open an issue](https://github.com/statamic/migrator/issues/new).
