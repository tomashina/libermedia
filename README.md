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

Do not pull directly into the live document root. Prepare a separate release, attach the existing production configuration, media and storage, verify the release, and then switch the active release atomically. Keep the previous release available for immediate rollback.
