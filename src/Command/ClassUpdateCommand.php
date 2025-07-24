<?php

namespace Survos\Bundle\MakerBundle\Command;

use SebastianBergmann\Diff\Differ;
use Survos\Bundle\MakerBundle\Service\MakerService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StreamableInputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

#[AsCommand('survos:class:update', 'insert or update a method in an existing class')]
final class ClassUpdateCommand extends Command
{

    public function __invoke(
        SymfonyStyle $io,
        MakerService $makerService,
        #[Argument(description: 'name of the class (path or FQCN)')]
        string $className,
        #[Option('method', shortcut: 'm', description: 'name of the method (to check for existence)')]
        ?string $methodName,
        #[Option(description: 'overwrite if exists')]
        ?bool $force,
        #[Option(description: 'class use statements')]
        array $use,
        #[Option(description: 'class traits')]
        array $trait,
        #[Option(description: 'class implements')]
        array $implements,
        #[Option(shortcut: 'di', description: 'inject into __construct/__invoke')]
        array $inject,
        #[Option(name: 'dry-run', shortcut: 'dry', description: 'do not actually modify the class file')]
        bool $dryRun,
        #[Option(description: 'show a diff of the changes')]
        bool $diff,
    ): void {


        // this is only valid is are no prompts.
        $input = $io->input();
        $inputStream = ($input instanceof StreamableInputInterface) ? $input->getStream() : null;
        $inputStream = $inputStream ?? STDIN;
        $methodPhp = stream_get_contents($inputStream);

        $reflectionClass = $makerService->getReflectionClass($className);

        // Ideally, we'd replace the function / function body use AST
        // https://tomasvotruba.com/blog/2017/11/06/how-to-change-php-code-with-abstract-syntax-tree/

        $source = $makerService->modifyClass($reflectionClass, traits: $trait, uses: $use, injects: $inject, methodName: $methodName, php: $methodPhp);

        if ($diff) {
            $differ = new Differ();
            $io->write($differ->diff($reflectionClass->getLocatedSource()->getSource(), $source));
        }

        if (!$dryRun) {
            file_put_contents($reflectionClass->getFileName(), $source);
        }

        $io->success(sprintf('Class %s Updated ' . $reflectionClass->getFileName(), $dryRun ? 'not' : ''));
    }
}
