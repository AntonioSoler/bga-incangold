<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * incangold implementation : © Antonio Soler <morgald.es@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * gameoptions.inc.php
 *
 * incangold game options description
 *
 * In this file, you can define your game options (= game variants).
 *
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in incangold.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = [
    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => [
        'name' => totranslate('Artifact Scoring'),
        'values' => [
            // A simple value for this option:
            1 => [
                'name' => totranslate('Modern'),
                'description' => totranslate('The artifacts are worth the value printed on them (5, 7, 8, 10, 12)')
            ],
            2 => [
                'name' => totranslate('Classic'),
                'description' => totranslate('The first three artifacts picked are worth 5, the last two are worth 10')
            ],
        ]
    ]
];


