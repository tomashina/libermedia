<?php
// HTTP
define('HTTP_SERVER', 'https://libermedia.test/');

// HTTPS
define('HTTPS_SERVER', 'https://libermedia.test/');

// DIR
define('DIR_APPLICATION', '/absolute/path/to/libermedia/upload/catalog/');
define('DIR_SYSTEM', '/absolute/path/to/libermedia/upload/system/');
define('DIR_IMAGE', '/absolute/path/to/libermedia/upload/image/');
define('DIR_STORAGE', '/absolute/path/to/libermedia/storageunbtbl/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/theme/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
define('DB_DRIVER', 'mysqli');
define('DB_HOSTNAME', '127.0.0.1');
define('DB_USERNAME', 'database_user');
define('DB_PASSWORD', 'database_password');
define('DB_DATABASE', 'database_name');
define('DB_PORT', '3306');
define('DB_PREFIX', 'oc_');
