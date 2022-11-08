<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * DiceHospitalER implementation : © <Firgon> <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * DiceHospitalER game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

if (!defined('STATE_END_GAME')) { // guard since this included multiple times
    //define("START_GAME",2);
    define("THROW_DICE", 3);
    define("PLAYER_TURN", 4);
    define("OTHER_PLAYERS_TURN", 5);
    define("ALICAT_TURN", 6);
    define("END_TURN", 7);
    define("STATE_END_GAME", 99);
}

$machinestates = array(

    // The initial state. Please do not modify.
    1 => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => array("" => THROW_DICE)
    ),
    /*
    START_GAME => array(
        "name" => "startGame",
        "description" => clienttranslate('A new game begins'),
        "type" => "game",
        "action" => "stStartGame",
        "transitions" => array( "" => PLAYER_TURN )
),
*/
    //throw dice and give hand to first player (or ALICAT)
    THROW_DICE => array(
        "name" => "throwDice",
        "description" => clienttranslate('A new turn begins'),
        "type" => "game",
        "action" => "stThrowDice",
        "transitions" => array("normal" => PLAYER_TURN, "soloGame" =>  ALICAT_TURN)
    ),

    PLAYER_TURN => array(
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must choose a die'),
        "descriptionmyturn" => clienttranslate('${you} must choose a die'),
        "type" => "activeplayer",
        "possibleactions" => array("play", "pass"),
        "transitions" => array("play" => OTHER_PLAYERS_TURN, "pass" => OTHER_PLAYERS_TURN, "soloGame" => ALICAT_TURN)
    ),

    OTHER_PLAYERS_TURN => array(
        "name" => "otherPlayersTurn",
        "description" => clienttranslate('Other players must choose a die'),
        "descriptionmyturn" => clienttranslate('${you} must choose a die'),
        "type" => "multipleactiveplayer",
        "action" => "stOtherPlayersTurn",
        "possibleactions" => array("play", "pass"),
        "transitions" => array("allPlayed" => END_TURN)
    ),

    ALICAT_TURN => array(
        "name" => "otherPlayersTurn",
        "description" => clienttranslate('Other players must choose a die'),
        "descriptionmyturn" => clienttranslate('${you} must choose a die'),
        "type" => "game",
        "transitions" => array("play1" => OTHER_PLAYERS_TURN, "play2" => END_TURN)
    ),

    //state after a turn, decides to launch a new turn or end a game
    END_TURN => array(
        "name" => "endTurn",
        "description" => '',
        "type" => "game",
        "action" => "stEndTurn",
        "updateGameProgression" => true,
        "transitions" => array("endGame" => STATE_END_GAME, "newTurn" => THROW_DICE)
    ),

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    STATE_END_GAME => array(
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    )

);
