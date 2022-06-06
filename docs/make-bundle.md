

Goal: composer with 

name: "survos/foo-bundle"
"autoload": {
    "psr-4": { "Survos\\FooBundle\\": "src" },


```bash
REPO=b
BUNDLE_PATH=/home/tac/survos/bundle-dev
symfony new $REPO --webapp && cd $REPO

composer config repositories.survos_maker_bundle '{"type": "vcs", "url": "git@github.com:survos/AdminMakerBundle.git"}' 
OR

composer config repositories.survos_maker_bundle '{"type": "path", "url": "/home/tac/survos/bundles/maker-bundle"}' 
composer req survos/maker-bundle:*@dev --dev

bin/console survos:make:bundle Survos FooBundle --dir=../bundle-dev
cd  
git init
gh repo create survos/SurvosFooBundle --private -s .

cd ../bundles/$REPO
composer config repositories.survos_foo '{"type": "path", "url": "lib/FooBundle"}'
composer req survos/survosfoo-bundle:"*@dev"

echo "maker: { root_namespace: Survos\FooBundle }" > config/packages/maker.yaml
bin/console make:twig FooTwigExtension

```


Add the namespace to composer.json:
```json
{
  "autoload": {
    "psr-4": {
      "App\\": "src/",
      "Survos\\FooBundle\\": "bundle-dev/foo-bundle"
    }
  }
}
```
```bash
# important!  So that maker bundle can find the namespace, don't just clear the cache.
composer dump-autoload
```

echo "maker: { root_namespace: Survos\FooBundle }" > config/packages/maker.yaml


    composer config repositories.survos_foo '{"type": "path", "url": "lib/FooBundle"}'
    composer req survos/survosfoo-bundle:"*@dev"

survos/-bundle
