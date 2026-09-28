# Liber Media

OpenCart 3.0.3.8 application source for the Liber Media webshop.

## Layout

- `upload/` is the public web root.
- `storageunbtbl/` contains the OpenCart storage runtime and vendor libraries.
- `tools/` contains local maintenance helpers.

## Environment files

Database dumps, product images, runtime logs, sessions, generated caches and the two environment-specific `config.php` files are intentionally excluded from Git.

For a new environment, copy `upload/config.example.php` to `upload/config.php` and `upload/admin/config.example.php` to `upload/admin/config.php`, then provide the correct URLs, absolute paths and database credentials.

## Deployment

The current Hetzner Level 4 package does not provide an interactive SSH shell, so deployment uses SFTP instead of a server-side `git pull`. Copy `.deploy.env.example` to `.deploy.env` and adjust the private-key path. Preview a commit range first:

```sh
php tools/deploy-sftp.php --from=<previous-commit> --to=<new-commit>
```

Apply the same range only after reviewing the preview:

```sh
php tools/deploy-sftp.php --from=<previous-commit> --to=<new-commit> --apply
```

Only deployable application files are transferred. Production configuration, images, database data, logs and sessions are never touched. Each changed file is uploaded under a temporary name and atomically renamed into place. Existing remote files are downloaded into `.deploy-backups/` before deployment, and an unsuccessful health check triggers an automatic rollback.

If the hosting package is later upgraded to one with SSH access, prefer timestamped release directories with a shared production configuration/media layer and an atomic `current` symlink switch.
