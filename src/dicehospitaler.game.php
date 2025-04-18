<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * DiceHospitalER implementation : © <firgon> <emmanuel.albisser@gmail.com>
 * 
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * dicehospitaler.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');


class DiceHospitalER extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            "R" => 10,
            "G" => 11,
            "Y" => 12,
            "epidemiologist" => 13,
            "cardiologist_1" => 14,
            "cardiologist_2" => 15,
            "radiologist" => 16,
            "used_die" => 17

            //      ...
            //    "my_first_game_variant" => 100,
            //    "my_second_game_variant" => 101,
            //      ...
        ));


        $this->cards = self::getNew("module.common.deck");
        $this->cards->init("card");
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "dicehospitaler";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";

            for ($i = 0; $i < 7; $i++) {
                $requete = "INSERT INTO `wards` (`ward_id`, `player_id` ) VALUES ('$i', '$player_id')";
                self::DbQuery($requete);
            }
        }
        $sql .= implode(',', $values);
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue('R', bga_rand(1, 6));
        self::setGameStateInitialValue('G', bga_rand(1, 6));
        self::setGameStateInitialValue('Y', bga_rand(1, 6));
        // little hack since i fix the epidemiologist 2 card
        // $epid_card = bga_rand(0, 4);
        // while ($epid_card == 2) {
        //     $epid_card = bga_rand(0, 4);
        // }
        self::setGameStateInitialValue('epidemiologist', bga_rand(0, 4));
        self::setGameStateInitialValue('radiologist', bga_rand(5, 9));
        self::setGameStateInitialValue('used_die', -1);

        if (count($players) == 2) {
            $first_cardiologist = bga_rand(16, 21);
            $second_cardiologist = $first_cardiologist;
            while ($second_cardiologist == $first_cardiologist) {
                $second_cardiologist = bga_rand(16, 21);
            }
        } else {
            $first_cardiologist = bga_rand(10, 15);
            $second_cardiologist = $first_cardiologist;
            while ($second_cardiologist == $first_cardiologist) {
                $second_cardiologist = bga_rand(10, 15);
            }
        }


        self::setGameStateInitialValue('cardiologist_1', $first_cardiologist);
        self::setGameStateInitialValue('cardiologist_2', $second_cardiologist);

        // Init game statistics
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        self::initStat('player', 'ward_score', 0);  // Init a player statistics (for all players)
        self::initStat('player', 'critical_score', 0);
        self::initStat('player', 'nurses_score', 0);
        self::initStat('player', 'cardiologist_score', 0);
        self::initStat('player', 'radiologist_score', 0);
        self::initStat('player', 'epidemiologist_score', 0);
        self::initStat('player', 'deads_count', 0);

        // TODO: setup the initial game situation here

        //create cards
        $cards = array();

        for ($i = 0; $i < count($this->ambulance_infos); $i++) {
            $cards[] = array('type' => $this->ambulance_infos[$i]['type'], 'type_arg' => $i, 'nbr' => 1);
        }

        //add cards in deck depending of their type
        $this->cards->createCards($cards, "deck");
        $this->cards->shuffle("deck");

        $cards_aside = 3;
        if (count($players) == 5) $cards_aside = 2;
        $this->cards->pickCardsForLocation($cards_aside, 'deck', 'trash');




        /************ End of the game initialization *****/
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        try {
            //for compatibility purpose
            $sql = "SELECT player_id id, player_name name, player_score score, nb_stetos, used_stetos, nb_bloods, used_bloods, nb_nurses, nb_deads, score_wards, score_critical, score_nurses, score_cardiologist_1, score_cardiologist_2, score_radiologist, score_epidemiologist, score_dead, player_score score_total, epidemiologist_rooms FROM player ";
            $result['players'] = self::getCollectionFromDb($sql);
        } catch (Exception $e) {
            $sql = "SELECT player_id id, player_name name, player_score score, nb_stetos, used_stetos, nb_bloods, used_bloods, nb_nurses, nb_deads, score_wards, score_critical, score_nurses, score_cardiologist_1, score_cardiologist_2, score_radiologist, score_epidemiologist, score_dead, player_score score_total FROM player ";
            $result['players'] = self::getCollectionFromDb($sql);
        }


        // TODO: Gather all information about current game situation (visible by player $current_player_id).
        $sql = "SELECT player_id, room_id, value, decoration FROM rooms ";
        $result['rooms'] = self::getDoubleKeyCollectionFromDB($sql);

        $sql = "SELECT player_id, ward_id, VP, nurse FROM wards ";
        $result['wards'] = self::getDoubleKeyCollectionFromDB($sql);

        $result['counters'] = $this->counters;

        $result['sheet'] = $this->sheet;
        $result['specialist_infos'] = $this->specialist_infos;
        $result['ambulance_infos'] = $this->ambulance_infos;
        $result['help'] = $this->help;
        $result['powers'] = $this->powers;

        $result['red_die'] = self::getGameStateValue('R');
        $result['green_die'] = self::getGameStateValue('G');
        $result['yellow_die'] = self::getGameStateValue('Y');

        $result['used_die'] = self::getGameStateValue('used_die'); //envoie 0,1 ou 2

        $active_card = $this->cards->getCardOnTop('table');
        $result['active_card'] = ($active_card != null) ? $active_card['type_arg'] : -1;
        $result['cards_count'] = $this->cards->countCardInLocation('deck'); //count 1 card on 'table'

        $result['epidemiologist'] = self::getGameStateValue('epidemiologist');
        $result['radiologist'] = self::getGameStateValue('radiologist');
        $result['cardiologist_1'] = self::getGameStateValue('cardiologist_1');
        $result['cardiologist_2'] = self::getGameStateValue('cardiologist_2');

        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        return floor((24 - $this->cards->countCardInLocation('deck')) * 100 / 24);
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getAllRoomX()
    {
        //change it if sheets change
        return [102, 405];
    }

    function getAllRawAdjacentHexs($room_id)
    {
        // returns all hexs number around a room (without verifying if number room really exists)
        if ($this->isEvenRow($room_id)) {
            $possible_adjacents = [$room_id - 100, $room_id - 99, $room_id + 1, $room_id + 101, $room_id + 100, $room_id - 1];
        } else {
            $possible_adjacents = [$room_id - 101, $room_id - 100, $room_id + 1, $room_id + 100, $room_id + 99, $room_id - 1];
        }
        return $possible_adjacents;
    }

    function getAllRealAdjacentHexs($room_id)
    {
        // returns all adjacents hex around a room verifying if number room are valid
        $possible_adjacents = $this->getAllRawAdjacentHexs($room_id);

        $retour = array();

        foreach ($possible_adjacents as $key => $room) {
            if ($this->isValidID($room))
                $retour[] = $room;
        }

        return $retour;
    }

    //return True if row is Even, False if row is Odd
    function isEvenRow($room_id)
    {

        $row = floor($room_id / 100);

        return ($row % 2 == 0);
    }

    function isValidID($room_id)
    {
        return array_key_exists($room_id, $this->sheet['hexs']);
    }

    function getAll($sets)
    {
        // function to get all elements in all sets
        $result = [];
        foreach ($sets as $set) {
            foreach ($set as $room) {
                $result[] = $room;
            }
        }
        return array_values($result);
    }

    function calculateWardScore($player_id, $room_id, $ward_id)
    {
        //calcule les points de bases
        $info_ward = $this->sheet['wards'][$ward_id];
        $nb_rooms = count($info_ward['hexs']);
        $last_room_of_ward = $info_ward['hexs'][$nb_rooms - 1];
        if ($room_id == $last_room_of_ward) {
            $bonus = $info_ward['VP'];

            $sql = "UPDATE player SET score_wards = score_wards + $bonus, player_score = player_score + $bonus WHERE player_id=$player_id";
            self::DbQuery($sql);

            $sql = "UPDATE wards SET VP = $bonus WHERE player_id=$player_id AND ward_id = $ward_id";
            self::DbQuery($sql);

            // Notify all players
            self::notifyAllPlayers("scoreWards", clienttranslate('${player_name} fills wards n°${num_ward} and wins ${bonus} VP'), array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'ward_id' => $ward_id,
                'num_ward' => $ward_id + 1,
                'bonus' => $bonus
            ));
        }
    }

    function calculateNursesScore($player_id, $ward_id)
    {
        $sql = "SELECT nb_nurses FROM player WHERE player_id=$player_id";
        $nb_nurses = self::getUniqueValueFromDB($sql);

        $score_nurses = $this->nursesVP[$nb_nurses];
        $sql = "UPDATE player SET score_nurses = $score_nurses, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
        self::DbQuery($sql);

        // Notify all players
        self::notifyAllPlayers("scoreNurses", clienttranslate('${player_name} recruits a nurse on ward n°${num_ward} and increases his nurses bonus to ${new_score} VP'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'new_score' => $score_nurses,
            'ward_id' => $ward_id,
            'num_ward' => $ward_id + 1,
        ));
    }

    function calculateCriticalScore($player_id, $value)
    {
        $sql = "UPDATE player SET score_critical = score_critical + $value, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
        self::DbQuery($sql);
        // Notify all players
        self::notifyAllPlayers("scoreCritical", clienttranslate('${player_name} accepts a critical patient and wins ${value} VP'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'value' => $value
        ));
    }

    //recursive function to find 2 then 3 then 4 linked... storing then in $sets
    function searchForSuit(&$sets, $buildingSet, $roomsDB)
    {
        $rooms_selection = $this->getAllRealAdjacentHexs(end($buildingSet));
        $next_value = count($buildingSet) + 1;

        if ($next_value > 6) {
            $sets[] = $buildingSet;
            return;
        }

        $possible_next_rooms = $this->getAllRooms($next_value, $roomsDB, $rooms_selection);

        if (empty($possible_next_rooms)) return;
        else {
            foreach ($possible_next_rooms as $room) {
                $new_building_set = $buildingSet;
                $new_building_set[] = $room;
                $this->searchForSuit($sets, $new_building_set, $roomsDB);
            }
        }
    }

    // recursive function to find the most important set of same value room
    function searchForSame($buildingSet, $roomsDB, &$already_used_rooms, $room_id, $value)
    {

        //select adjacents hex not already used
        $adjacents = array_diff($this->getAllRealAdjacentHexs($room_id), $already_used_rooms);
        $adjacents = array_intersect($adjacents, array_keys($roomsDB));

        //keep only matching adjacent (with same value)
        $matching_adjacents = $this->getAllRooms($value, $roomsDB, $adjacents);

        //if empty stop here
        if (empty($matching_adjacents)) {
            return array_unique($buildingSet);
        }

        //add matching adjacent to buildingset
        $buildingSet = array_merge($buildingSet, $matching_adjacents);

        //considere them as already used
        $already_used_rooms = array_merge($already_used_rooms, $matching_adjacents);

        foreach ($matching_adjacents as $new_room_id) {
            $buildingSet = $this->searchForSame($buildingSet, $roomsDB, $already_used_rooms, $new_room_id, $value);
        }

        return array_unique($buildingSet);
    }

    //get all room with value in rooms (id=>value) must be in rooms selection if this is not null
    function getAllRooms($searched_value, $rooms, $rooms_selection = null)
    {
        $rooms_for_search = $rooms_selection ?? array_keys($rooms);

        $filtered = array_filter($rooms, fn($value, $key) => in_array($key, $rooms_for_search) && $value == $searched_value, ARRAY_FILTER_USE_BOTH);
        return array_keys($filtered);
    }

    //take a table of sets of rooms in parameters and return a table of sets of rooms without dupplicate sets
    function removeDupplicate($sets)
    {

        $counts = array();

        foreach ($sets as $set) {
            foreach ($set as $room) {
                if (key_exists($room, $counts)) {
                    $counts[$room] += 1;
                } else {
                    $counts[$room] = 1;
                }
            }
        }

        if (max($counts) <= 1) return $sets;
        else {
            $set_to_erase = [];
            $max_score = 0;
            foreach ($sets as $set) {
                $score_set = 0;
                foreach ($set as $room) {
                    $score_set += $counts[$room];
                }
                if ($score_set > $max_score) {
                    $max_score = $score_set;
                    $set_to_erase = $set;
                }
            }
            foreach ($sets as $index => $set) {
                if ($set == $set_to_erase) {
                    unset($sets[$index]);
                    break;
                }
            }
            return $this->removeDupplicate($sets);
        }
    }

    function scoreEpidemiologist($player_id, $points, $good_rooms)
    {
        $json = json_encode($good_rooms);
        try {
            //for compatibility purpose
            $sql = "UPDATE player SET epidemiologist_rooms = '$json', score_epidemiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
            self::DbQuery($sql);
        } catch (\Throwable $th) {
            $sql = "UPDATE player SET score_epidemiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
            self::DbQuery($sql);
        }
    }

    function calculateEpidemiologistScore($player_id)
    {
        $card_id = self::getGameStateValue('epidemiologist');

        $good_rooms = [];

        switch ($card_id) {
            case '0':
                //from 1 to 6 connected numbers
                $sql = "SELECT room_id, value FROM rooms WHERE player_id=$player_id";
                $roomsDB = self::getCollectionFromDB($sql, true);

                $sets = array();

                $first_rooms = $this->getAllRooms(1, $roomsDB);

                foreach ($first_rooms as $room) {
                    $buildingSet = [$room];
                    $this->searchForSuit($sets, $buildingSet, $roomsDB);
                }

                //remove invalids sets (because rooms are used twice or more)
                if (count($sets) > 1) $sets = $this->removeDupplicate($sets);

                $points = count($sets) * $this->specialist_infos[$card_id]['VP'];
                $good_rooms = $this->getAll($sets);

                $sql = "SELECT score_epidemiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);

                if ($points != $previous_points) {

                    $this->scoreEpidemiologist($player_id, $points, $good_rooms);

                    // Notify all players
                    $value = $points - $previous_points;

                    self::notifyAllPlayers("scoreEpidemiologist", clienttranslate('${player_name} wins ${value} VP more with Epidemiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $value,
                        'epidemiologist_rooms' => json_encode($good_rooms),
                    ));
                }

                break;

            case '1':
                //line of 3 equal number
                $sql = "SELECT room_id, value FROM rooms WHERE player_id=$player_id";
                $roomsDB = self::getCollectionFromDB($sql, true);

                $sets = array();

                $possible_adjacents = [];
                $possible_adjacents[0] = [1, 101, 100];
                $possible_adjacents[1] = [1, 100, 99];

                foreach ($roomsDB as $room_id => $value) {
                    $row = floor($room_id / 100);
                    for ($i = 0; $i < 3; $i++) {
                        $room_id2 = $room_id + $possible_adjacents[$row % 2][$i];
                        $row2 = floor($room_id2 / 100);
                        $room_id3 = $room_id2 + $possible_adjacents[$row2 % 2][$i];
                        $value2 = $roomsDB[$room_id2] ?? 0;
                        $value3 = $roomsDB[$room_id3] ?? 0;
                        if ($value == $value2 && $value == $value3 && $value != 0) {
                            $sets[] = [$room_id, $room_id2, $room_id3];
                        }
                    }
                }

                //TODO remove invalids sets (because rooms are used twice or more)
                if (count($sets) > 1) $sets = $this->removeDupplicate($sets);

                $points = count($sets) * $this->specialist_infos[$card_id]['VP'];
                $good_rooms = $this->getAll($sets);

                $sql = "SELECT score_epidemiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);

                if ($points != $previous_points) {

                    $this->scoreEpidemiologist($player_id, $points, $good_rooms);

                    // Notify all players
                    $value = $points - $previous_points;

                    self::notifyAllPlayers("scoreEpidemiologist", clienttranslate('${player_name} wins ${value} VP more with Epidemiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $value,
                        'epidemiologist_rooms' => json_encode($good_rooms),
                    ));
                }

                break;

            case '2':
                // score with max connected same value
                $sql = "SELECT room_id, value FROM rooms WHERE player_id=$player_id";
                $roomsDB = self::getCollectionFromDB($sql, true);

                $already_used_rooms = array();

                $sets = array();

                foreach ($roomsDB as $room_id => $value) {
                    if (in_array($room_id, $already_used_rooms)) continue;
                    else {
                        $already_used_rooms[] = $room_id;
                        $new_set = $this->searchForSame([$room_id], $roomsDB, $already_used_rooms, $room_id, $value);
                        $sets[] = $new_set;
                    }
                }

                $maxSet = 0;

                foreach ($sets as $set) {
                    $maxSet = max($maxSet, count($set));
                    if (count($set) == $maxSet) $good_rooms = array_values($set);
                }

                $points = $maxSet * $this->specialist_infos[$card_id]['VP'];

                $sql = "SELECT score_epidemiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);

                if ($points != $previous_points) {
                    $this->scoreEpidemiologist($player_id, $points, $good_rooms);

                    // Notify all players
                    $value = $points - $previous_points;

                    self::notifyAllPlayers("scoreEpidemiologist", clienttranslate('${player_name} wins ${value} VP more with Epidemiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $value,
                        'epidemiologist_rooms' => json_encode($good_rooms),
                    ));
                }

                break;

            case '3':
                //max number - min number
                $sql = "SELECT value, count(room_id) count FROM rooms WHERE player_id=$player_id GROUP BY value";
                $infos = self::getCollectionFromDB($sql, true);

                $max = 0;
                $min = 50;
                $best_number = 0;
                for ($i = 1; $i <= 6; $i++) {
                    if (array_key_exists($i, $infos)) {
                        $max = max($max, $infos[$i]);
                        if ($max == $infos[$i]) $best_number = $i;
                        $min = min($min, $infos[$i]);
                    } else $min = 0;
                }
                $score = $max - $min;

                $sql = "SELECT room_id, value FROM rooms WHERE player_id=$player_id";
                $roomsDB = self::getCollectionFromDB($sql, true);

                $good_rooms = $this->getAllRooms($best_number, $roomsDB);

                $points = $score * $this->specialist_infos[$card_id]['VP'];

                $sql = "SELECT score_epidemiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);

                if ($points != $previous_points) {

                    $this->scoreEpidemiologist($player_id, $points, $good_rooms);

                    // Notify all players
                    $value = $points - $previous_points;
                    if ($value > 0) {
                        $message = clienttranslate('${player_name} wins ${value} VP more with Epidemiologist');
                    }
                    else {
                        $message = clienttranslate('${player_name} loses ${value} VP with Epidemiologist');
                    }
                    
                    self::notifyAllPlayers("scoreEpidemiologist", $message, array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $value,
                        'epidemiologist_rooms' => json_encode($good_rooms),
                    ));
                }
                break;


            case '4':
                //triange with even or odd numbers
                $sql = "SELECT room_id, value FROM rooms WHERE player_id=$player_id";
                $roomsDB = self::getCollectionFromDB($sql, true);

                $sets = array();

                $possible_adjacents = [];
                $possible_adjacents[0] = [1, 101, 100];
                $possible_adjacents[1] = [1, 100, 99];

                foreach ($roomsDB as $room_id => $value) {

                    $row = floor($room_id / 100);

                    $room_id2 = $room_id + $possible_adjacents[$row % 2][0];
                    $room_id3 = $room_id + $possible_adjacents[$row % 2][1];
                    $room_id4 = $room_id + $possible_adjacents[$row % 2][2];
                    $value2 = $roomsDB[$room_id2] ?? 0;
                    $value3 = $roomsDB[$room_id3] ?? 0;
                    $value4 = $roomsDB[$room_id4] ?? 0;
                    // echo ($room_id." ".$room_id2." ".$room_id3."    ");
                    if ($value % 2 == $value2 % 2 && $value % 2 == $value3 % 2 && $value2 != 0 && $value3 != 0) {
                        $sets[] = [$room_id, $room_id2, $room_id3];
                    }
                    if ($value % 2 == $value3 % 2 && $value % 2 == $value4 % 2 && $value3 != 0 && $value4 != 0) {
                        $sets[] = [$room_id, $room_id3, $room_id4];
                    }
                }


                //remove invalids sets (because rooms are used twice or more)
                if (count($sets) > 1) $sets = $this->removeDupplicate($sets);

                $points = count($sets) * $this->specialist_infos[$card_id]['VP'];
                $good_rooms = $this->getAll($sets);

                $sql = "SELECT score_epidemiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);

                if ($points != $previous_points) {

                    $this->scoreEpidemiologist($player_id, $points, $good_rooms);

                    // Notify all players
                    $value = $points - $previous_points;

                    self::notifyAllPlayers("scoreEpidemiologist", clienttranslate('${player_name} wins ${value} VP more with Epidemiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $value,
                        'epidemiologist_rooms' => json_encode($good_rooms),
                    ));
                }

                break;
        }
    }

    function calculateCardiologistScore($i)
    {
        $cardiologist_id = "cardiologist_$i";
        $card_id = self::getGameStateValue($cardiologist_id);
        if ($card_id < 0) return;

        $new_card_id = $this->specialist_infos[$card_id]['back'];
        $is_completed = false;

        switch ($card_id) {
            case '10':
            case '16':
                //gain 8 stetos
                $sql = "SELECT player_id, player_name, nb_stetos, score_$cardiologist_id FROM player";
                $infos = self::getCollectionFromDb($sql);


                foreach ($infos as $player_id => $info) {
                    if ($info['nb_stetos'] > 7 && $info["score_$cardiologist_id"] == 0) {
                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_cardiologist_$i = score_cardiologist_$i + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $info['player_name'],
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }
                break;
            case '11':
            case '17':
                //fill all rooms around a X room
                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRealAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRealAdjacentHexs($x_rooms[1]);

                $sql = "SELECT player_id, room_id, value FROM rooms where room_id IN (" . implode(',', $ids_1) . ")";
                $sql2 = "SELECT player_id, room_id, value FROM rooms where room_id IN (" . implode(',', $ids_2) . ")";
                $infos = self::getDoubleKeyCollectionFromDB($sql, true);
                $infos2 = self::getDoubleKeyCollectionFromDB($sql2, true);

                foreach ($infos as $player_id => $rooms) {

                    if (count($rooms) == 6) {
                        $sql = "SELECT score_$cardiologist_id FROM player WHERE player_id=$player_id";
                        $score = self::getUniqueValueFromDB($sql);
                        if ($score != 0) continue;

                        $sql = "SELECT player_name FROM player WHERE player_id=$player_id";
                        $player_name = self::getUniqueValueFromDB($sql);

                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_$cardiologist_id = score_$cardiologist_id + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $player_name,
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }
                foreach ($infos2 as $player_id => $rooms) {

                    if (count($rooms) == 6) {
                        $sql = "SELECT score_$cardiologist_id FROM player WHERE player_id=$player_id";
                        $score = self::getUniqueValueFromDB($sql);
                        if ($score != 0) continue;

                        $sql = "SELECT player_name FROM player WHERE player_id=$player_id";
                        $player_name = self::getUniqueValueFromDB($sql);

                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_$cardiologist_id = score_$cardiologist_id + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $player_name,
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }


                break;
            case '12':
            case '18':
                //gain 3 nurse
                $sql = "SELECT player_id, player_name name, nb_nurses, score_$cardiologist_id FROM player";
                $infos = self::getCollectionFromDb($sql);


                foreach ($infos as $player_id => $info) {
                    if ($info['nb_nurses'] >= 3 && $info["score_$cardiologist_id"] == 0) {
                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_cardiologist_$i = score_cardiologist_$i + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $info['name'],
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }
                break;

            case '13':
            case '19':
                //gain 4 bloods
                $sql = "SELECT player_id, player_name name, nb_bloods, score_$cardiologist_id FROM player";
                $infos = self::getCollectionFromDb($sql);


                foreach ($infos as $player_id => $info) {
                    if ($info['nb_bloods'] >= 4 && $info["score_$cardiologist_id"] == 0) {
                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_cardiologist_$i = score_cardiologist_$i + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $info['name'],
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }
                break;

            case '14':
            case '20':
                //collected first rooms id in each wards
                $ids = array();
                for ($index = 0; $index < 7; $index++) {
                    $ids[] = $this->sheet['wards'][$index]['hexs'][0];
                }

                $sql = "SELECT player_id, room_id, value FROM rooms where room_id IN (" . implode(',', $ids) . ")";
                $infos = self::getDoubleKeyCollectionFromDB($sql, true);

                foreach ($infos as $player_id => $rooms) {

                    if (count($rooms) == 7) {
                        $sql = "SELECT score_$cardiologist_id FROM player WHERE player_id=$player_id";
                        $score = self::getUniqueValueFromDB($sql);
                        if ($score != 0) continue;

                        $sql = "SELECT player_name FROM player WHERE player_id=$player_id";
                        $player_name = self::getUniqueValueFromDB($sql);

                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_$cardiologist_id = score_$cardiologist_id + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $player_name,
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }

                break;

            case '15':
            case '21':
                $sql = "SELECT player_id, COUNT(ward_id) count FROM wards WHERE NOT VP=0 GROUP BY player_id";
                $infos = self::getCollectionFromDb($sql, true);


                foreach ($infos as $player_id => $count) {
                    if ($count >= 2) {
                        $sql = "SELECT score_$cardiologist_id FROM player WHERE player_id=$player_id";
                        $score = self::getUniqueValueFromDB($sql);
                        if ($score != 0) continue;

                        $sql = "SELECT player_name FROM player WHERE player_id=$player_id";
                        $player_name = self::getUniqueValueFromDB($sql);

                        $gain = $this->specialist_infos[$card_id]['VP'];
                        $sql = "UPDATE player SET score_cardiologist_$i = score_cardiologist_$i + $gain, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                        self::DbQuery($sql);
                        $is_completed = true;
                        // Notify all players
                        self::notifyAllPlayers("scoreCardiologist", clienttranslate('${player_name} completes a cardiologist objective and wins ${value} VP'), array(
                            'player_id' => $player_id,
                            'player_name' => $player_name,
                            'value' => $gain,
                            'cardiologist_id' => $cardiologist_id,
                            'new_card' => $new_card_id
                        ));
                    }
                }
                break;

                break;
        }
        if ($is_completed) {
            self::setGameStateValue($cardiologist_id, $new_card_id);
        }
    }
    function calculateRadiologistScore($player_id)
    {
        $card_id = self::getGameStateValue('radiologist');
        $good_rooms = [];

        switch ($card_id) {
            case '5':
                //score half critical points around X
                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRealAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRealAdjacentHexs($x_rooms[1]);

                $ids = array_merge($ids_1, $ids_2);

                $sql = "SELECT SUM(value) sum FROM rooms where room_id IN (" . implode(',', $ids) . ") AND decoration LIKE '%critical%' AND player_id = $player_id GROUP BY player_id";
                $score = self::getUniqueValueFromDB($sql);

                $points = ceil($score * $this->specialist_infos[$card_id]['VP']);

                $sql = "SELECT score_radiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);
                if ($points != $previous_points) {

                    $sql = "UPDATE player SET score_radiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                    self::DbQuery($sql);
                    // Notify all players
                    self::notifyAllPlayers("scoreRadiologist", clienttranslate('${player_name} wins ${value} VP more with Radiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $points - $previous_points
                    ));
                }

                break;

            case '6':
                //max 12 or min 30
                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRealAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRealAdjacentHexs($x_rooms[1]);

                $sql = "SELECT room_id, value FROM rooms where room_id IN (" . implode(',', $ids_1) . ") AND player_id=$player_id";
                $sql2 = "SELECT room_id, value FROM rooms where room_id IN (" . implode(',', $ids_2) . ") AND player_id=$player_id";
                $rooms = array();
                $rooms[0] = self::getCollectionFromDB($sql, true);
                $rooms[1] = self::getCollectionFromDB($sql2, true);

                $nb_corrects = 0;

                foreach ($rooms as $key => $room) {
                    if (count($room) == 6) {
                        $total = 0;
                        foreach ($room as $room_id => $value) {
                            $total += $value;
                        }
                        if ($total <= 12 || $total >= 30) {
                            $nb_corrects += 1;
                        }
                    }
                }

                $points = $nb_corrects * $this->specialist_infos[$card_id]['VP'];

                $sql = "SELECT score_radiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);
                if ($points != $previous_points) {

                    $sql = "UPDATE player SET score_radiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                    self::DbQuery($sql);
                    // Notify all players
                    self::notifyAllPlayers("scoreRadiologist", clienttranslate('${player_name} wins ${value} VP more with Radiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $points - $previous_points
                    ));
                }

                break;

            case '7':
                // score with 4more values near X room 

                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRealAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRealAdjacentHexs($x_rooms[1]);

                $ids = array_merge($ids_1, $ids_2);

                $sql = "SELECT count(room_id) sum FROM rooms where room_id IN (" . implode(',', $ids) . ") AND value > 3 AND player_id=$player_id";
                $nb_corrects = self::getUniqueValueFromDB($sql);

                $points = $nb_corrects * $this->specialist_infos[$card_id]['VP'];

                $sql = "SELECT score_radiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);
                if ($points != $previous_points) {

                    $sql = "UPDATE player SET score_radiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                    self::DbQuery($sql);
                    // Notify all players
                    self::notifyAllPlayers("scoreRadiologist", clienttranslate('${player_name} wins ${value} VP more with Radiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $points - $previous_points
                    ));
                }

                break;
            case '8':
                //equality both side X room
                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRawAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRawAdjacentHexs($x_rooms[1]);

                $sql = "SELECT room_id, value FROM rooms where room_id IN (" . implode(',', $ids_1) . ") AND player_id=$player_id";
                $rooms = self::getCollectionFromDB($sql, true);
                $sql = "SELECT room_id, value FROM rooms where room_id IN (" . implode(',', $ids_2) . ") AND player_id=$player_id";
                $rooms2 = self::getCollectionFromDB($sql, true);

                $nb_corrects = 0;

                for ($i = 0; $i < 3; $i++) {
                    $room_id1 = $ids_1[$i];
                    $room_id2 = $ids_1[$i + 3];
                    if (!array_key_exists($room_id1, $rooms) || !array_key_exists($room_id2, $rooms)) continue;
                    $value1 = $rooms[$room_id1];
                    $value2 = $rooms[$room_id2];
                    if ($value1 == $value2) {
                        $nb_corrects += 1;
                    }
                }

                for ($i = 0; $i < 3; $i++) {
                    $room_id1 = $ids_2[$i];
                    $room_id2 = $ids_2[$i + 3];
                    if (!array_key_exists($room_id1, $rooms2) || !array_key_exists($room_id2, $rooms2)) continue;
                    $value1 = $rooms2[$room_id1];
                    $value2 = $rooms2[$room_id2];
                    if ($value1 == $value2) {
                        $nb_corrects += 1;
                    }
                }

                $points = $nb_corrects * $this->specialist_infos[$card_id]['VP'];

                $sql = "SELECT score_radiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);
                if ($points != $previous_points) {

                    $sql = "UPDATE player SET score_radiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                    self::DbQuery($sql);
                    // Notify all players
                    self::notifyAllPlayers("scoreRadiologist", clienttranslate('${player_name} wins ${value} VP more with Radiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $points - $previous_points
                    ));
                }

                break;
            case '9':
                //points with max same value around X room
                $x_rooms = $this->getAllRoomX();

                $ids_1 = $this->getAllRealAdjacentHexs($x_rooms[0]);
                $ids_2 = $this->getAllRealAdjacentHexs($x_rooms[1]);

                $sql = "SELECT room_id, value FROM rooms where room_id IN (" . implode(',', $ids_1) . ") AND player_id=$player_id";
                $rooms = self::getCollectionFromDB($sql, true);
                $sql = "SELECT  room_id, value FROM rooms where room_id IN (" . implode(',', $ids_2) . ") AND player_id=$player_id";
                $rooms2 = self::getCollectionFromDB($sql, true);

                $points = 0;
                $nb_corrects = array(
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0
                );

                foreach ($rooms as $room => $value) {
                    $nb_corrects[$value] += 1;
                }

                foreach ($nb_corrects as $value => $num) {
                    if ($num >= 3) {
                        $points += $this->specialist_infos[$card_id]['VP'][$num];
                        break;
                    }
                }

                $nb_corrects = array(
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    6 => 0
                );

                foreach ($rooms2 as $room => $value) {
                    $nb_corrects[$value] += 1;
                }

                foreach ($nb_corrects as $value => $num) {
                    if ($num >= 3) {
                        $points += $this->specialist_infos[$card_id]['VP'][$num];
                        break;
                    }
                }


                $sql = "SELECT score_radiologist FROM player WHERE player_id=$player_id";
                $previous_points = self::getUniqueValueFromDB($sql);
                if ($points != $previous_points) {

                    $sql = "UPDATE player SET score_radiologist = $points, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
                    self::DbQuery($sql);
                    // Notify all players
                    self::notifyAllPlayers("scoreRadiologist", clienttranslate('${player_name} wins ${value} VP more with Radiologist'), array(
                        'player_id' => $player_id,
                        'player_name' => self::getCurrentPlayerName(),
                        'value' => $points - $previous_points
                    ));
                }
                break;
        }
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in dicehospitaler.action.php)
    */

    function screen($die, $room_id)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('play');

        $player_id = $this->getCurrentPlayerId();

        $card = $this->cards->getCardOnTop('table');
        $real_bonus = $this->ambulance_infos[$card['type_arg']]['value'][$this->powers[$die]];

        //check that a screen was playable
        if ($real_bonus != SCREEN) throw new BgaUserException(self::_("You can't play a screen with that die"));

        $sql = "UPDATE rooms SET decoration = CONCAT('screen ', decoration) WHERE player_id=$player_id AND room_id=$room_id";
        self::DbQuery($sql);

        // Notify all players
        self::notifyAllPlayers("newScreen", clienttranslate('${player_name} places a new screen'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'room_id' => $room_id
        ));
    }

    function pass($die)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('pass');

        $player_id = $this->getCurrentPlayerId();

        $sql = "SELECT score_dead FROM player WHERE player_id=$player_id";
        $score = self::getUniqueValueFromDB($sql);
        $loss = 0;
        $card = $this->cards->getCardOnTop('table');

        //calculate bonus even if patient is dead
        $real_bonus = $this->ambulance_infos[$card['type_arg']]['value'][$this->powers[$die]];
        $virtual_stetos = ($real_bonus == STETOS) ? 2 : 0;
        $virtual_bloods = ($real_bonus == BLOODS) ? 1 : 0;

        if ($real_bonus == STETOS) {
            // Notify all players
            self::notifyAllPlayers("moreStetos", clienttranslate('${player_name} receives two new stetoscopes '), array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'new_stetos' => 2
            ));
        } else if ($real_bonus == BLOODS) {
            // Notify all players
            self::notifyAllPlayers("moreBlood", clienttranslate('${player_name} receives a new blood bag'), array(
                'player_id' => $player_id,
                'player_name' => self::getCurrentPlayerName(),
                'new_bloods' => 1
            ));
        }

        if ($score > -4) {
            $score = $score - 1;
            $loss = 1;
        }
        $nb_dead = -$score;
        $sql = "UPDATE player SET nb_stetos = nb_stetos + $virtual_stetos, nb_bloods=nb_bloods+$virtual_bloods, nb_deads = $nb_dead, score_dead = $score, player_score = score_wards+ score_critical+score_nurses + score_cardiologist_1 + score_cardiologist_2 + score_radiologist + score_epidemiologist + score_dead WHERE player_id=$player_id";
        self::DbQuery($sql);


        if ($this->gamestate->state_id() == PLAYER_TURN) {
            self::setGameStateValue('used_die', $this->powers[$die]);
        }

        // Notify all players
        self::notifyAllPlayers("playerPassed", clienttranslate('${player_name} puts a patient ${die_color} into the morgue and loses ${value} VP'), array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'value' => $loss,
            'die_color' => $this->colors[$die],
            'used_die' => self::getGameStateValue('used_die')
        ));


        if ($this->gamestate->state_id() == PLAYER_TURN) {
            //TODO prévoir ALICAT GAME
            $this->gamestate->nextState('play');
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, "allPlayed");
        }
    }

    /**
     * $die = Y R G
     * room_id 
     * value
     * decoration ' Y 1 Yellow_die' (first move description) or 'pass normal die"/ "pass extra die"
     * room id2
     * extradie source Y R G
     */

    //play.html?die=Y&room_id=506&value=3&decoration=Y%201%20yellow_die&room_id2=505&extra_die_source=Y _>pb
    //play.html?die=Y&room_id=505&value=1&decoration=Y%203%20extra_die&room_id2=203&extra_die_source=Y ->ok
    function playTwice($die, $room_id, $value, $decoration, $room_id2, $extra_die_source)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('play');

        //first check if one die have been passed
        if ($decoration == "pass_normal_die" || $decoration == "pass_extra_die") {
            if ($decoration == "pass_normal_die") {
                $this->play($die, $room_id, $value, $extra_die_source, false);
                $this->pass($extra_die_source);
            } else {
                $this->play($die, $room_id, $value, '', false);
                $this->pass($extra_die_source);
            }
        } else {

            $first_move = explode(" ", $decoration);
            $first_move_die = $first_move[0];
            $first_move_value = $first_move[1];
            $first_move_source = $first_move[2];

            if ($first_move_source == 'extra_die') {
                //if you played with extra-die
                $this->play($first_move_die, $room_id2, $first_move_value, $extra_die_source, false);
                $this->play($die, $room_id, $value, '', true);
            } else {
                $this->play($first_move_die, $room_id2, $first_move_value, '', false);
                $this->play($die, $room_id, $value, $extra_die_source, true);
            }
        }
    }

    function play($die, $room_id, $value, $decoration, $bEndOfTurn)
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction('play');

        $player_id = $this->getCurrentPlayerId();

        $card = $this->cards->getCardOnTop('table');

        //check if decoration is not an extra die -> there is a bonus
        if ($decoration != 'R' && $decoration != 'Y' && $decoration != 'G') {
            //verifie que l'action est possible
            $initial_value = self::getGameStateValue($die);
            $real_bonus = $this->ambulance_infos[$card['type_arg']]['value'][$this->powers[$die]];
            $virtual_stetos = ($real_bonus == STETOS) ? 2 : 0;
            $virtual_bloods = ($real_bonus == BLOODS) ? 1 : 0;
            $virtual_nurses = ($real_bonus == NURSE) ? 1 : 0;
            $used_die = $die;
        } else {
            $real_bonus = $this->ambulance_infos[$card['type_arg']]['value'][$this->powers[$decoration]];

            // check if extra dice is correct
            if (strpos($real_bonus, 'patient') !== 0) throw new BgaUserException(self::_("It seems you can't play twice"));

            $bonus_infos = explode(" ", $real_bonus);
            $initial_value = $bonus_infos[2];
            if ($value > $initial_value) {
                $initial_value = $bonus_infos[3];
            }
            $virtual_stetos = 0;
            $virtual_bloods = 0;
            $virtual_nurses = 0;
            if ($bEndOfTurn) {
                $used_die = $decoration;
            }
        }


        $sql = "SELECT nb_stetos, used_stetos, nb_bloods, used_bloods, nb_nurses FROM player WHERE player_id=$player_id";
        $infos = self::getObjectFromDb($sql);

        //if die value has been too much modified
        $virtual_used_stetos = abs($initial_value - $value);
        if ($virtual_used_stetos > ($infos['nb_stetos'] - $infos['used_stetos'] + $virtual_stetos)) throw new BgaUserException(self::_("You used too many stethoscopes"));

        //if die color has not been respected
        $virtual_used_bloods = 0;
        //if player has used a already used die (blood-bag-1!)
        if ($bEndOfTurn && $this->powers[$used_die] == self::getGameStateValue('used_die')) $virtual_used_bloods = $virtual_used_bloods + 1;
        if ($this->sheet['hexs'][$room_id]['type'] != $die && $this->sheet['hexs'][$room_id]['type'] != "W") $virtual_used_bloods = $virtual_used_bloods + 1;

        if ($virtual_used_bloods > ($infos['nb_bloods'] - $infos['used_bloods'] + $virtual_bloods)) throw new BgaUserException(self::_("You used too many blood bags"));

        //verifie que la room n'est pas déjà occupee
        $sql = "SELECT value FROM rooms WHERE player_id=$player_id AND room_id=$room_id";
        $infos = self::getCollectionFromDb($sql);

        if (count($infos) == 1) throw new BgaUserException(self::_("This room is already occupied"));

        //verifie que les rooms précedentes dans la ward sont déjà occupées
        $ward_id = $this->sheet['hexs'][$room_id]['ward'];
        $room_list = $this->sheet['wards'][$ward_id]['hexs'];
        $last_value = 0;
        //verifie que les rooms sont coherente
        for ($i = 0; $i < count($room_list); $i++) {
            if ($room_list[$i] == $room_id) {
                if ($last_value >= $value) throw new BgaUserException(self::_("The previous room has a value higher or equal to the new room."));
                break;
            }
            $sql = "SELECT room_id, value,decoration FROM rooms WHERE player_id=$player_id AND room_id=$room_list[$i]";
            $infos = self::getCollectionFromDb($sql);
            //si une room precedente n'est pas remplie, on ne peut pas enregistrer ce nouvel enregistrement
            if (count($infos) == 0) throw new BgaUserException(self::_(" You must fill the previous room first"));
            else {
                if (!strpos($infos[$room_list[$i]]['decoration'], SCREEN)) $last_value = 0;
                else $last_value = $infos[$room_list[$i]]['value'];
            }
        }

        //give a nurse if bonus is nurse and there is still a nurse in the ward
        $sql = "SELECT nurse FROM wards WHERE player_id=$player_id AND ward_id=$ward_id";
        $has_nurse = self::getUniqueValueFromDB($sql);

        $virtual_nurses = $virtual_nurses * $has_nurse;

        //enregistre le nouveau patient
        $sql = "INSERT INTO `rooms` (`room_id`, `player_id`, `value`, `decoration`) VALUES ('$room_id', '$player_id', '$value', '$decoration')";
        self::DbQuery($sql);
        //modifie les changements pour le joueurs TODO
        $sql = "UPDATE player SET nb_stetos = nb_stetos + $virtual_stetos, used_stetos = used_stetos+$virtual_used_stetos, nb_bloods=nb_bloods+$virtual_bloods, used_bloods=used_bloods+$virtual_used_bloods, nb_nurses=nb_nurses+$virtual_nurses WHERE player_id=$player_id";
        self::DbQuery($sql);

        if ($virtual_nurses == 1) {
            $sql = "UPDATE wards SET nurse = 0 WHERE player_id=$player_id AND ward_id=$ward_id";
            self::DbQuery($sql);
        }

        if ($this->gamestate->state_id() == PLAYER_TURN && $bEndOfTurn) {
            self::setGameStateValue('used_die', $this->powers[$used_die]);
        }

        if ($virtual_stetos > 0) {
            $message = clienttranslate('${player_name} writes a ${value} with ${die_color} die in ward n°${num_ward} and receives ${new_stetos} new stethoscopes');
        } else if ($virtual_bloods > 0) {
            $message = clienttranslate('${player_name} writes a ${value} with ${die_color} die in ward n°${num_ward} and receives ${new_bloods} blood bag');
        } else {
            $message = clienttranslate('${player_name} writes a ${value} with ${die_color} die in ward n°${num_ward}');
        }
        // Notify all players
        self::notifyAllPlayers("playerPlayed", $message, array(
            'player_id' => $player_id,
            'player_name' => self::getCurrentPlayerName(),
            'value' => $value,
            'decoration' => $decoration,
            'die_color' => $this->colors[$die],
            'room_id' => $room_id,
            'ward_id' => $ward_id,
            'num_ward' => $ward_id + 1,
            'new_stetos' => $virtual_stetos,
            'new_used_stetos' => $virtual_used_stetos,
            'new_bloods' => $virtual_bloods,
            'new_used_bloods' => $virtual_used_bloods,
            'new_nurses' => $virtual_nurses,
            'used_die' => self::getGameStateValue('used_die')
        ));

        $this->calculateWardScore($player_id, $room_id, $ward_id);
        if ($decoration == CRITICAL) $this->calculateCriticalScore($player_id, $value);
        if ($virtual_nurses == 1) $this->calculateNursesScore($player_id, $ward_id);
        $this->calculateEpidemiologistScore($player_id);
        $this->calculateRadiologistScore($player_id);

        if (!$bEndOfTurn) return;
        else if ($this->gamestate->state_id() == PLAYER_TURN) {
            //TODO prévoir ALICAT GAME
            $this->gamestate->nextState('play');
        } else {
            $this->gamestate->setPlayerNonMultiactive($player_id, "allPlayed");
        }
    }

    /*

    Example:

    function playCard( $card_id )
    {
        // Check that this is the player's turn and that it is a "possible action" at this game state (see states.inc.php)
        self::checkAction( 'playCard' ); 
        
        $player_id = self::getActivePlayerId();
        
        // Add your game logic to play a card there 
        ...
        
        // Notify all players about the card played
        self::notifyAllPlayers( "cardPlayed", clienttranslate( '${player_name} plays ${card_name}' ), array(
            'player_id' => $player_id,
            'player_name' => self::getActivePlayerName(),
            'card_name' => $card_name,
            'card_id' => $card_id
        ) );
          
    }
    
    */


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */

    /*
    
    Example for game state "MyGameState":
    
    function argMyGameState()
    {
        // Get some values from the current game situation in database...
    
        // return values:
        return array(
            'variable1' => $value1,
            'variable2' => $value2,
            ...
        );
    }    
    */

    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */



    function stThrowDice()
    {
        //throw dice
        self::setGameStateValue('R', bga_rand(1, 6));
        self::setGameStateValue('G', bga_rand(1, 6));
        self::setGameStateValue('Y', bga_rand(1, 6));
        self::setGameStateValue('used_die', -1);


        $card = $this->cards->getCardOnTop('deck');
        $this->cards->insertCardOnExtremePosition($card['id'], 'table', true);

        self::notifyAllPlayers('throwDice', clienttranslate('A new turn begins, new dice are rolled and a new card is flipped'), array(
            'red_die' => self::getGameStateValue('R'),
            'yellow_die' => self::getGameStateValue('Y'),
            'green_die' => self::getGameStateValue('G'),
            'active_card' => $card['type_arg'],
            'cards_count' => $this->cards->countCardInLocation('deck')
        ));

        $num_turn = 24 - $this->cards->countCardInLocation('deck');
        //TODO ajouter un control si ALICAT GAME et si num turn pair -> "sologame"

        // else go to another gamestate
        $this->activeNextPlayer();
        $player_id = $this->getActivePlayerId();
        $this->giveExtraTime($player_id);
        $this->gamestate->nextState('normal');
    }

    function stOtherPlayersTurn()
    {
        //get players list
        $players = array_keys($this->loadPlayersBasicInfos());

        //remove active player
        $player = $this->getActivePlayerId();
        $index = array_search($player, $players);
        unset($players[$index]);

        foreach ($players as $player_id) {
            $this->giveExtraTime($player_id);
        }

        $this->gamestate->setPlayersMultiactive($players, END_TURN, true);
    }

    function stEndTurn()
    {
        $this->calculateCardiologistScore(1);
        $this->calculateCardiologistScore(2);
        if ($this->cards->countCardInLocation('deck') == 0) {
            $sql = "SELECT player_id id, score_wards, score_critical, score_nurses, score_cardiologist_1, score_cardiologist_2, score_radiologist, score_epidemiologist, score_dead FROM player ";
            $players = self::getCollectionFromDb($sql);

            foreach ($players as $player_id => $infos) {
                self::setStat($infos['score_wards'], 'ward_score', $player_id);
                self::setStat($infos['score_critical'], 'critical_score', $player_id);
                self::setStat($infos['score_nurses'], 'nurses_score', $player_id);
                self::setStat($infos['score_cardiologist_1'] + $infos['score_cardiologist_2'], 'cardiologist_score', $player_id);
                self::setStat($infos['score_radiologist'], 'radiologist_score', $player_id);
                self::setStat($infos['score_epidemiologist'], 'epidemiologist_score', $player_id);
                self::setStat(-$infos['score_dead'], 'deads_count', $player_id);
            }

            $this->gamestate->nextState('endGame');
        } else {
            $this->gamestate->nextState('newTurn');
        }
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("pass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    // █████                              █████ █████                        
    //░░███                              ░░███ ░░███                         
    // ░███         ██████   ██████    ███████  ░███████  █████ ████  ███████
    // ░███        ███░░███ ░░░░░███  ███░░███  ░███░░███░░███ ░███  ███░░███
    // ░███       ░███ ░███  ███████ ░███ ░███  ░███ ░███ ░███ ░███ ░███ ░███
    // ░███      █░███ ░███ ███░░███ ░███ ░███  ░███ ░███ ░███ ░███ ░███ ░███
    // ███████████░░██████ ░░████████░░████████ ████████  ░░████████░░███████
    //░░░░░░░░░░░  ░░░░░░   ░░░░░░░░  ░░░░░░░░ ░░░░░░░░    ░░░░░░░░  ░░░░░███
    //                                                               ███ ░███
    //                                                              ░░██████ 
    //                                                               ░░░░░░  

    public function test(){
        $this->calculateEpidemiologistScore(2294365);
    }

    public function loadBugReportSQL(int $reportId, array $studioPlayers): void
    {
        $prodPlayers = $this->getObjectListFromDb("SELECT `player_id` FROM `player`", true);
        $prodCount = count($prodPlayers);
        $studioCount = count($studioPlayers);
        if ($prodCount != $studioCount) {
            throw new BgaVisibleSystemException("Incorrect player count (bug report has $prodCount players, studio table has $studioCount players)");
        }

        // SQL specific to your game
        // For example, reset the current state if it's already game over
        $sql = [
            "UPDATE `global` SET `global_value` = 4 WHERE `global_id` = 1 AND `global_value` = 99"
        ];
        foreach ($prodPlayers as $index => $prodId) {
            $studioId = $studioPlayers[$index];
            // SQL common to all games
            $sql[] = "UPDATE `player` SET `player_id` = $studioId WHERE `player_id` = $prodId";
            $sql[] = "UPDATE `global` SET `global_value` = $studioId WHERE `global_value` = $prodId";
            $sql[] = "UPDATE `stats` SET `stats_player_id` = $studioId WHERE `stats_player_id` = $prodId";

            // SQL specific to your game
            // $sql[] = "UPDATE `card` SET `card_location_arg` = $studioId WHERE `card_location_arg` = $prodId";
            // $sql[] = "UPDATE `rooms` SET `my_column` = REPLACE(`my_column`, $prodId, $studioId)";
            $sql[] = "UPDATE `rooms` SET `player_id` = $studioId WHERE `player_id` = $prodId";
            $sql[] = "UPDATE `wards` SET `player_id` = $studioId WHERE `player_id` = $prodId";
        }
        foreach ($sql as $q) {
            $this->DbQuery($q);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //


    }
}
