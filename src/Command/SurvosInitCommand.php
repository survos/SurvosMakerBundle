<?php

namespace Survos\BaseBundle\Command;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Yaml\Yaml;
use Twig\Environment;

class SurvosInitCommand extends Command
{
    protected static $defaultName = 'survos:init';

    protected $projectDir;
    private $kernel;
    private $em;
    private $twig;
    private $toolsRequested; // array of requested tools

    private $appCode;

    /** @var SymfonyStyle */
    private $io;

    CONST recommendedBundles = [
//        'EasyAdminBundle',
        'SurvosWorkflowBundle',
//        'UserBundle'
    ];

    CONST requiredJsLibraries = [
        'jquery',
        'sass-loader@^11',
        'node-sass',
        'simulus',
        'Hinclude',
//        'bootstrap', // actually, this comes from adminlte, so maybe we shouldn't load it.
//        'fontawesome',
        '@popperjs/core'
    ];

    CONST tools = [
        'heroku',
        'easyadmin',
        'all'
    ];

    private ConsoleLogger $consoleLogger;
    private ParameterBagInterface $parameterBag;

    public function __construct(KernelInterface $kernel, EntityManagerInterface $em,
                                ParameterBagInterface $parameterBag,
                                Environment $twig, string $name = null)
    {
        parent::__construct($name);
        $this->kernel = $kernel;
        $this->em = $em;
        $this->projectDir = $kernel->getProjectDir();
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
    }
    protected function configure()
    {

        $this
            ->setDescription('Basic environment: base page, heroku, yarn install, sqlite in .env.local, ')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('heroku', null, InputOption::VALUE_NONE, 'configure heroku (must be logged in.)')
        ;
    }

    private function setAppCode($appCode) {
        $this->appCode = $appCode;
    }

    private function getAppCode() {
        // default  is the directory
        if (empty($this->appCode)) {
            $this->appCode = basename($this->kernel->getProjectDir());
        }
       return $this->appCode;
    }


    private function toolRequested($tool)
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->consoleLogger = new ConsoleLogger($output);

        $this->io = $io = new SymfonyStyle($input, $output);
        $all = true; // for now.  in_array('all', $input->getOption('tools'));

        if ($input->getOption('heroku')) {
        // we should look in .git/config for the heroku repo
            $this->checkHeroku($io);
            return 1;
        }

        // handle fontawesomefree, hack, need to ask

//        if (!file_exists($npmRcFile = $this->projectDir . '/.npmrc')) {
//            file_put_contents($npmRcFile, "@fortawesome:registry=");
//        }

        $this->checkYarn($io);
        $this->installYarnLibraries($io);
        $this->createConfigs($io);

        // @todo: use this in the base bundle!!  Or prompt and use SVG, then colors could be used for environment.
        $this->createFavicon($io);
        $this->createTranslations($io);
        $this->setupDatabase($io);
        $this->updateBase($io);

        // perhaps install required yarn modules here?  Then in setup have the optional ones?

        /*
         *
         */


