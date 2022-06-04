

Add the namespace to composer.json:
```json
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "Foo\\": "lib/foo"
        }
    },

```
```bash
# important!  So that maker bundle can find the namespace, don't just clear the cache.
composer dump-autoload
```

echo "maker: { root_namespace: Foo }" > config/packages/maker.yaml

