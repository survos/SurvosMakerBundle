# make:bundle

The bundle maker is considerably more complicated that the other maker scripts, because files are being generated that are included outside of the application namespace.

The basic workflow is to

* Establish a separate namespace for the bundle pointing to a directory within the repository.
* composer dump-autoload -o
* Create the bundle-specific file structure (controllers, services, templates, packages, etc.), without dependencies
* Create the *Bundle.php, automatically wiring the relevant services
* Create the composer.json files in that directory
* Add the external dependencies
* unset the bundle namespace from the app repository
* set the config.repositories to point to the local directory and install it.
* develop the bundle, making sure to run composer update if 3rd-party dependecies are added to the bundle.
* create a github repo for the bundle, reset the config.repositories to point to the private github repo.
* Add bundle to packagist
* Submit recipe to recipes-contrib
* delete the config.repositories line once it's on packagist.


open maker.yaml and set the root_namespace: /bundle-dev
```yaml
maker:
  root_namespace: Survos\CsvBundle

survos_maker:
  vendor: Survos
  bundle_name: CsvBundle
```

```bash
# set the namespace keys.  idea: use ENV var?
bin/console survos:bundle CsvBundle Survos --force 
composer dump-autoload -o

```




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

bin/console survos:make:bundle Survos CsvBundle --dir=../bundle-dev
cd  
git init
gh repo create survos/SurvosFooBundle --private -s .

cd ../bundles/$REPO

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
    composer config repositories.survos_test '{"type": "path", "url": "lib/temp"}'
    composer req survos/survosfoo-bundle:"*@dev"

survos/-bundle




composer config repositories.survos_foo '{"type": "path", "url": "lib/FooBundle"}'
composer req survos/survosfoo-bundle:"*@dev"

echo "maker: { root_namespace: Survos\FooBundle }" > config/packages/maker.yaml
bin/console make:twig FooTwigExtension

