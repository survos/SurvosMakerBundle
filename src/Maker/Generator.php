<?php

namespace Survos\Bundle\MakerBundle\Maker;

use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator as SymfonyGenerator;
use Symfony\Bundle\MakerBundle\Util\PhpCompatUtil;
use Symfony\Bundle\MakerBundle\Util\TemplateComponentGenerator;

class Generator extends SymfonyGenerator
{
    public function __construct(FileManager $fileManager, string $namespacePrefix, PhpCompatUtil $phpCompatUtil = null, TemplateComponentGenerator $templateComponentGenerator = null)
    {
        parent::__construct($fileManager, $namespacePrefix, $phpCompatUtil, $templateComponentGenerator);
    }

    public function getRootDirectory(): string
    {
        return parent::getRootDirectory(); 
    }

    public function generateBundleClass()
    {
    }
}
