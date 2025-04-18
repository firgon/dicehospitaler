/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * DiceHospitalER implementation : © <firgon> <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * dicehospitaler.js
 *
 * DiceHospitalER user interface script
 *
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */
var isDebug =
  window.location.host == 'studio.boardgamearena.com' ||
  window.location.hash.indexOf('debug') > -1
var debug = isDebug ? console.info.bind(window.console) : function () {}

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
], function (dojo, declare) {
  return declare('bgagame.dicehospitaler', ebg.core.gamegui, {
    constructor: function () {
      console.log('dicehospitaler constructor')

      this.specialist_card_width = 165
      this.specialist_card_height = 225
      this.ambulance_card_width = 375
      this.ambulance_card_height = 275

      this.dice = ['red_die', 'yellow_die', 'green_die']

      this.deckinfo = new ebg.counter()

      this.counters = {}

      //store virtual values for not validated yet moves
      this.virtual = {}

      //for spectator, record a player_id 'point of view'
      this.virtual_id = 0
    },

    /*
            setup:
            
            This method must set up the game user interface according to current game situation specified
            in parameters.
            
            The method is called each time the game interface is displayed to a player, ie:
            _ when the game starts
            _ when a player refreshes the game page (F5)
            
            "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
        */

    setup: function (gamedatas) {
      console.log('Starting game setup')

      // Setting up player boards
      for (let player_id in gamedatas.players) {
        if (this.virtual_id == 0) {
          this.virtual_id = this.isSpectator ? player_id : this.player_id
        }

        let player = gamedatas.players[player_id]

        if (player_id == this.virtual_id) {
          dojo.place(
            this.format_block('jstpl_sheet', {
              player_id: player_id,
              name: player['name'],
              display: 'inline-block',
            }),
            'sheets',
          )
        } else {
          dojo.place(
            this.format_block('jstpl_sheet', {
              player_id: player_id,
              name: player['name'],
              display: 'inline-block',
            }),
            'dher_table',
          )
        }

        //add a button to see an other sheet
        // if (player_id != this.virtual_id){
        //     dojo.place( this.format_block('jstpl_player_board', {player_id:player_id, voir:_("See their sheet")} ), 'player_board_'+player_id );
        //     dojo.connect ($('player_board_'+player_id), 'onmouseover', this, (e) => { this.displaySheet(player_id); } );
        //     dojo.connect ($('player_board_'+player_id), 'onmouseout', this, (e) => { this.hideSheet(player_id); } );
        // }

        //prepare all places :
        this.prepareAllHexs(player_id)

        if (player['epidemiologist_rooms'])
          this.displayEpidemiologistRooms(
            player_id,
            JSON.parse(player['epidemiologist_rooms']),
          )
        this.prepareAllSquares(player_id)

        //}

        // TODO: Setting up players boards if needed
        //setting players counters:

        this.counters[player_id] = {}
        gamedatas.counters.forEach((type) => {
          dojo.place(
            this.format_block('jstpl_counter', {
              player_id: player_id,
              type: type,
            }),
            'sheet_' + player_id,
          )
          this.counters[player_id][type] = new ebg.counter()
          this.counters[player_id][type].create(
            'score_' + type + '_' + player_id,
          )
          this.counters[player_id][type].setValue(player['score_' + type])
        })
      }
      // TODO: Set up your game interface here, according to "gamedatas"

      for (let player in gamedatas.rooms) {
        for (let room in gamedatas.rooms[player]) {
          this.writeANumber(
            gamedatas.rooms[player][room]['value'],
            room,
            player,
            gamedatas.rooms[player][room]['decoration'],
          )
        }
      }

      for (let player in gamedatas.wards) {
        for (let ward in gamedatas.wards[player]) {
          this.writeAWard(
            ward,
            player,
            gamedatas.wards[player][ward]['VP'],
            gamedatas.wards[player][ward]['nurse'],
          )
        }
      }

      //display and prepare dice
      for (let index in this.dice) {
        dojo.connect($(this.dice[index]), 'onclick', this, (e) => {
          this.onClickOnDice(this.dice[index])
        })

        dojo.style(
          this.dice[index],
          'background-position-y',
          this.getYFromDiceNb(gamedatas[this.dice[index]]),
        )
        dojo.attr(this.dice[index], 'data-value', gamedatas[this.dice[index]])
      }

      //extra die
      dojo.connect($('extra_die'), 'onclick', this, (e) => {
        if (!this.checkAction('play', true)) return
        this.onClickOnDice('extra_die')
      })

      this.useADie(gamedatas.used_die)

      //display active_card
      this.displayNewCard(gamedatas.active_card)

      //display specialist cards
      dojo.style(
        'radiologist',
        'background-position',
        this.getCoordFromCardNb(gamedatas.radiologist, true),
      )
      this.addTooltip(
        'radiologist',
        this.getSpecialistToolTip(gamedatas.radiologist),
        '',
      )
      dojo.style(
        'epidemiologist',
        'background-position',
        this.getCoordFromCardNb(gamedatas.epidemiologist, true),
      )
      this.addTooltip(
        'epidemiologist',
        this.getSpecialistToolTip(gamedatas.epidemiologist),
        '',
      )
      if (gamedatas.cardiologist_1 < 0) {
        dojo.addClass('cardiologist_1', 'used')
        dojo.style(
          'cardiologist_1',
          'background-position',
          this.getCoordFromCardNb(-gamedatas.cardiologist_1, true),
        )
      } else {
        dojo.style(
          'cardiologist_1',
          'background-position',
          this.getCoordFromCardNb(gamedatas.cardiologist_1, true),
        )
      }
      this.addTooltip(
        'cardiologist_1',
        this.getSpecialistToolTip(gamedatas.cardiologist_1),
        '',
      )
      if (gamedatas.cardiologist_2 < 0) {
        dojo.addClass('cardiologist_2', 'used')
        dojo.style(
          'cardiologist_2',
          'background-position',
          this.getCoordFromCardNb(-gamedatas.cardiologist_2, true),
        )
      } else {
        dojo.style(
          'cardiologist_2',
          'background-position',
          this.getCoordFromCardNb(gamedatas.cardiologist_2, true),
        )
      }

      this.addTooltip(
        'cardiologist_2',
        this.getSpecialistToolTip(gamedatas.cardiologist_2),
        '',
      )
      this.deckinfo.create('deckinfo')
      this.deckinfo.setValue(gamedatas['cards_count'])
      this.addTooltip('deckinfo', _('Remaining cards'), '')

      this.resetVirtual()

      // Setup game notifications to handle (see "setupNotifications" method below)
      this.setupNotifications()

      console.log('Ending game setup')
    },

    ///////////////////////////////////////////////////
    //// Game & client states

    // onEnteringState: this method is called each time we are entering into a new game state.
    //                  You can use this method to perform some user interface changes at this moment.
    //
    onEnteringState: function (stateName, args) {
      console.log('Entering state: ' + stateName)

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Show some HTML block at this game state
                dojo.style( 'my_html_block_id', 'display', 'block' );
                
                break;
           */

        case 'dummmy':
          break
      }
    },

    // onLeavingState: this method is called each time we are leaving a game state.
    //                 You can use this method to perform some user interface changes at this moment.
    //
    onLeavingState: function (stateName) {
      console.log('Leaving state: ' + stateName)

      switch (stateName) {
        /* Example:
            
            case 'myGameState':
            
                // Hide the HTML block we are displaying only during this game state
                dojo.style( 'my_html_block_id', 'display', 'none' );
                
                break;
           */

        case 'dummmy':
          break
      }
    },

    // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
    //                        action status bar (ie: the HTML links in the status bar).
    //
    onUpdateActionButtons: function (stateName, args) {
      console.log('onUpdateActionButtons: ' + stateName)

      if (this.isCurrentPlayerActive()) {
        switch (stateName) {
          case 'playerTurn':
            this.addActionButton('minus', '-1', 'onUseStetos_minus')
            this.checkMinusPossible()
            this.addActionButton('plus', '+1', 'onUseStetos_plus')
            this.checkPlusPossible()
            this.addActionButton(
              'blood_all',
              _('Use blood bag'),
              'onUseBlood_all_color',
            )
            this.checkBloodAllPossible()
            this.addActionButton('pass', _('Pass'), 'onPass')
            this.checkPassPossible()
            break

            break

          case 'otherPlayersTurn':
            this.addActionButton('minus', '-1', 'onUseStetos_minus')
            this.checkMinusPossible()
            this.addActionButton('plus', '+1', 'onUseStetos_plus')
            this.checkPlusPossible()
            this.addActionButton(
              'blood_all',
              _('Use blood bag'),
              'onUseBlood_all_color',
            )
            this.checkBloodAllPossible()
            this.addActionButton(
              'blood_activate',
              _('Use already played die'),
              'onUseBlood_activate',
            )
            this.checkBloodActivatePossible()
            this.addActionButton('pass', _('Pass'), 'onPass')
            this.checkPassPossible()
            break

            break
        }
      }
    },

    ///////////////////////////////////////////////////
    //// Utility methods

    /*
        
            Here, you can defines some utility methods that you can use everywhere in your javascript
            script.
        
        */

    prepareAllHexs: function (player_id) {
      let size_x = this.gamedatas.sheet['hexs_size_x']
      let size_y = this.gamedatas.sheet['hexs_size_y']

      for (let room_id in this.gamedatas.sheet['hexs']) {
        let row = this.getRowFromRoomID(room_id)
        let column = this.getColumnFromRoomID(room_id)

        let x =
          this.gamedatas.sheet['hexs_origin_x'] +
          (column - (row % 2 == 1 ? 0.5 : 0)) *
            this.gamedatas.sheet['hexs_offset_x']
        let y =
          this.gamedatas.sheet['hexs_origin_y'] +
          row * this.gamedatas.sheet['hexs_offset_y']

        let div_id = 'hex_' + player_id + '_' + room_id

        dojo.place(
          this.format_block('jstpl_hex', {
            div_id: div_id,
            type: this.gamedatas.sheet['hexs'][room_id]['type'],
            X: x,
            Y: y,
            sizeX: size_x,
            sizeY: size_y,
          }),
          'sheet_' + player_id,
        )

        dojo.connect($(div_id), 'onclick', this, (e) => {
          this.onWrite(e, room_id)
        })
      }

      //prepare Hexs 'wards' (not playable but useful for displaying score)
      for (let ward_id in this.gamedatas.sheet['wards']) {
        let x = this.gamedatas.sheet['wards'][ward_id]['positionning_x_VP']
        let y = this.gamedatas.sheet['wards'][ward_id]['positionning_y_VP']

        let div_id = 'ward_' + player_id + '_' + ward_id

        dojo.place(
          this.format_block('jstpl_ward', {
            div_id: div_id,
            X: x,
            Y: y,
          }),
          'sheet_' + player_id,
        )
      }
    },

    writeANumber: function (value, room_id, player_id, decoration) {
      let div_id = 'hex_' + player_id + '_' + room_id
      dojo.addClass(div_id, decoration)
      $(div_id).innerHTML = value
      dojo.attr(div_id, 'data-value', value)
      dojo.removeClass(div_id, 'virtual_value')
    },

    writeAWard: function (ward_id, player_id, VP, nurse) {
      let div_id = 'ward_' + player_id + '_' + ward_id
      if (VP > 0) dojo.addClass(div_id, 'ended')
      if (nurse == 0) dojo.addClass(div_id, 'nurse-taken')
    },

    useADie: function (die_id) {
      switch (parseInt(die_id)) {
        case 0:
          dojo.addClass('red_die', 'used')
          this.addTooltip(
            'red_die',
            _(
              'This die has been used, you can play it though by using a blood bag',
            ),
            '',
          )
          this.used_die = 'R'
          break
        case 1:
          dojo.addClass('yellow_die', 'used')
          this.addTooltip(
            'yellow_die',
            _(
              'This die has been used, you can play it though by using a blood bag',
            ),
            '',
          )
          this.used_die = 'Y'
          break
        case 2:
          dojo.addClass('green_die', 'used')
          this.addTooltip(
            'green_die',
            _(
              'This die has been used, you can play it though by using a blood bag',
            ),
            '',
          )
          this.used_die = 'G'
          break
        default:
          this.removeTooltip('red_die')
          this.removeTooltip('yellow_die')
          this.removeTooltip('green_die')
          dojo.query('.die.used').removeClass('used')
          this.used_die = ''
          break
      }
    },

    prepareAllSquares: function (player_id) {
      for (let type in this.gamedatas.sheet.squares_types) {
        this.prepareSquares(this.gamedatas.sheet.squares_types[type], player_id)
      }
    },

    prepareSquares: function (type, player_id) {
      let row = this.gamedatas.sheet[type + '_nb_row']
      let column = this.gamedatas.sheet[type + '_nb_column']
      let size = this.gamedatas.sheet[type + '_size']
      let index = 0

      for (let y = 0; y < row; y++) {
        for (let x = 0; x < column; x++) {
          index++

          let div_id = type + '_' + player_id + '_' + index
          dojo.place(
            this.format_block('jstpl_square', {
              div_id: div_id,
              X: x * size + this.gamedatas.sheet[type + '_origin_x'],
              Y: y * size + this.gamedatas.sheet[type + '_origin_y'],
              size: size,
              type: type,
            }),
            'sheet_' + player_id,
          )

          //circle all squares < nb squares owned by player
          if (index <= this.gamedatas.players[player_id]['nb_' + type]) {
            dojo.addClass(div_id, 'circle')
          }

          if (type == 'bloods' || type == 'stetos') {
            if (index <= this.gamedatas.players[player_id]['used_' + type]) {
              dojo.addClass(div_id, 'used')
            }
          }
        }
      }
    },

    updateAllSquares: function (player_id = this.virtual_id) {
      for (let type in this.gamedatas.sheet.squares_types) {
        this.updateSquares(this.gamedatas.sheet.squares_types[type], player_id)
      }
    },

    updateSquares: function (type, player_id = this.virtual_id) {
      let row = this.gamedatas.sheet[type + '_nb_row']
      let column = this.gamedatas.sheet[type + '_nb_column']
      let index = 0

      for (let y = 0; y < row; y++) {
        for (let x = 0; x < column; x++) {
          index++

          let div_id = type + '_' + player_id + '_' + index

          //circle all squares < nb squares owned by player
          if (index <= this.gamedatas.players[player_id]['nb_' + type]) {
            dojo.addClass(div_id, 'circle')
          } else if (
            this.virtual_id == player_id &&
            index <=
              this.virtual['nb_' + type] +
                parseInt(this.gamedatas.players[player_id]['nb_' + type])
          ) {
            dojo.addClass(div_id, 'virtual_circle')
          } else {
            dojo.removeClass(div_id, 'virtual_circle')
          }

          if (type == 'bloods' || type == 'stetos') {
            if (index <= this.gamedatas.players[player_id]['used_' + type]) {
              dojo.addClass(div_id, 'used')
            } else if (
              this.virtual_id == player_id &&
              index <=
                this.virtual['used_' + type] +
                  parseInt(this.gamedatas.players[player_id]['used_' + type])
            ) {
              dojo.addClass(div_id, 'virtual_used')
            } else {
              dojo.removeClass(div_id, 'virtual_used')
            }
          }
        }
      }
    },

    getRowFromRoomID: function (room_id) {
      return Math.floor(room_id / 100)
    },

    getColumnFromRoomID: function (room_id) {
      return room_id % 100
    },

    getYFromDiceNb: function (x) {
      return -108 * (x - 1) + 'px'
    },

    getCoordFromCardNb: function (x, bSpecialist) {
      if (bSpecialist) {
        let row = Math.floor(x / 11)
        let column = x % 11
        return (
          -this.specialist_card_width * column +
          'px ' +
          -this.specialist_card_height * row +
          'px '
        )
      } else {
        let column = Math.floor(x / 7)
        let row = x % 7
        return (
          -this.ambulance_card_width * column +
          'px ' +
          -this.ambulance_card_height * row +
          'px '
        )
      }
    },

    displaySheet: function (player_id) {
      dojo.style('sheet_' + player_id, 'display', 'inline-block')
      dojo.style('sheet_' + this.virtual_id, 'display', 'none')
    },

    hideSheet: function (player_id) {
      dojo.style('sheet_' + player_id, 'display', 'none')
      dojo.style('sheet_' + this.virtual_id, 'display', 'inline-block')
    },

    updateMoves: function () {
      // function to take into account a new selection of die
      let type //"R", "Y" or "G"
      let selected_die = this.getSelectedDie()

      //if a die is selected...
      if (selected_die != '') {
        //if it's extra die or extra die source, reset action but keep in mind extra die
        if (
          selected_die[0].id == 'extra_die' ||
          selected_die[0].id == this.virtual['extra_die_source']
        ) {
          /* BIG CHANGE
                        let source = this.virtual['extra_die_source'];
                        let first_move = this.virtual['first_move'];
                        let first_room = this.virtual['first_room'];
                        let first_value = this.virtual['first_value'];
                        this.resetVirtual();
                        this.virtual['first_room'] = first_room;
                        this.virtual['first_move'] = first_move;
                        this.virtual['first_value'] = first_value;
                        this.virtual['extra_die_source'] = source;*/

          //Reset virtual (keeping extra_die_source) except if a first move have been recorded
          if (this.virtual['first_move'] == '') {
            let source = this.virtual['extra_die_source']
            this.resetVirtual()
            this.virtual['extra_die_source'] = source
          } else {
          }
        } else {
          //...else reset action and activate new die
          this.resetVirtual()
          type = selected_die.attr('data-type')
          //value = parseInt(selected_die.attr('data-value'));

          let power_id = this.gamedatas.powers[type]
          let power = this.gamedatas.ambulance_infos[this.gamedatas.active_card]
            .value[power_id]

          switch (power) {
            case 'stetos':
              this.virtual['nb_stetos'] = 2
              this.updateSquares('stetos')
              break
            case 'nurses':
              this.virtual['nb_nurses'] = 1
              this.updateSquares('nurses')
              break
            case 'bloods':
              this.virtual['nb_bloods'] = 1
              this.updateSquares('bloods')
              break
            case 'critical':
              this.virtual['decoration'] = power
              break
            case 'screen':
              this.virtual['decoration'] = power
              break

            default:
              //should be always true
              if (power.indexOf('patient') == 0) {
                this.virtual['extra_die_source'] = selected_die[0].id

                let extra_value = power.split(' ')[2]
                let extra_type = power.substring(8, 9).toUpperCase()
                let extra_color = power.split(' ')[1]

                dojo.attr('extra_die', 'data-value', extra_value)
                dojo.attr('extra_die', 'data-type', extra_type)
                dojo.removeClass('extra_die', 'red')
                dojo.removeClass('extra_die', 'green')
                dojo.removeClass('extra_die', 'yellow')

                dojo.addClass('extra_die', extra_color)

                dojo.style(
                  'extra_die',
                  'background-position-y',
                  this.getYFromDiceNb(extra_value),
                )
              }
              break
          }
        }

        //if selected die, or extra die source is used die, increments used bloods
        if (
          this.used_die != '' &&
          (type == this.used_die ||
            this.virtual['extra_die_source'].charAt(0).toUpperCase() ==
              this.used_die)
        ) {
          this.virtual['used_bloods'] = 1
          this.virtual['blood_activate'] = 1
        }
      } else {
        //s'il n'y a pas de dé selectionné, il n'y a plus d'action en cours, réseter l'action en cours
        this.resetVirtual()
      }

      //si on a un extra die en mémoire, on peut l'afficher
      if (this.virtual['extra_die_source'] != '') {
        dojo.style('extra_die', 'visibility', 'visible')
      } else {
        dojo.style('extra_die', 'visibility', 'hidden')
      }

      this.update()
    },

    toggleExtraDieSource: function (die) {
      let other_die =
        die == 'extra_die' ? this.virtual['extra_die_source'] : 'extra_die'

      dojo.addClass(die, 'selected')
      dojo.removeClass(other_die, 'selected')
    },

    selectDie: function (die) {
      //first, check if we are on "extra_die_move" then toggle extra die and extra die source
      if (die == this.virtual['extra_die_source'] || die == 'extra_die') {
        if (dojo.hasClass(die, 'inactive')) return
        //TODOTODO demander confirmation si un premier coup était en cours
        this.toggleExtraDieSource(die)
      } else {
        //then on normal move, toogle the 3 normal dice
        for (let index in this.dice) {
          if (this.dice[index] == die) {
            dojo.addClass(this.dice[index], 'selected')
            dojo.removeClass(this.dice[index], 'inactive')
          } else {
            dojo.removeClass(this.dice[index], 'selected')
            dojo.addClass(this.dice[index], 'inactive')
          }
        }

        dojo.removeClass('extra_die', 'selected')
        dojo.removeClass('extra_die', 'inactive')
      }
    },

    unselectDie: function (die) {
      for (let index in this.dice) {
        if (this.dice[index] == die || die === undefined) {
          dojo.removeClass(this.dice[index], 'selected')
          dojo.removeClass(this.dice[index], 'inactive')
        } else {
          dojo.removeClass(this.dice[index], 'inactive')
        }
      }
      //dojo.removeClass('extra_die', 'inactive');
      dojo.removeClass('extra_die', 'selected')
      dojo.style('extra_die', 'visibility', 'hidden')
    },

    displayEpidemiologistRooms: function (player_id, good_rooms) {
      // function to highlight rooms that give points for epidemilogist
      dojo
        .query('#sheet_' + player_id + ' .epidemiologist_rooms')
        .removeClass('epidemiologist_rooms')
      for (const room_id of good_rooms) {
        let div = 'hex_' + player_id + '_' + room_id

        dojo.addClass(div, 'epidemiologist_rooms')
      }
    },

    displayPossibleMoves: function () {
      this.hidePossibleMoves()
      if (this.virtual['first_move'] != '') {
        let div = 'hex_' + this.player_id + '_' + this.virtual['first_room']
        dojo.attr(div, 'data-value', this.virtual['first_value'])
        $(div).innerHTML = this.virtual['first_value']
        dojo.addClass(div, 'virtual_value')
      } else {
        dojo.query('.hex.virtual_value').innerHTML('')
        dojo.query('.hex.virtual_value').attr('data-value', 0)
        dojo.query('.hex.virtual_value').removeClass('virtual_value')
      }

      let value = this.getSelectedValue() + this.virtual['modif']
      let decoration = this.virtual['decoration']
      if (this.virtual['screened_room'] != -1)
        dojo.addClass(
          'hex_' + this.player_id + '_' + this.virtual['screened_room'],
          'virtual-screen',
        )
      let type = this.getSelectedType()

      for (let ward of this.gamedatas.sheet.wards) {
        let last_room_id = null
        let last_value = 0
        for (let room_id of ward.hexs) {
          let value_hex = dojo.attr(
            'hex_' + this.player_id + '_' + room_id,
            'data-value',
          )
          //if room is not empty
          if (value_hex != 0) {
            //if there is a screen, value count as 0
            if (
              dojo.hasClass(
                'hex_' + this.player_id + '_' + room_id,
                'screen',
              ) ||
              dojo.hasClass(
                'hex_' + this.player_id + '_' + room_id,
                'virtual-screen',
              )
            ) {
              last_room_id = null
              last_value = 0
            } else {
              last_room_id = room_id
              last_value = value_hex
            }
            continue
          } else {
            if (
              last_value < value &&
              (this.virtual['type'] == 'W' ||
                dojo.attr(
                  'hex_' + this.player_id + '_' + room_id,
                  'data-type',
                ) == type ||
                dojo.attr(
                  'hex_' + this.player_id + '_' + room_id,
                  'data-type',
                ) == 'W')
            ) {
              dojo.addClass('hex_' + this.player_id + '_' + room_id, 'active')
            }
            if (decoration == 'screen' && last_room_id != null)
              dojo.addClass(
                'hex_' + this.player_id + '_' + last_room_id,
                'active-screen',
              )
            break
          }
        }
      }
      if (decoration != '') dojo.query('.hex.active').addClass(decoration)
      dojo.query('.hex.active').innerHTML(value)
    },

    hidePossibleMoves: function () {
      dojo.query('.hex.active').innerHTML('')

      dojo.query('.hex.active').removeClass('screen')
      dojo.query('.hex.virtual-screen').removeClass('virtual-screen')
      dojo.query('.hex.active-screen').removeClass('active-screen')
      dojo.query('.hex.active').removeClass('critical')
      dojo.query('.hex.active').removeClass('active')
    },

    rollDice: function (dice) {
      for (let index in this.dice) {
        dojo.style(
          this.dice[index],
          'background-position-y',
          this.getYFromDiceNb(dice[this.dice[index]]),
        )
        dojo.attr(this.dice[index], 'data-value', dice[this.dice[index]])
      }
    },

    displayNewCard: function (card_id) {
      this.gamedatas.active_card = card_id
      if (card_id == -1) {
        dojo.style(
          'active_card',
          'background-position',
          this.ambulance_card_width + 'px ' + this.ambulance_card_height + 'px',
        )
        return
      }
      dojo.style(
        'active_card',
        'background-position',
        this.getCoordFromCardNb(card_id, false),
      )

      this.addTooltip('red_bonus', this.getToolTip(card_id, 0), '')
      this.addTooltip('yellow_bonus', this.getToolTip(card_id, 1), '')
      this.addTooltip('green_bonus', this.getToolTip(card_id, 2), '')
    },

    getToolTip: function (card_id, num) {
      let card = this.gamedatas.ambulance_infos[card_id]
      let type = card.value[num]
      return _(this.gamedatas.help[type])
    },

    getSpecialistToolTip: function (card_id) {
      if (card_id < 0) return _('This card is no longer active')
      let card = this.gamedatas.specialist_infos[card_id]
      return _(card.help)
    },

    updateCardsCount: function (num_card) {
      this.deckinfo.toValue(num_card)
    },

    setMainTitle: function (text) {
      let main = $('pagemaintitletext')
      main.innerHTML = text
    },

    //function to reset all variables around actual move from user before sending data to server
    resetVirtual: function () {
      this.virtual = {
        nb_stetos: 0,
        used_stetos: 0,
        nb_bloods: 0,
        used_bloods: 0,
        nb_nurses: 0,
        nb_deads: 0,
        modif: 0,
        blood_all: 0,
        decoration: '',
        screened_room: -1,
        type: '',
        blood_activate: 0,
        extra_die_source: '',
        first_room: '',
        first_move: '',
        first_value: '',
      }
      this.updateAllSquares()
    },

    getSelectedDie: function () {
      return dojo.query('.die.selected')
    },

    getSelectedValue: function () {
      return parseInt(this.getSelectedDie().attr('data-value'))
    },

    getSelectedType: function () {
      return this.getSelectedDie().attr('data-type')
    },

    get: function (type_value, player_id = this.player_id) {
      return parseInt(this.gamedatas.players[player_id][type_value])
    },

    set: function (type_value, value, player_id = this.player_id) {
      this.gamedatas.players[player_id][type_value] = value
    },

    increase: function (type_value, new_value, player_id = this.player_id) {
      old_value = this.get(type_value, player_id)
      this.set(type_value, old_value + parseInt(new_value), player_id)
    },

    checkMinusPossible: function () {
      //if button not visible return
      if ($('minus') == null) return

      //Display only if a die is selected
      if (this.getSelectedDie() == '') {
        dojo.style('minus', 'display', 'none')
        return
      } else dojo.style('minus', 'display', 'inline-block')

      //you can't write a number under 1
      if (this.getSelectedValue() + this.virtual['modif'] <= 1) {
        dojo.addClass('minus', 'disabled')
        this.addTooltip('minus', _('Die value is already at minimum'), '')
      } else if (
        this.getSelectedDie()[0].id == 'extra_die' &&
        this.getSelectedValue() % 2 == 0
      ) {
        //if extra_die selected and value even, you can always minus one
        dojo.removeClass('minus', 'disabled')
        this.addTooltip('minus', '', _('Minus 1 from die value'))
      } else if (
        this.get('used_stetos') + this.virtual['used_stetos'] ==
          this.get('nb_stetos') + this.virtual['nb_stetos'] &&
        this.virtual['modif'] <= 0
      ) {
        dojo.addClass('minus', 'disabled')
        this.addTooltip('minus', _("You don't have enough stethoscopes"), '')
      } else {
        dojo.removeClass('minus', 'disabled')
        this.addTooltip('minus', '', _('Minus 1 from die value'))
      }
    },

    checkPlusPossible: function () {
      if ($('plus') == null) return
      if (this.getSelectedDie() == '') {
        dojo.style('plus', 'display', 'none')
        return
      } else dojo.style('plus', 'display', 'inline-block')

      if (this.getSelectedValue() + this.virtual['modif'] >= 6) {
        dojo.addClass('plus', 'disabled')
        this.addTooltip('plus', _('Die value is already at maximum'), '')
      } else if (
        this.getSelectedDie()[0].id == 'extra_die' &&
        this.getSelectedValue() % 2 == 1
      ) {
        //if extra_die selected and value even, you can always add one
        dojo.removeClass('plus', 'disabled')
        this.addTooltip('plus', '', _('Add 1 to die value'))
      } else if (
        this.get('used_stetos') + this.virtual['used_stetos'] ==
          this.get('nb_stetos') + this.virtual['nb_stetos'] &&
        this.virtual['modif'] >= 0
      ) {
        dojo.addClass('plus', 'disabled')
        this.addTooltip('plus', _("You don't have enough stethoscopes"), '')
      } else {
        dojo.removeClass('plus', 'disabled')

        this.addTooltip('plus', '', _('Add 1 to die value'))
      }
    },

    checkBloodAllPossible: function () {
      // if button not in dom return
      if ($('blood_all') == null) return
      //if no die selected make button invisible
      if (this.getSelectedDie() == '') {
        dojo.style('blood_all', 'display', 'none')
        return
      } else dojo.style('blood_all', 'display', 'inline-block')

      if (this.virtual['blood_all'] == 1) {
        dojo.addClass('blood_all', 'disabled')
        this.addTooltip('blood_all', _('Already used'), '')
      } else if (
        this.get('used_bloods') + this.virtual['used_bloods'] ==
        this.get('nb_bloods') + this.virtual['nb_bloods']
      ) {
        dojo.addClass('blood_all', 'disabled')
        this.addTooltip('blood_all', _("You don't have enough blood bags"), '')
      } else {
        dojo.removeClass('blood_all', 'disabled')

        this.addTooltip('blood_all', '', _('Ignore die color'))
      }
    },

    checkBloodActivatePossible: function () {
      if ($('blood_activate') == null) return
      if (this.getSelectedDie() != '') {
        dojo.style('blood_activate', 'display', 'none')
        return
      } else dojo.style('blood_activate', 'display', 'inline-block')

      if (this.virtual['blood_activate'] == 1) {
        dojo.addClass('blood_activate', 'disabled')
        this.addTooltip('blood_activate', _('Already used'), '')
      } else if (
        this.get('used_bloods') + this.virtual['used_bloods'] ==
        this.get('nb_bloods') + this.virtual['nb_bloods']
      ) {
        dojo.addClass('blood_activate', 'disabled')
        this.addTooltip(
          'blood_activate',
          _("You don't have enough blood bags"),
          '',
        )
      } else {
        dojo.removeClass('blood_activate', 'disabled')

        this.addTooltip(
          'blood_activate',
          '',
          _('Paying 1 blood bag, you can play the used dice'),
        )
      }
    },

    checkPassPossible: function () {
      if ($('pass') == null) return
      if (this.getSelectedDie() == '') {
        dojo.addClass('pass', 'disabled')
      } else dojo.removeClass('pass', 'disabled')
    },

    update: function () {
      if (this.getSelectedDie() == '') {
        let new_title = bga_format(_('*You* must choose a die'), {
          '*': (t) =>
            '<span style="font-weight:bold;color:#ff0000;">' + t + '</span>',
        })
        this.setMainTitle(new_title)
        this.hidePossibleMoves()
        this.resetVirtual()
      } else {
        let new_title = dojo.string.substitute(
          _('Select a room to write a ${number}'),
          {
            number: this.getSelectedValue() + this.virtual['modif'],
          },
        )
        this.setMainTitle(new_title)
        this.displayPossibleMoves()
      }

      this.updateAllSquares()
      this.checkMinusPossible()
      this.checkPlusPossible()
      this.checkBloodAllPossible()
      this.checkBloodActivatePossible()
      this.checkPassPossible()
    },

    ///////////////////////////////////////////////////
    //// Player's action

    /*
        
            Here, you are defining methods to handle player's action (ex: results of mouse click on 
            game objects).
            
            Most of the time, these methods:
            _ check the action is possible at this game state.
            _ make a call to the game server
        
        */

    onClickOnDice: function (die) {
      // function to toggle selection on a die

      //return if the click is forbiden (die used or not your turn)
      if (!this.checkAction('play', true)) return

      if (
        dojo.hasClass(die, 'used') &&
        die != this.virtual['extra_die_source']
      ) {
        return
      }

      if (dojo.hasClass(die, 'selected')) this.unselectDie(die)
      else this.selectDie(die)

      this.updateMoves()
    },

    onUseStetos_minus: function (evt) {
      console.log('onUseStetos_minus')

      // Preventing default browser reaction
      dojo.stopEvent(evt)

      //first : if modif has been increased, minus it
      if (this.virtual['modif'] > 0 && this.virtual['used_stetos'] > 0) {
        this.virtual['used_stetos'] -= 1
        this.virtual['modif'] -= 1
      } else if (
        this.getSelectedDie()[0].id == 'extra_die' &&
        this.getSelectedValue() % 2 == 0
      ) {
        //if selected die is extra die and value is even...

        //modify die display and value
        let extra_value = parseInt(dojo.attr('extra_die', 'data-value'))
        extra_value -= 1

        dojo.attr('extra_die', 'data-value', extra_value)
        dojo.style(
          'extra_die',
          'background-position-y',
          this.getYFromDiceNb(extra_value),
        )
      } else {
        this.virtual['used_stetos'] += 1
        this.virtual['modif'] -= 1
      }
      this.update()
    },

    onUseStetos_plus: function (evt) {
      console.log('onUseStetos_plus')

      // Preventing default browser reaction
      dojo.stopEvent(evt)

      //first if modif has been minused, increase it (something like a cancelation)
      if (this.virtual['modif'] < 0 && this.virtual['used_stetos'] > 0) {
        this.virtual['used_stetos'] -= 1
        this.virtual['modif'] += 1
      } else if (
        this.getSelectedDie()[0].id == 'extra_die' &&
        this.getSelectedValue() % 2 == 1
      ) {
        //modifier affichage dé et modifier value
        let extra_value = parseInt(dojo.attr('extra_die', 'data-value'))
        extra_value += 1

        dojo.attr('extra_die', 'data-value', extra_value)
        dojo.style(
          'extra_die',
          'background-position-y',
          this.getYFromDiceNb(extra_value),
        )
      } else {
        this.virtual['used_stetos'] += 1
        this.virtual['modif'] += 1
      }

      this.update()
    },

    onUseBlood_all_color: function (evt) {
      console.log('onUseBlood_all_color')

      // Preventing default browser reaction
      dojo.stopEvent(evt)

      this.virtual['type'] = 'W'
      this.virtual['used_bloods'] += 1
      this.virtual['blood_all'] = 1

      this.updateSquares('bloods')

      this.update()
    },

    onUseBlood_activate: function (evt) {
      // function to activate the already used die by using a blood bag
      console.log('onUseBlood_activate+')

      // Preventing default browser reaction
      dojo.stopEvent(evt)

      this.virtual['used_bloods'] += 1
      this.virtual['blood_activate'] = 1

      this.updateSquares('bloods')

      dojo.query('.die.used').addClass('selected')
      this.updateMoves()
    },

    onPass: function (evt) {
      console.log('onPass')

      //Preventing default browser reaction
      dojo.stopEvent(evt)

      // Check that this action is possible (see "possibleactions" in states.inc.php)
      if (!this.checkAction('pass')) {
        return
      }

      if (this.getSelectedDie() == '') return

      if (this.virtual['first_move'] != '') {
        //find first die
        let infos = this.virtual['first_move'].split(' ')
        let die = infos[0]
        //if first move was with extra die, then pass normal die, else pass extra die
        let decoration =
          infos[2] == 'extra_die' ? 'pass_normal_die' : 'pass_extra_die'

        this.ajaxcall(
          '/dicehospitaler/dicehospitaler/play.html',
          {
            lock: true,
            die: die,
            room_id: this.virtual['first_room'],
            value: this.virtual['first_value'],
            decoration: decoration,
            extra_die_source: dojo.attr(
              this.virtual['extra_die_source'],
              'data-type',
            ),
          },
          this,
          function (result) {
            // What to do after the server call if it succeeded
            // (most of the time: nothing)
          },
          function (is_error) {
            // What to do after the server call in anyway (success or failure)
            // (most of the time: nothing)
          },
        )
      } else {
        this.ajaxcall(
          '/dicehospitaler/dicehospitaler/pass.html',
          {
            lock: true,
            die: this.getSelectedType(),
            decoration: 'screen',
            room_id2: this.virtual['screened_room'],
          },
          this,
          function (result) {
            // What to do after the server call if it succeeded
            // (most of the time: nothing)
          },
          function (is_error) {
            // What to do after the server call in anyway (success or failure)
            // (most of the time: nothing)
          },
        )
      }
    },

    onWrite: function (evt, room_id, screenOK = false) {
      console.log('onWrite')

      //Preventing default browser reaction
      dojo.stopEvent(evt)

      // Check that this action is possible (see "possibleactions" in states.inc.php)
      if (!this.checkAction('play')) {
        return
      }

      //if room is not active, or there is no selected die=> return (should not happen)
      if (
        !dojo.hasClass('hex_' + this.player_id + '_' + room_id, 'active') &&
        !dojo.hasClass('hex_' + this.player_id + '_' + room_id, 'active-screen')
      )
        return false
      if (this.getSelectedDie() == '') return

      let selected_die = this.getSelectedDie()[0]
      if (dojo.hasClass('hex_' + this.player_id + '_' + room_id, 'active-screen')) {
        //if it's just a screen, record the "screen move"
        this.virtual['decoration'] = ''
        this.virtual['screened_room'] = room_id
        //remove other active-screen
        dojo.query('.hex.active-screen').removeClass('active-screen')
        this.displayPossibleMoves()
      } 
      else if (dojo.query('.hex.virtual-screen') != '' && room_id != -1) {
        // else if it's a number AND a screen send it
        this.ajaxcall(
          '/dicehospitaler/dicehospitaler/play.html',
          {
            lock: true,
            die: this.getSelectedType(), //indique quel de a ete choisi
            room_id: room_id, //indique ou il faut marquer le resultat
            value: $('hex_' + this.player_id + '_' + room_id).innerHTML, //indique la valeur a entrer
            decoration: 'screen',
            room_id2: this.virtual['screened_room'],
          },
          this,
          function (result) {
            // What to do after the server call if it succeeded
            // (most of the time: nothing)
          },
          function (is_error) {
            // What to do after the server call in anyway (success or failure)
            // (most of the time: nothing)
          },
        )
      }
      else if (selected_die.id == 'extra_die' || selected_die.id == this.virtual['extra_die_source']) {
        // else if "2 moves needed"
        if (this.virtual['first_move'] == '') {
          //record first move
          let value = $('hex_' + this.player_id + '_' + room_id).innerHTML

          this.virtual['first_room'] = room_id
          this.virtual['first_value'] = value
          this.virtual['first_move'] =
            this.getSelectedType() + ' ' + value + ' ' + selected_die.id // "R 4 extra_die" or "Y 5 Yellow die"
          //and reset modif (which has been used)
          this.virtual['modif'] = 0

          let other_die =
            selected_die.id == 'extra_die'
              ? this.virtual['extra_die_source']
              : 'extra_die'

          dojo.addClass(selected_die.id, 'inactive')
          this.toggleExtraDieSource(other_die)
          this.updateMoves()
        } else {
          //or send 2 moves
          this.ajaxcall(
            '/dicehospitaler/dicehospitaler/play.html',
            {
              lock: true,
              die: this.getSelectedType(), //indique quel de a ete choisi
              room_id: room_id, //indique ou il faut marquer le resultat
              value: $('hex_' + this.player_id + '_' + room_id).innerHTML, //indique la valeur a entrer
              decoration: this.virtual['first_move'],
              room_id2: this.virtual['first_room'],
              extra_die_source: dojo.attr(
                this.virtual['extra_die_source'],
                'data-type',
              ),
            },
            this,
            function (result) {
              // What to do after the server call if it succeeded
              // (most of the time: nothing)
            },
            function (is_error) {
              // What to do after the server call in anyway (success or failure)
              // (most of the time: nothing)
            },
          )
        }
      } 
      else if (this.virtual['decoration'] == 'screen' && !screenOK) {
        //if screen has not be played (ask if it's normal)
        this.confirmationDialog(
          _('You are using screen and number at same place'),
          dojo.hitch(this, function () {
            this.onWrite(evt, room_id, true)
          }),
        )
      }
      else {
        //finally else : normal move
        this.ajaxcall(
          '/dicehospitaler/dicehospitaler/play.html',
          {
            lock: true,
            die: this.getSelectedType(), //indique quel de a ete choisi
            room_id: room_id, //indique ou il faut marquer le resultat
            value: $('hex_' + this.player_id + '_' + room_id).innerHTML, //indique la valeur a entrer
            decoration: this.virtual['decoration'],
          },
          this,
          function (result) {
            // What to do after the server call if it succeeded
            // (most of the time: nothing)
          },
          function (is_error) {
            // What to do after the server call in anyway (success or failure)
            // (most of the time: nothing)
          },
        )
      }
    },

    /* Example:
        
        onMyMethodToCall1: function( evt )
        {
            console.log( 'onMyMethodToCall1' );
            
            // Preventing default browser reaction
            dojo.stopEvent( evt );

            // Check that this action is possible (see "possibleactions" in states.inc.php)
            if( ! this.checkAction( 'myAction' ) )
            {   return; }

            this.ajaxcall( "/dicehospitaler/dicehospitaler/myAction.html", { 
                                                                    lock: true, 
                                                                    myArgument1: arg1, 
                                                                    myArgument2: arg2,
                                                                    ...
                                                                 }, 
                         this, function( result ) {
                            
                            // What to do after the server call if it succeeded
                            // (most of the time: nothing)
                            
                         }, function( is_error) {

                            // What to do after the server call in anyway (success or failure)
                            // (most of the time: nothing)

                         } );        
        },        
        
        */

    ///////////////////////////////////////////////////
    //// Reaction to cometD notifications

    /*
 ██████   █████    ███████    ███████████ █████ ███████████  █████████ 
░░██████ ░░███   ███░░░░░███ ░█░░░███░░░█░░███ ░░███░░░░░░█ ███░░░░░███
 ░███░███ ░███  ███     ░░███░   ░███  ░  ░███  ░███   █ ░ ░███    ░░░ 
 ░███░░███░███ ░███      ░███    ░███     ░███  ░███████   ░░█████████ 
 ░███ ░░██████ ░███      ░███    ░███     ░███  ░███░░░█    ░░░░░░░░███
 ░███  ░░█████ ░░███     ███     ░███     ░███  ░███  ░     ███    ░███
 █████  ░░█████ ░░░███████░      █████    █████ █████      ░░█████████ 
░░░░░    ░░░░░    ░░░░░░░       ░░░░░    ░░░░░ ░░░░░        ░░░░░░░░░  
                                                                       
                                                                       
                                                                       
*/

    /*
            setupNotifications:
            
            In this method, you associate each of your game notifications with your local method to handle it.
            
            Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                  your dicehospitaler.game.php file.
        
        */
    setupNotifications: function () {
      console.log('notifications subscriptions setup')

      // TODO: here, associate your game notifications with local methods

      // Example 1: standard notification handling
      // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );

      // Example 2: standard notification handling + tell the user interface to wait
      //            during 3 seconds after calling the method in order to let the players
      //            see what is happening in the game.
      // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
      // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
      //

      dojo.subscribe('throwDice', this, 'notif_throwDice')

      dojo.subscribe('playerPlayed', this, 'notif_playerPlayed')
      dojo.subscribe('playerPassed', this, 'notif_playerPassed')
      dojo.subscribe('newScreen', this, 'notif_newScreen')
      dojo.subscribe('scoreWards', this, 'notif_scoreWards')
      dojo.subscribe('moreStetos', this, 'notif_moreStetos')
      dojo.subscribe('moreBlood', this, 'notif_moreBlood')

      dojo.subscribe('scoreCritical', this, 'notif_scoreCritical')
      dojo.subscribe('scoreNurses', this, 'notif_scoreNurses')

      dojo.subscribe('scoreCardiologist', this, 'notif_scoreCardiologist')
      dojo.subscribe('scoreRadiologist', this, 'notif_scoreRadiologist')
      dojo.subscribe('scoreEpidemiologist', this, 'notif_scoreEpidemiologist')
    },

    // TODO: from this point and below, you can write your game notifications handling methods

    notif_throwDice: function (notif) {
      console.log('notif_throwDice')

      this.rollDice(notif.args)
      this.displayNewCard(notif.args.active_card)
      this.updateCardsCount(notif.args.cards_count)
      this.useADie()
    },

    notif_scoreWards: function (notif) {
      console.log('notif_scoreWards')
      console.log(notif)

      this.counters[notif.args.player_id]['wards'].incValue(notif.args.bonus)
      this.counters[notif.args.player_id]['total'].incValue(notif.args.bonus)
      this.scoreCtrl[notif.args.player_id].incValue(notif.args.bonus)

      this.writeAWard(
        notif.args.ward_id,
        notif.args.player_id,
        notif.args.bonus,
        1,
      )
    },

    notif_scoreCritical: function (notif) {
      console.log('notif_scoreCritical')
      console.log(notif)

      this.counters[notif.args.player_id]['critical'].incValue(notif.args.value)
      this.counters[notif.args.player_id]['total'].incValue(notif.args.value)
      this.scoreCtrl[notif.args.player_id].incValue(notif.args.value)
    },

    notif_scoreNurses: function (notif) {
      console.log('notif_scoreNurses')
      console.log(notif)

      let old_value = this.counters[notif.args.player_id]['nurses'].getValue()
      let bonus = notif.args.new_score - old_value
      this.counters[notif.args.player_id]['nurses'].toValue(
        notif.args.new_score,
      )
      this.counters[notif.args.player_id]['total'].incValue(bonus)
      this.scoreCtrl[notif.args.player_id].incValue(bonus)

      this.writeAWard(notif.args.ward_id, notif.args.player_id, 0, 0)
    },

    notif_scoreCardiologist: function (notif) {
      console.log('notif_scoreCardiologist')
      console.log(notif)

      this.counters[notif.args.player_id][notif.args.cardiologist_id].incValue(
        notif.args.value,
      )
      this.counters[notif.args.player_id]['total'].incValue(notif.args.value)
      this.scoreCtrl[notif.args.player_id].incValue(notif.args.value)

      //calculate new card
      if (notif.args.new_card < 0) {
        dojo.addClass(notif.args.cardiologist_id, 'used')
        dojo.style(
          notif.args.cardiologist_id,
          'background-position',
          this.getCoordFromCardNb(-notif.args.new_card, true),
        )
        this.addTooltip(
          notif.args.cardiologist_id,
          this.getSpecialistToolTip(notif.args.new_card),
          '',
        )
      } else {
        dojo.style(
          notif.args.cardiologist_id,
          'background-position',
          this.getCoordFromCardNb(notif.args.new_card, true),
        )
        this.addTooltip(
          notif.args.cardiologist_id,
          this.getSpecialistToolTip(notif.args.new_card),
          '',
        )
      }
    },

    notif_scoreRadiologist: function (notif) {
      console.log('notif_scoreRadiologist')
      console.log(notif)

      this.counters[notif.args.player_id]['radiologist'].incValue(
        notif.args.value,
      )
      this.counters[notif.args.player_id]['total'].incValue(notif.args.value)
      this.scoreCtrl[notif.args.player_id].incValue(notif.args.value)
    },

    notif_scoreEpidemiologist: function (notif) {
      console.log('notif_scoreEpidemiologist')
      console.log(notif)

      this.counters[notif.args.player_id]['epidemiologist'].incValue(
        notif.args.value,
      )
      this.counters[notif.args.player_id]['total'].incValue(notif.args.value)
      this.scoreCtrl[notif.args.player_id].incValue(notif.args.value)
      if (notif.args.epidemiologist_rooms)
        this.displayEpidemiologistRooms(
          notif.args.player_id,
          JSON.parse(notif.args.epidemiologist_rooms),
        )
    },

    notif_newScreen: function (notif) {
      console.log('notif_playerPlayed')
      console.log(notif)

      dojo.addClass(
        'hex_' + notif.args.player_id + '_' + notif.args.room_id,
        'screen',
      )
    },

    notif_moreBlood(notif) {
      debug('notif moreBlood', notif)
      if (notif.args.player_id == this.player_id) {
        this.unselectDie()
        this.update()
      }

      this.increase('nb_bloods', notif.args.new_bloods, notif.args.player_id)
      this.updateAllSquares()
    },

    notif_moreStetos(notif) {
      debug('notif moreStetos', notif)
      if (notif.args.player_id == this.player_id) {
        this.unselectDie()
        this.update()
      }

      this.increase('nb_stetos', notif.args.new_stetos, notif.args.player_id)
      this.updateAllSquares()
    },

    notif_playerPassed: function (notif) {
      console.log('notif_playerPlayed')
      console.log(notif)

      if (notif.args.player_id == this.player_id) {
        this.unselectDie()
        this.update()
      }

      this.increase('nb_deads', notif.args.value, notif.args.player_id)
      this.counters[notif.args.player_id]['dead'].incValue(-notif.args.value)
      this.counters[notif.args.player_id]['total'].incValue(-notif.args.value)
      this.scoreCtrl[notif.args.player_id].incValue(-notif.args.value)

      this.updateAllSquares()

      this.useADie(notif.args.used_die)
    },

    notif_playerPlayed: function (notif) {
      console.log('notif_playerPlayed')
      console.log(notif)

      if (notif.args.player_id == this.player_id) {
        this.unselectDie()
        this.update()
      }

      this.writeANumber(
        notif.args.value,
        notif.args.room_id,
        notif.args.player_id,
        notif.args.decoration,
      )

      this.increase('nb_stetos', notif.args.new_stetos, notif.args.player_id)
      this.increase(
        'used_stetos',
        notif.args.new_used_stetos,
        notif.args.player_id,
      )
      this.increase('nb_bloods', notif.args.new_bloods, notif.args.player_id)
      this.increase(
        'used_bloods',
        notif.args.new_used_bloods,
        notif.args.player_id,
      )
      this.increase('nb_nurses', notif.args.new_nurses, notif.args.player_id)

      this.updateAllSquares(notif.args.player_id)

      this.useADie(notif.args.used_die)
    },

    /**
     * UTILS FUNCTIONS
     */

    /*
     * Detect if spectator or replay
     */
    isReadOnly() {
      return (
        this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode
      )
    },

    /*
     * Add a timer on an action button :
     * params:
     *  - buttonId : id of the action button
     *  - time : time before auto click
     *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
     */

    startActionTimer(buttonId, time, pref, autoclick = false) {
      var button = $(buttonId)
      var isReadOnly = this.isReadOnly()
      if (button == null || isReadOnly || pref == 2) {
        debug(
          'Ignoring startActionTimer(' + buttonId + ')',
          'readOnly=' + isReadOnly,
          'prefValue=' + pref,
        )
        return
      }

      // If confirm disabled, click on button
      if (pref == 0) {
        if (autoclick) button.click()
        return
      }

      this._actionTimerLabel = button.innerHTML
      this._actionTimerSeconds = time
      this._actionTimerFunction = () => {
        var button = $(buttonId)
        if (button == null) {
          this.stopActionTimer()
        } else if (this._actionTimerSeconds-- > 1) {
          button.innerHTML =
            this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')'
        } else {
          debug('Timer ' + buttonId + ' execute')
          button.click()
        }
      }
      this._actionTimerFunction()
      this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000)
      debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start')
    },

    stopActionTimer() {
      if (this._actionTimerId != null) {
        debug('Timer #' + this._actionTimerId + ' stop')
        window.clearInterval(this._actionTimerId)
        delete this._actionTimerId
      }
    },
  })
})
