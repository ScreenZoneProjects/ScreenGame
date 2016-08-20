<?php

namespace SnapGame;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class HomeControllerProvider implements ControllerProviderInterface {
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];

        $controllers
            ->get('/', 'SnapGame\HomeControllerProvider::index')
            ->bind('home')
        ;

        $controllers
            ->get('/game', 'SnapGame\HomeControllerProvider::game')
            ->bind('game')
        ;

        $controllers->post('/game', 'SnapGame\HomeControllerProvider::game');

        $controllers
            ->get('/gameover', 'SnapGame\HomeControllerProvider::gameover')
            ->bind('gameover')
        ;

        return $controllers;
    }

    public function index(Application $app) {
        // Initialisation du jeu
        $app['session']->set('game_begin', true);
        $app['session']->set('score', 0);
        $app['session']->set('life', $app['config']['snapgame']['nb_errors_allowed']);
        $app['session']->set('nb_choices', $app['config']['snapgame']['nb_choices']);

        return $app['twig']->render('HomeController/index.twig');
    }

    public function game(Application $app, Request $request) {
        if (!$app['session']->has('game_begin')) {
            return $app->redirect($app->url('home'));
        }

        if ('POST' === $request->getMethod()) {
            $user_answer    = (int) $request->request->get('answer');
            $time_end       = time();
            $previous_score = $app['session']->get('score');

            // Si mauvaise réponse, on perd une vie
            if ($user_answer !== $app['session']->get('answer')) {
                $app['session']->set('life', $app['session']->get('life') - 1);

                // Si plus de vie, on va au game over
                if (0 >= $app['session']->get('life')) {
                    return $app->redirect($app->url('gameover'));
                }
            } else {
                // Si bonne réponse, calcul...
                $app['session']->set('score',
                    $app['session']->get('score') +
                    100 * $app['session']->get('nb_choices') +
                    10 * ($app['config']['snapgame']['answer_timeout'] - ($time_end - $app['session']->get('time_begin')))
                );

                // Si on atteind le palier de score on ajoute une réponse possible
                /*if (0 === $app['session']->get('score') % $app['config']['snapgame']['score_limit_for_new_answer']) {
                    $app['session']->set('nb_choices', $app['session']->get('nb_choices') + 1);
                }*/

                // Si on atteind un palier de vie, on l'ajoute
                foreach ($app['config']['snapgame']['life_steps'] as $step) {
                    if ($previous_score < $step && $app['session']->get('score') > $step) {
                        $app['session']->set('life', $app['session']->get('life') + 1);
                    }
                }
            }

            return $app->redirect($app->url('game'));
        }

        // Récupération d'un jeu et de X réponses au hasard
        $game       = $app['db']->fetchAssoc('SELECT id, name, image FROM games ORDER BY RAND() LIMIT 1');
        $answers    = $app['db']->fetchAll(sprintf('SELECT id, name FROM games ORDER BY RAND() LIMIT %u', $app['session']->get('nb_choices')));
        $max_score  = $app['db']->fetchAssoc('SELECT username, MAX(score) AS score FROM scores');

        $app['session']->set('answer', (int) $game['id']);
        $app['session']->set('time_begin', time());

        return $app['twig']->render('HomeController/game.twig', [
            'game'              => $game,
            'answers'           => $answers,
            'max_score'         => $max_score,
            'answer_timeout'    => $app['config']['snapgame']['answer_timeout'],
            'score'             => $app['session']->get('score'),
            'life'              => $app['session']->get('life'),
        ]);
    }

    public function gameover(Application $app) {
        if (!$app['session']->has('game_begin')) {
            return $app->redirect($app->url('home'));
        }

        return $app['twig']->render('HomeController/gameover.twig');
    }
}
