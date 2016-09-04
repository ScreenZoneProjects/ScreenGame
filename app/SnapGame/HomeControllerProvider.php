<?php

namespace SnapGame;

use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

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

        $controllers->post('/gameover', 'SnapGame\HomeControllerProvider::gameover');

        return $controllers;
    }

    public function index(Application $app) {
        // Initialisation du jeu
        $app['session']->set('game_begin', true);

        $this->initGame($app);

        // Récupère le TOP X des scores
        $top = $app['db']->fetchAll('SELECT username, score FROM scores ORDER BY score DESC LIMIT 10');

        return $app['twig']->render('HomeController/index.twig', [
            'top' => $top,
        ]);
    }

    public function game(Application $app, Request $request) {
        if (!$app['session']->has('game_begin')) {
            return $app->redirect($app->url('home'));
        }

        if ('POST' === $request->getMethod()) {
            $user_answer    = (int) $request->request->get('answer');
            $answer_time    = max(0, (time() - $app['session']->get('time_begin')));
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
                    10 * ($app['config']['snapgame']['answer_timeout'] - $answer_time)
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

        // Récupération d'un jeu, de X réponses au hasard et du high-score
        $passed_answers = $app['session']->get('passed_answers', []);

        if ($app['config']['snapgame']['different_answers_limit'] < count($passed_answers)) {
            $passed_answers = [];
        }

        $answer_not_in      = !empty($passed_answers) ? sprintf('WHERE id NOT IN (%s)', implode(',', $passed_answers)) : '';
        $answers            = $app['db']->fetchAll(sprintf('SELECT id, name, image FROM games %s ORDER BY RAND() LIMIT %u', $answer_not_in, $app['session']->get('nb_choices')));
        $game               = current($answers);
        $max_score          = $app['db']->fetchAssoc('SELECT username, score FROM scores ORDER BY score DESC LIMIT 1');
        $passed_answers[]   = (int) $game['id'];

        shuffle($answers);

        $app['session']->set('answer', (int) $game['id']);
        $app['session']->set('time_begin', time());
        $app['session']->set('passed_answers', $passed_answers);

        return $app['twig']->render('HomeController/game.twig', [
            'game'              => $game,
            'answers'           => $answers,
            'max_score'         => $max_score,
            'answer_timeout'    => $app['config']['snapgame']['answer_timeout'],
            'score'             => $app['session']->get('score'),
            'life'              => $app['session']->get('life'),
        ]);
    }

    public function gameover(Application $app, Request $request) {
        if (!$app['session']->has('game_begin') && 'POST' !== $request->getMethod()) {
            return $app->redirect($app->url('home'));
        }

        // Sauvegarde du score actuel avant de réinitialiser les données
        if ('POST' !== $request->getMethod()) {
            $app['session']->set('current_score', $app['session']->get('score'));
        }

        $this->initGame($app);

        // Formulaire d'enregistrement du high-score
        $data   = ['username' => 'Anonymous'];
        $form   = $app->form($data)
            ->add('username', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(['pattern' => '/^[a-zA-Z0-9 ]{1,10}$/']),
                ]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data       = $form->getData();
            $existing   = $app['db']->fetchAssoc('SELECT id, score FROM scores WHERE username = ?', [$data['username']]);

            // Si le joueur n'existe pas, on le crée
            if (false === $existing) {
                $app['db']->executeUpdate(
                    'INSERT INTO scores (username, score, created_at) VALUES (?, ?, NOW())',
                    [$data['username'], $app['session']->get('current_score')]
                );
            // Sinon on met à jour son score si il est meilleur
            } elseif ($app['session']->get('current_score') > $existing['score']) {
                $app['db']->executeUpdate(
                    'UPDATE scores SET score = ?, created_at = NOW() WHERE id = ?',
                    [$app['session']->get('current_score'), $existing['id']]
                );
            }

            $app['session']->remove('current_score');

            return $app->redirect($app->url('home'));
        }

        return $app['twig']->render('HomeController/gameover.twig', [
            'form' => $form->createView(),
        ]);
    }

    protected function initGame(Application $app)
    {
        // Initialisation du jeu
        $app['session']->set('score', 0);
        $app['session']->set('life', $app['config']['snapgame']['nb_errors_allowed']);
        $app['session']->set('nb_choices', $app['config']['snapgame']['nb_choices']);
    }
}
