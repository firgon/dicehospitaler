{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- DiceHospitalER implementation : © <firgon> <emmanuel.albisser@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------

-->

<div id="dher_table">
    <div id="sheets"></div>

    <div id="game">


        <div id="active_card" class= "ambulance_card">
            <div id="red_bonus" class="bonus red"></div>
            <div id="yellow_bonus" class="bonus yellow"></div>
            <div id="green_bonus" class="bonus green"></div>
            <div id="deckinfo">24</div>
        </div>

        <div id="dice">
            <div id="red_die" class="die bonus red" data-type="R"></div>
            <div id="yellow_die" class="die bonus yellow" data-type="Y"></div>
            <div id="green_die" class="die bonus green" data-type="G"></div>
        </div>
        <div id="dice2">
            <div id="extra_die" class="die bonus" data-type="Y"></div>
        </div>

        <div id="specialist_cards">
            <div id="cardiologist_1" class="specialist_card"></div>
            <div id="cardiologist_2" class="specialist_card"></div>
            <div id="radiologist" class="specialist_card"></div>
            <div id="epidemiologist" class="specialist_card"></div>
        </div>

    </div>

</div>




<script type="text/javascript">

// Javascript HTML templates

var jstpl_sheet='<div class="sheet" id="sheet_${player_id}" style="display:${display}">${name}</div>';
var jstpl_counter = '<div class="counter ${type}" id="score_${type}_${player_id}" ></div>';
var jstpl_hex='<div class="hex" data-type="${type}" data-value="0" id="${div_id}" style="position:absolute; left: ${X}px; top:${Y}px; width:${sizeX}px; height: ${sizeY}px;"></div>';
var jstpl_ward='<div class="ward" id="${div_id}" style="position:absolute; left: ${X}px; top:${Y}px;"></div>';
var jstpl_square='<div class="${type}" style="position:absolute; left: ${X}px; top:${Y}px; width:${size}px; height: ${size}px;"><div id="${div_id}"></div></div>';
var jstpl_player_board = '<button id="voir_${player_id}" class="button">${voir}</button>';

</script>  

{OVERALL_GAME_FOOTER}
