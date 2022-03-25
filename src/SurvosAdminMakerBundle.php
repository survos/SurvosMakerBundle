<?php
namespace Survos;

use Survos\BaseBundle\DependencyInjection\Compiler\SurvosBaseCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SurvosAdminMakerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new SurvosBaseCompilerPass());
    }

}
