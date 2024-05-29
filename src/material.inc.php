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
 * material.inc.php
 *
 * DiceHospitalER game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */



if (!defined('STETOS')) { // guard since this included multiple times
  define("STETOS", "stetos");
  define("BLOODS", "bloods");
  define("SCREEN", "screen");
  define("CRITICAL", "critical");
  define("NURSE", "nurses");
  define("PATIENT_RED_1_2", "patient red 1 2"); // NOI18N
  define("PATIENT_RED_3_4", "patient red 3 4"); // NOI18N
  define("PATIENT_RED_5_6", "patient red 5 6"); // NOI18N
  define("PATIENT_GREEN_1_2", "patient green 1 2"); // NOI18N
  define("PATIENT_GREEN_3_4", "patient green 3 4"); // NOI18N
  define("PATIENT_GREEN_5_6", "patient green 5 6"); // NOI18N
  define("PATIENT_YELLOW_1_2", "patient yellow 1 2"); // NOI18N
  define("PATIENT_YELLOW_3_4", "patient yellow 3 4"); // NOI18N
  define("PATIENT_YELLOW_5_6", "patient yellow 5 6"); // NOI18N
  define("EPIDEMIOLOGIST", 0);
  define("RADIOLOGIST", 1);
  define("CARDIOLOGIST", 2);
  define("AMBULANCE", 3);
}

$this->help = array(
  STETOS => clienttranslate("Bonus : 2 stethoscopes"),
  BLOODS => clienttranslate("Bonus : 1 blood bag"),
  SCREEN => clienttranslate("Bonus : isolate a patient"),
  CRITICAL => clienttranslate("Bonus : Victory Points"),
  NURSE => clienttranslate("Bonus : 1 nurse (for the ward, if remaining)"),
  PATIENT_RED_1_2 => clienttranslate("Bonus : extra red patient with value 1 or 2"),
  PATIENT_RED_3_4 => clienttranslate("Bonus : extra red patient with value 3 or 4"),
  PATIENT_RED_5_6 => clienttranslate("Bonus : extra red patient with value 5 or 6"),
  PATIENT_GREEN_1_2 => clienttranslate("Bonus : extra green patient with value 1 or 2"),
  PATIENT_GREEN_3_4 => clienttranslate("Bonus : extra green patient with value 3 or 4"),
  PATIENT_GREEN_5_6 => clienttranslate("Bonus : extra green patient with value 5 or 6"),
  PATIENT_YELLOW_1_2 => clienttranslate("Bonus : extra yellow patient with value 1 or 2"),
  PATIENT_YELLOW_3_4 => clienttranslate("Bonus : extra yellow patient with value 3 or 4"),
  PATIENT_YELLOW_5_6 => clienttranslate("Bonus : extra yellow patient with value 5 or 6"),
);

$this->powers = array(
  "R" => 0,
  "Y" => 1,
  "G" => 2
);

$this->colors = array(
  "R" => clienttranslate("Red"),
  "Y" => clienttranslate("Yellow"),
  "G" => clienttranslate("Green")
);

$this->counters = array('wards', 'critical', 'nurses', 'cardiologist_1', 'cardiologist_2', 'radiologist', 'epidemiologist', 'dead', 'total');
$this->nursesVP = array(0, 2, 5, 9, 14, 20, 27, 35);

