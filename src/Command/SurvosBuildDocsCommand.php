<?php

namespace Survos\BaseBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;
use Twig\Environment;

class SurvosBuildDocsCommand extends Command
{
    protected static $defaultName = 'survos:build-docs';
    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Environment $twig, string $name = null)
    {
        parent::__construct($name);
        $this->twig = $twig;
    }

    protected function configure()
    {
        $this
            ->setDescription('Compile .rst.twig files')
            ->addArgument('template-dir', InputArgument::OPTIONAL, 'Template Directory',  './templates/')
            ->addArgument('template-subdir', InputArgument::OPTIONAL, 'Template Subdirectory', 'docs/')
            ->addOption('output-dir', 'o', InputOption::VALUE_OPTIONAL, 'Output Directory (the .rst file)',  './docs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dir = $input->getArgument('template-dir');
        $subdir = $input->getArgument('template-subdir');

        $finder = new Finder();
        $finder->files()->in($dir . $subdir);

        foreach ($finder as $file) {
            $rst = $this->twig->render($subdir . $file->getBasename(), [

            ]);
            $outputFilename = $input->getOption('output-dir') . $file->getBasename('.twig');
            file_put_contents($outputFilename, $rst);
            $output->write("$outputFilename written.", true);
        }


        $io->success('Templates compiled, now run make html');

        return 0;
    }
}
