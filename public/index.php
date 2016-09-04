<?php

use GeckoPackages\Silex\Services\Config\ConfigServiceProvider;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\FormServiceProvider;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TranslationServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use SnapGame\BaseApplication;
use SnapGame\HomeControllerProvider;

ini_set('display_errors', true);
ini_set('error_reporting', -1);
date_default_timezone_set('Europe/Paris');

require_once __DIR__.'/../vendor/autoload.php';

$app = new BaseApplication();

// Configuration du fichier de config
$env = getenv('APP_ENV') ?: 'prod';

$app->register(new ConfigServiceProvider(), [
    'config.dir'    => __DIR__.'/../data/config',
    'config.format' => sprintf('%%key%%.%s.json', $env),
]);

$app['debug'] = $app['config']['snapgame']['debug'];

// Configuration de Twig
$app->register(new TwigServiceProvider(), [
    'twig.path' => __DIR__.'/../app/SnapGame/views',
    'twig.options' => [
        'cache' => realpath(__DIR__ . '/../data/cache'),
    ],
]);

$app->extend('twig', function ($twig, $app) {
    // Rendu du pseudo d'un joueur avec les lettres glyph
    $twig->addFilter(
        new Twig_SimpleFilter('render_username_glyph', function ($input) {
            $return = '';

            for ($x = 0; $x < mb_strlen($input); $x++) {
                $char = $input[$x];

                if (' ' === $char) {
                    $return .= '<span class="sprite glyphe-space">&nbsp;</span>';
                } else {
                    $return .= sprintf('<span class="sprite glyphe-%s">%s</span>', mb_strtolower($char), $char);
                }
            }

            return $return;
        }, ['is_safe' => ['html']])
    );

    // Rendu d'un score avec les LEDs
    $twig->addFilter(
        new Twig_SimpleFilter('render_score_leds', function ($input, $assets_base) {
            $return         = '';
            $input          = str_pad($input, 6, 'E', STR_PAD_LEFT);
            $assets_base    = substr($assets_base, 0, strrpos($assets_base, '?'));

            for ($x = 0; $x < mb_strlen($input); $x++) {
                $return .= sprintf('<img src="%s/led/led-%s.png" alt="" />', $assets_base, $input[$x]);
            }

            return $return;
        }, ['is_safe' => ['html']])
    );

    // base64 des images
    $twig->addFilter(new Twig_SimpleFilter('imgbase64', function ($image_path) {
        return base64_encode(file_get_contents(__DIR__.'/assets/img/snaps/'.$image_path));
    }));

    return $twig;
});

// Configuration des assets
$app->register(new AssetServiceProvider(), [
    'assets.version' => 'v20160904',
    'assets.named_packages' => [
        'css' => [
            'base_path' => 'assets/css/',
        ],
        'images' => [
            'base_path' => 'assets/img/',
        ],
        'js' => [
            'base_path' => 'assets/js/',
        ],
        'vendor' => [
            'base_path' => 'assets/vendor/',
        ],
    ],
]);

// Configuration de Doctrine
$app->register(new DoctrineServiceProvider(), [
    'db.options' => [
        'dbname'    => $app['config']['snapgame']['database']['dbname'],
        'user'      => $app['config']['snapgame']['database']['username'],
        'password'  => $app['config']['snapgame']['database']['password'],
        'host'      => $app['config']['snapgame']['database']['host'],
        'driver'    => 'pdo_mysql',
    ],
]);

// Configuration des sessions
$app->register(new SessionServiceProvider(), [
    'session.storage.options' => [
        'name' => 'snap_game',
    ],
]);

// Configuration des traductions (requis pour les formulaires)
$app->register(new LocaleServiceProvider());
$app->register(new TranslationServiceProvider(), ['locale_fallbacks' => ['fr']]);

// Configuration des formulaires
$app->register(new FormServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new TranslationServiceProvider(), ['translator.domains' => []]);

// Routing
$app->mount('/', new HomeControllerProvider());

$app->run();