$this->specialist_infos = array(
  0 => array(
    'type' => EPIDEMIOLOGIST,
    'value' => "from 1 to 6", // NOI18N
    'help' => clienttranslate("Score 10 points for each continuous sequence of patients valued 1-6"),
    'VP' => 10,
    'solo' => 10,
    'back' => null
  ),
  1 => array(
    'type' => EPIDEMIOLOGIST,
    'value' => "3 same value in line", // NOI18N
    'help' => clienttranslate("Score 5 points for each straight line of 3 connected patients with matching values"),
    'VP' => 5,
    'solo' => 10,
    'back' => null
  ),
  2 => array(
    'type' => EPIDEMIOLOGIST,
    'value' => "max connected area", // NOI18N
    'help' => clienttranslate("Score 3 points for each patient in your largest single connected group of patients with matching values"),
    'VP' => 3,
    'solo' => 9,
    'back' => null
  ),
  3 => array(
    'type' => EPIDEMIOLOGIST,
    'value' => "max area minus min area", // NOI18N
    'help' => clienttranslate("Score 3 points for each patient of your most common value, minus 3 points for each patient of your least common value"),
    'VP' => 3,
    'solo' => 9,
    'back' => null
  ),
  4 => array(
    'type' => EPIDEMIOLOGIST,
    'value' => "triangular even or odd", // NOI18N
    'help' => clienttranslate("Score 4 points for each triangular triple of all odd or all even patient values"),
    'VP' => 4,
    'solo' => 8,
    'back' => null
  ),
  5 => array(
    'type' => RADIOLOGIST,
    'value' => "half adjacent values", // NOI18N
    'help' => clienttranslate("Score points equal to half the total value (rounded up) of all critical patients adjacent to an X-ray lab"),
    'VP' => 0.5,
    'solo' => 8,
    'back' => null
  ),
  6 => array(
    'type' => RADIOLOGIST,
    'value' => "12 or less, 30 or more", // NOI18N
    'help' => clienttranslate("Score 10 points for each X-ray lab surrounded by six patients that have a total of either 12 or less, or 30 or more"),
    'VP' => 10,
    'solo' => 10,
    'back' => null
  ),
  7 => array(
    'type' => RADIOLOGIST,
    'value' => "4 or more", // NOI18N
    'help' => clienttranslate("Score 2 points for each patient valued 4, 5 or 6 adjacent to an X-ray lab"),
    'VP' => 2,
    'solo' => 8,
    'back' => null
  ),
  8 => array(
    'type' => RADIOLOGIST,
    'value' => "pairs opposite side", // NOI18N
    'help' => clienttranslate("Score 4 points for each pair of matching patient values on opposite sides of an X-ray lab"),
    'VP' => 4,
    'solo' => 8,
    'back' => null
  ),
  9 => array(
    'type' => RADIOLOGIST,
    'value' => "same values", // NOI18N
    'help' => clienttranslate("Score 5/10/15/20 points
    for each X-ray lab if it
    has 3/4/5/6 matching
    adjacent patient values"),
    'VP' => array(0, 0, 0, 5, 10, 15, 20),
    'solo' => 10,
    'back' => null
  ),
  10 => array(
    'type' => CARDIOLOGIST,
    'value' => "stetos", // NOI18N
    'help' => clienttranslate("Gain 8 stethoscopes"),
    'VP' => 8,
    'solo' => 0,
    'back' => 16
  ),
  11 => array(
    'type' => CARDIOLOGIST,
    'value' => "X-ray", // NOI18N
    'help' => clienttranslate("Surround an X-ray lab with patients"),
    'VP' => 8,
    'solo' => 0,
    'back' => 17
  ),
  12 => array(
    'type' => CARDIOLOGIST,
    'value' => "nurses", // NOI18N
    'help' => clienttranslate("Gain 3 nurses"),
    'VP' => 8,
    'solo' => 0,
    'back' => 18
  ),
  13 => array(
    'type' => CARDIOLOGIST,
    'value' => "bloods", // NOI18N
    'help' => clienttranslate("Gain 4 blood bags"),
    'VP' => 8,
    'solo' => 0,
    'back' => 19
  ),
  14 => array(
    'type' => CARDIOLOGIST,
    'value' => "opened wards", // NOI18N
    'help' => clienttranslate("Admit at least one patient to all 7 wards"),
    'VP' => 8,
    'solo' => 0,
    'back' => 20
  ),
  15 => array(
    'type' => CARDIOLOGIST,
    'value' => "full wards", // NOI18N
    'help' => clienttranslate("Fill 2 wards"),
    'VP' => 8,
    'solo' => 0,
    'back' => 21
  ),
  16 => array(
    'type' => CARDIOLOGIST,
    'value' => "stetos", // NOI18N
    'help' => clienttranslate("Gain 8 stethoscopes"),
    'VP' => 5,
    'solo' => 0,
    'back' => -16
  ),
  17 => array(
    'type' => CARDIOLOGIST,
    'value' => "X-ray", // NOI18N
    'help' => clienttranslate("Surround an X-ray lab with patients"),
    'VP' => 5,
    'solo' => 0,
    'back' => -17
  ),
  18 => array(
    'type' => CARDIOLOGIST,
    'value' => "nurses", // NOI18N
    'help' => clienttranslate("Gain 3 nurses"),
    'VP' => 5,
    'solo' => 0,
    'back' => -18
  ),
  19 => array(
    'type' => CARDIOLOGIST,
    'value' => "bloods", // NOI18N
    'help' => clienttranslate("Gain 4 blood bags"),
    'VP' => 5,
    'solo' => 0,
    'back' => -19
  ),
  20 => array(
    'type' => CARDIOLOGIST,
    'value' => "opened wards", // NOI18N
    'help' => clienttranslate("Admit at least one patient to all 7 wards"),
    'VP' => 5,
    'solo' => 0,
    'back' => -20
  ),
  21 => array(
    'type' => CARDIOLOGIST,
    'value' => "full wards", // NOI18N
    'help' => clienttranslate("Fill 2 wards"),
    'VP' => 5,
    'solo' => 0,
    'back' => -21
  )
);
$this->ambulance_infos = array(
  0 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, PATIENT_RED_1_2, BLOODS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  1 => array(
    'type' => AMBULANCE,
    'value' => array(NURSE, SCREEN, PATIENT_RED_5_6),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  2 => array(
    'type' => AMBULANCE,
    'value' => array(BLOODS, SCREEN, NURSE),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  3 => array(
    'type' => AMBULANCE,
    'value' => array(BLOODS, CRITICAL, PATIENT_YELLOW_1_2),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  4 => array(
    'type' => AMBULANCE,
    'value' => array(PATIENT_YELLOW_5_6, NURSE, SCREEN),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  5 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, BLOODS, SCREEN),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  6 => array(
    'type' => AMBULANCE,
    'value' => array(PATIENT_GREEN_1_2, BLOODS, CRITICAL),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  7 => array(
    'type' => AMBULANCE,
    'value' => array(SCREEN, PATIENT_GREEN_5_6, NURSE),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  8 => array(
    'type' => AMBULANCE,
    'value' => array(SCREEN, NURSE, CRITICAL),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  9 => array(
    'type' => AMBULANCE,
    'value' => array(STETOS, CRITICAL, BLOODS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  10 => array(
    'type' => AMBULANCE,
    'value' => array(BLOODS, NURSE, CRITICAL),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  11 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, BLOODS, STETOS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  12 => array(
    'type' => AMBULANCE,
    'value' => array(STETOS, NURSE, SCREEN),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  13 => array(
    'type' => AMBULANCE,
    'value' => array(SCREEN, STETOS, NURSE),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  14 => array(
    'type' => AMBULANCE,
    'value' => array(NURSE, SCREEN, STETOS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  15 => array(
    'type' => AMBULANCE,
    'value' => array(STETOS, CRITICAL, NURSE),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  16 => array(
    'type' => AMBULANCE,
    'value' => array(NURSE, STETOS, CRITICAL),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  17 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, NURSE, STETOS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  18 => array(
    'type' => AMBULANCE,
    'value' => array(PATIENT_RED_3_4, BLOODS, SCREEN),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  19 => array(
    'type' => AMBULANCE,
    'value' => array(SCREEN, PATIENT_YELLOW_3_4, BLOODS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  20 => array(
    'type' => AMBULANCE,
    'value' => array(BLOODS, SCREEN, PATIENT_GREEN_3_4),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  21 => array(
    'type' => AMBULANCE,
    'value' => array(STETOS, SCREEN, CRITICAL),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  22 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, STETOS, SCREEN),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  23 => array(
    'type' => AMBULANCE,
    'value' => array(SCREEN, CRITICAL, BLOODS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  24 => array(
    'type' => AMBULANCE,
    'value' => array(CRITICAL, NURSE, BLOODS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  25 => array(
    'type' => AMBULANCE,
    'value' => array(BLOODS, STETOS, NURSE),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  26 => array(
    'type' => AMBULANCE,
    'value' => array(NURSE, BLOODS, STETOS),
    'help' => clienttranslate("Ambulance card"),
    'VP' => 0,
    'solo' => 0,
    'back' => 28
  ),
  /*28 => array (
    'type' => "back",
    'value' => array(),
    'help' => clienttranslate("Deck"),
    'VP' => 0,
    'solo' => 0,
    'back' => null  
  ),*/
);


$this->sheet = array(
  "hexs" => array(
    0 => array(
      'type' => "R",
      'ward' => 0
    ),
    1 => array(
      'type' => "G",
      'ward' => 1
    ),
    2 => array(
      'type' => "Y",
      'ward' => 1
    ),
    3 => array(
      'type' => "R",
      'ward' => 2
    ),
    4 => array(
      'type' => "Y",
      'ward' => 2
    ),
    5 => array(
      'type' => "R",
      'ward' => 2
    ),

    100 => array(
      'type' => "W",
      'ward' => 0
    ),
    101 => array(
      'type' => "W",
      'ward' => 1
    ),
    102 => array(
      'type' => "X",
      'ward' => -1
    ),
    103 => array(
      'type' => "W",
      'ward' => 2
    ),
    104 => array(
      'type' => "Y",
      'ward' => 3
    ),
    105 => array(
      'type' => "W",
      'ward' => 3
    ),
    106 => array(
      'type' => "G",
      'ward' => 3
    ),

    200 => array(
      'type' => "G",
      'ward' => 0
    ),
    201 => array(
      'type' => "Y",
      'ward' => 1
    ),
    202 => array(
      'type' => "R",
      'ward' => 2
    ),
    203 => array(
      'type' => "Y",
      'ward' => 3
    ),
    204 => array(
      'type' => "W",
      'ward' => 4
    ),
    205 => array(
      'type' => "Y",
      'ward' => 4
    ),
    206 => array(
      'type' => "R",
      'ward' => 4
    ),

    301 => array(
      'type' => "W",
      'ward' => 0
    ),
    302 => array(
      'type' => "G",
      'ward' => 1
    ),
    304 => array(
      'type' => "G",
      'ward' => 4
    ),
    305 => array(
      'type' => "G",
      'ward' => 5
    ),
    306 => array(
      'type' => "R",
      'ward' => 5
    ),
    307 => array(
      'type' => "W",
      'ward' => 4
    ),

    403 => array(
      'type' => "R",
      'ward' => 5
    ),
    404 => array(
      'type' => "G",
      'ward' => 5
    ),
    405 => array(
      'type' => "X",
      'ward' => -1
    ),
    406 => array(
      'type' => "R",
      'ward' => 5
    ),
    407 => array(
      'type' => "G",
      'ward' => 5
    ),

    504 => array(
      'type' => "Y",
      'ward' => 6
    ),
    505 => array(
      'type' => "W",
      'ward' => 6
    ),
    506 => array(
      'type' => "Y",
      'ward' => 6
    ),
  ),

  "hexs_size_x" => 42,
  "hexs_size_y" => 50,
  "hexs_origin_x" => 76,
  "hexs_origin_y" => 85,
  "hexs_offset_x" => 58.85,
  "hexs_offset_y" => 50.75,

  "squares_types" => array("stetos", "bloods", "nurses", "deads"),

  "stetos_size" => 52,
  "stetos_origin_x" => 79,
  "stetos_origin_y" => 518,
  "stetos_nb_row" => 3,
  "stetos_nb_column" => 5,

  "bloods_size" => 52,
  "bloods_origin_x" => 391,
  "bloods_origin_y" => 518,
  "bloods_nb_row" => 3,
  "bloods_nb_column" => 3,

  "nurses_size" => 41.5,
  "nurses_origin_x" => 82,
  "nurses_origin_y" => 704,
  "nurses_nb_row" => 1,
  "nurses_nb_column" => 7,

  "deads_size" => 41.5,
  "deads_origin_x" => 418,
  "deads_origin_y" => 704,
  "deads_nb_row" => 1,
  "deads_nb_column" => 4,

  "wards" => array(
    0 => array(
      "hexs" => array(301, 200, 100, 0),
      "VP" => 5,
      "positionning_x_VP" => 110,
      "positionning_y_VP" => 30
    ),
    1 => array(
      "hexs" => array(302, 201, 101, 1, 2),
      "VP" => 7,
      "positionning_x_VP" => 230,
      "positionning_y_VP" => 30
    ),
    2 => array(
      "hexs" => array(202, 103, 3, 4, 5),
      "VP" => 8,
      "positionning_x_VP" => 405,
      "positionning_y_VP" => 30
    ),
    3 => array(
      "hexs" => array(203, 104, 105, 106),
      "VP" => 6,
      "positionning_x_VP" => 461,
      "positionning_y_VP" => 125
    ),
    4 => array(
      "hexs" => array(304, 204, 205, 206, 307),
      "VP" => 9,
      "positionning_x_VP" => 519,
      "positionning_y_VP" => 224
    ),
    5 => array(
      "hexs" => array(403, 404, 305, 306, 406, 407),
      "VP" => 10,
      "positionning_x_VP" => 524,
      "positionning_y_VP" => 344
    ),
    6 => array(
      "hexs" => array(504, 505, 506),
      "VP" => 4,
      "positionning_x_VP" => 437,
      "positionning_y_VP" => 395
    )
  )

);
