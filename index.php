<?php
include __DIR__ . '/lib/php-file-cache.php';

$cache_path = path::get('cache', 'parts');

$Cache = new PhpFileCache([
  'path' => $cache_path,
]);

option::set('cache', $Cache);