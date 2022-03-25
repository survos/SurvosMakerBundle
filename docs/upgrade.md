### Upgrading to Bootstrap5 and AdminKit

    yarn add https://github.com/survos/adminkit.git

    composer update symfony/flex 
    yarn upgrade "@symfony/webpack-encore@^1.1"
    yarn remove jquery
    yarn remove popper.js
    yarn add @popperjs/core
    yarn add Hinclude
    yarn remove admin-lte
    yarn upgrade sass-loader@11
    yarn remove bootstrap 
    yarn add bootstrap@next
    yarn upgrade @symfony/webpack-encore@1.1
    use Survos\BaseBundle\Event\KnpMenuEvent; # (instead of KevinPabst)

