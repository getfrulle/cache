# Cache - Plugin for IO CMS

```php
echo option('cache')->getOrSet('myfile', function() {
  sleep(2);
  return 'cached data';
});
```

## Dependencies

- Options plugin
- Path plugin