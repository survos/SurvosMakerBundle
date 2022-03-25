<?php

namespace Survos\BaseBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class SurvosSetupCommand extends Command
{
    protected static $defaultName = 'survos:configure';

    protected $projectDir;
    private $kernel;
    private $em;
    private $twig;

    /** @var SymfonyStyle */
    private $io;

    CONST recommendedBundles = [
//        'EasyAdminBundle' => ['repo' => 'admin'],
        'SurvosWorkflowBundle' => ['repo' => 'survos/workflow-bundle'],
        // 'MsgPhpUserBundle' => ['repo' => 'msgphp/user-bundle']
    ];


    public function __construct(KernelInterface $kernel, EntityManagerInterface $em, \Twig\Environment $twig, string $name = null)
    {
        parent::__construct($name);
        $this->kernel = $kernel;
        $this->projectDir = $kernel->getProjectDir();
        $this->twig = $twig;
        $this->em = $em;
    }

    public function setEntityManager(EntityManagerInterface $entityManager) {
        $this->em = $entityManager;
    }

    protected function configure()
    {

        $this
            ->setDescription('Setup libraries and basic base page')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = $io = new SymfonyStyle($input, $output);

        // $this->checkEntities($io);
        $this->createSubscribers($io);

        $bundles = $this->checkBundles($io);
        $yarnPackages = []; // used to be $this->checkYarn($io);
        $this->updateAssets($io, ['bundles' => $bundles, 'yarnPackages' => $yarnPackages]);


        $io->success('Base Configuration Complete.');
        return self::SUCCESS;
    }

    private function createSubscribers(SymfonyStyle $io) {


        $dir = $this->projectDir . '/src/EventSubscriber';
        $fn = $dir . '/SidebarMenuSubscriber.php';

        if (!file_exists($fn)) {
            // why ask?
            if ($prefix = $io->ask("Application Menu Subscriber Class", 'App/EventSubscriber/SidebarMenuSubscriber')) {
                if (!is_dir($dir)) {
                    mkdir($dir);
                }
                $php = $this->twig->render("@SurvosBase/SidebarMenuSubscriber.php.twig", []);

                // $yaml =  Yaml::dump($config);
                file_put_contents($output = $fn, $php);
                $io->comment($fn . " written.");
            }
        }

    }

    private function checkEntities(SymfonyStyle $io) {
        $entities = array();
        $em = $this->em;
        $meta = $em->getMetadataFactory()->getAllMetadata();
        foreach ($meta as $m) {
            $entities[] = $m->getName();
        }

        // if there are entities and easyadmin, create the easyadmin.yaml file??
        dump($entities);
    }

    private function updateAssets(SymfonyStyle $io, array $params) {
        $fn = '/templates/base.html.twig';
        if ($io->confirm("Replace app assets (js and css)?")) {
            // @todo: specific to yarn packages
            try {
                $file = $this->projectDir . '/webpack.config.js';
                $this->writeFile('/./webpack.config.js',
                    $x = str_replace('//.enableSassLoader()','.enableSassLoader()', file_get_contents($file)));
                $this->writeFile('/assets/app.js', $this->twig->render("@SurvosBase/app.js.twig", $params) );
                $this->writeFile('/assets/styles/app.scss', $this->twig->render("@SurvosBase/app.scss.twig", $params) );
            } catch (\Exception $e) {
                $io->error($e->getMessage());
            }
        }

        // this might be running with --watch, but this makes sure it happens after the write.
        echo exec('yarn run encore dev');
    }

    private function checkBundles(SymfonyStyle $io): array
    {
        $bundles = $this->kernel->getBundles();

        foreach (self::recommendedBundles as $bundleName=>$info) {
            if (empty($bundles[$bundleName])) {
                $io->warning($bundleName . ' is recommended, install it using composer req ' . $info['repo']);
            }
        }

        foreach ($bundles as $bundleName) {

        }

        return $bundles;

    }

    private function writeFile($fn, $contents) {
        file_put_contents($output = $this->projectDir . $fn, $contents);
        $this->io->success($fn . " written.");
    }
}