        $io->success("Run xterm -e \"yarn run encore dev-server\" & install more bundles, then run bin/console survos:configure");
        return self::SUCCESS;
    }

    private function updateBase(SymfonyStyle $io) {
        $fn = '/templates/base.html.twig';
        if ($io->confirm("Replace $fn?")) {
            $this->writeFile($fn, '{% extends "@SurvosBase/adminkit/layout.html.twig" %}');
        }
    }

    private function createFavicon(SymfonyStyle $io)
    {
        // $this->handleFavicon($io);
        // https://favicon.io/favicon-generator/?t=Fa&ff=Lancelot&fs=99&fc=%23FFFFFF&b=rounded&bc=%23B4B


        $host = 'https://favicon.io/favicon-generator/?';
        $params = [
            't' => $this->getAppCode(), // @todo, just get the first letters, e.g. something-demo => sd
            'ff' => 'Lancelot',
            'fs' => 80,
            'b' => 'rounded',
            // @todo random colors, etc.
        ];
        $url = $host . http_build_query($params);
        $io->writeln("\n\nDownload zip file at $url");

        $zipFile = 'favicon_io.zip';


        do {
            $path = $io->ask("path to $zipFile?  Defaults to directory ABOVE the repo.  Use ! to skip", '../');
            $fn = $path . $zipFile;
            if ($path !== '!') {
                if (!file_exists($fn)) {
                    $this->io->error("$fn does not exist.");
                }
            }
        } while (( $path !== '!') && !file_exists($fn) );

        if ($path === '!') {
            return;
        }

        if (!file_exists($fn . $zipFile)) {
            // re-ask
        }
        $zip = new \ZipArchive();

        if ($zip->open($fn) === TRUE) {
            $publicDir = $this->projectDir . '/./public';
            for($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);
                $io->writeln('Extracting ' . $filename . ' to ' . $publicDir);
                if (!$zip->extractTo($publicDir, array($zip->getNameIndex($i)))) {
                    $io->error(sprintf("Unable to extract %s to %s", $filename, $publicDir));
                }
                // copy("zip://".$path."#".$filename, "/your/new/destination/".$fileinfo['basename']);
            }

            // $zip->extractTo($publicDir);
            $zip->close();
            $io->success('Favicons extracted');
        } else {
            $io->error('Error extracting Favicons');
            return -1;
        }
    }

    private function createTranslations(SymfonyStyle $io) {
        $fn = '/translations/messages+intl-icu.en.yaml'; // @todo: get current default language code
        if ($io->confirm("Replace $fn?")) {
            $appCode = $this->getAppCode();
            $appCode =  $io->ask("Short Code?", $appCode);
            $t = [
                'home' => [
                    'title' => $title = $io->ask('Title?', "$appCode Title"),
                    'intro' => "Intro to $title",
                    'description' => $io->ask('description?', "$appCode *Description*, in _markdown_")
                ]
            ];
            $this->writeFile($fn, Yaml::dump($t, 5));
        }
    }


    private function checkYarn(SymfonyStyle $io)
    {
        if (!file_exists($this->projectDir . '/yarn.lock')) {
            $io->warning("Installing base yarn libraries with 'yarn install'");
            echo exec('yarn install');
        }

        // install the base yarn commands, this used to be in setup
    }

    private function installYarnLibraries(SymfonyStyle $io)
    {
        if (!file_exists($this->projectDir . '/yarn.lock')) {
            $io->error("run yarn install or bin/console survos:init first");
            die();
        }

        $packageFile = $this->parameterBag->get('kernel.project_dir') . '/package.json';

        $packageData = json_decode(file_get_contents($packageFile));

        $packageDependencies = $packageData->dependencies ?? [];
        $packageDevDependencies = $packageData->devDependencies ?? [];
        // dd($packageDevDependencies);
        $allPackages = array_merge((array)$packageDevDependencies, (array)$packageDependencies);

        $missing = [];

        $pro = false; // also need to set .npmrc or the env var

        $requiredJsLibraries = self::requiredJsLibraries;
        $fa = '@fortawesome/fontawesome-' . ($pro ? 'pro' : 'free');
        array_push($requiredJsLibraries, $fa);

        foreach ($requiredJsLibraries as $jsLibrary) {
            if (strpos($jsLibrary, '@') > 3) {
                list($package, $version) = explode('@', $jsLibrary);
            } else {
                $package = $jsLibrary;
                $version = '*';
            }
            if (!key_exists($package, $allPackages)) {
                array_push($missing, $jsLibrary);
                dump($package);
            } else {
                $io->writeln(sprintf("%s  installed as version %s", $jsLibrary, $allPackages[$package]));
            }
        }

        // dd($allPackages, $missing); // in package.json

        /* old way...
        $json = exec(sprintf('yarn list  --json') );
            $data = json_decode($json, true)['data']['trees'];

            $yarnModules = array_map(function ($moduleData) {
                try {
                    list($name, $version) = explode('@', $moduleData['name']);
                    return [$name => $version];
                } catch (\Exception $e) {
                    dd($moduleData);
                }
                }, $data);

            $missing = array_filter(self::requiredJsLibraries, function ($needle) use ($yarnModules) {

                $missingModule =  !in_array($needle, array_keys($yarnModules));
                dd(array_keys($yarnModules), $missingModule, $needle);
                $this->consoleLogger->warning(($missingModule ? 'Missing' : 'found')  . ' ' . $needle, [$needle]);

            });
            dd($missing);

        try {
            $modules = array_map(function ($tree) {
                if (is_string($tree)) {
                    return $tree;
                }

                if ( preg_match('/(.*)(@\^?[\d\.]+)$/', $tree['name'], $m) ) {
                    $name = $m[1];
                }
                // [$name, $version] = explode('@', $tree['name']);

                return $name;
            }, $yarnModules);

            // sort($modules); dump($modules); die();
        } catch (\Exception $e) {
            $io->error("Yarn failed -- is it installed? " . $e->getMessage());
        }

        $missing = array_diff(self::requiredJsLibraries, array_keys($modules));
        */

        if ($missing) {
            $io->error("Missing " . join(',', $missing));
            $command = sprintf("yarn add %s --dev", join(' ', $missing));
            if ($io->confirm("Install them now? with $command? ", true)) {
                echo exec($command) . "\n";
                // return $this->checkYarn($io); // recursive hack, should be refactored!
            } else {
                die("Cannot continue without yarn modules");
            }
        } else {
            return [];
        }


        /* better: */
        /*
        $process = new Process(['yarn', 'run', 'encore', 'dev']);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();
        */




    }
    private function checkHeroku(SymfonyStyle $io)
    {

        $this->io->writeln("Checking Heroku");
        // @todo: check buildpacks
        $this->io->writeln(exec("heroku buildpacks:add heroku/php"));
        $this->io->writeln(exec("heroku buildpacks:add heroku/nodejs"));

        // @todo: heroku config:set APP_ENV=prod, etc.

        if (!file_exists($this->projectDir . ($fn = '/Procfile'))) {
           //  $io->warning("Installing base yarn libraries");
            $procfile = $this->twig->render("@SurvosBase/heroku/Procfile.twig", []);
            $this->writeFile($fn, $procfile);
        }
        if (!file_exists($this->projectDir . ($fn = '/fpm_custom.conf'))) {
            //  $io->warning("Installing base yarn libraries");
            $procfile = $this->twig->render("@SurvosBase/heroku/fpm_custom.conf.twig", []);
            $this->writeFile($fn, $procfile);
        }

        if (!file_exists($this->projectDir . ($fn = '/heroku-nginx.conf'))) {
            //  $io->warning("Installing base yarn libraries");
            $procfile = $this->twig->render("@SurvosBase/heroku/heroku-nginx.conf.twig", []);
            $this->writeFile($fn, $procfile);
        }

        // fix monolog key
        $monologFile = $this->projectDir . ($fn = '/config/packages/prod/monolog.yaml');
        $data = Yaml::parse(file_get_contents($monologFile));
        $data['monolog']['handlers']['nested']['path'] = "php://stderr";
        $newData = Yaml::dump($data, 4);
        $this->writeFile($fn, $newData);


    }

    private function setupDatabase(SymfonyStyle $io) {
        $localExists = file_exists($fn = $this->projectDir . '/.env.local');
        $data = "MAILER_DSN=smtp://localhost\n\n"; // hack, why doesn't symfony do this??  Maybe do it in the survos recipe?
        if (!$localExists && $io->confirm('Use sqlite database in .env.local', true)) {
            $data .= "DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db";
            if (!file_exists($fn = $this->projectDir . '/.env.local')) {
                file_put_contents($fn, $data);
            }
        }
    }

    private function writeFile($fn, $contents) {
        file_put_contents($output = $this->projectDir . $fn, $contents);
        $this->io->success($fn . " written.");
    }

    /**
     * @param SymfonyStyle $io
     * @param string $output
     */
    private function createConfigs(SymfonyStyle $io): void
    {
// configure the route
        if ($prefix = $io->ask("Base Route Prefix", '/')) {
            $routes_by_name_config = <<< END

survos_landing: {path: /landing, controller: 'Survos\BaseBundle\Controller\LandingController::landing'}
app_homepage: {path: /, controller: 'Survos\BaseBundle\Controller\LandingController::landing'}
app_heroku: {path: /heroku, controller: 'Survos\BaseBundle\Controller\LandingController::heroku'}
app_logo: {path: /logo, controller: 'Survos\BaseBundle\Controller\LandingController::logo'}
app_profile: {path: /profile, controller: 'Survos\BaseBundle\Controller\LandingController::profile'}
profile: {path: /profile, controller: 'Survos\BaseBundle\Controller\LandingController::profile'}
#logout: {path: /profile, controller: 'Survos\BaseBundle\Controller\LandingController::logout'}
# required if app_profile is used, since you can change the password from the profile
app_change_password: {path: /change-password, controller: 'Survos\BaseBundle\Controller\LandingController::changePassword'}
app_typography: {path: /typography, controller: 'Survos\BaseBundle\Controller\LandingController::typography'}
survos_base_credits: {path: "/credits/{type}", controller: 'Survos\BaseBundle\Controller\LandingController::credits'}
END;


            $fn = '/config/routes/survos_base.yaml';
            $config = [
//                'survos_base_bundle' => [
//                    'resource' => '@SurvosBaseBundle/Controller/LandingController.php',
//                    'prefix' => $prefix,
//                    'type' => 'annotation'
//                ],
                'survos_base_bundle_oauth' => [
                    'resource' => '@SurvosBaseBundle/Controller/OAuthController.php',
                    'prefix' => $prefix,
                    'type' => 'annotation'
                ],

            ];
            file_put_contents($output = $this->projectDir . $fn, Yaml::dump($config) . "\n\n" . $routes_by_name_config);
            $io->comment($fn . " written.");
        }

        // use twig? Php?

        $yaml = <<< END
survos_base:
  theme: adminlte
#  theme: start-bootstrap
#  menu:
#    enable: true
#    main_menu: survos_sidebar_menu
#    breadcrumb_menu: true
#

twig:
  globals:
    theme: adminlte
  paths:
    'vendor/survos/base-bundle/src/Resources/views/adminlte': 'theme'
END;

        // should we remove admin_lte.yaml??
//        @unlink('/config/packages/admin_lte.yaml');

        $fn = '/config/packages/survos_base.yaml';
        if (!file_exists($fn)) {
            file_put_contents($output = $this->projectDir . $fn, $yaml);
            $io->comment($fn . "  written.");
        }
    }

}
