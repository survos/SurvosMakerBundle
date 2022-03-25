<?php

namespace Survos\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

class SurvosSetupHerokuCommand extends Command
{
    protected static $defaultName = 'survos:setup-heroku';
    /**
     * @var Environment
     */
    private $twig;

    public function __XXXXconstruct(Environment $twig, string $name = null)
    {
        parent::__construct($name);
        $this->twig = $twig;
    }

    protected function configure()
    {
        $this
            ->setDescription('Creates files for heroku deployment')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        $procfile = $this->twig->render("@SurvosBase/Procfile.twig", []);


        // heroku init

        // setup ENV vars

        // tweak monolog: https://devcenter.heroku.com/articles/deploying-symfony3#changing-the-log-destination-for-production

        // add node: heroku buildpacks:add --index 2 heroku/nodejs

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return 0;
    }
}
