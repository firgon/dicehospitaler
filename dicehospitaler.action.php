<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * DiceHospitalER implementation : © <firgon> <emmanuel.albisser@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * dicehospitaler.action.php
 *
 * DiceHospitalER main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/dicehospitaler/dicehospitaler/myAction.html", ...)
 *
 */
  
  
  class action_dicehospitaler extends APP_GameAction
  { 
    // Constructor: please do not modify
   	public function __default()
  	{
  	    if( self::isArg( 'notifwindow') )
  	    {
            $this->view = "common_notifwindow";
  	        $this->viewArgs['table'] = self::getArg( "table", AT_posint, true );
  	    }
  	    else
  	    {
            $this->view = "dicehospitaler_dicehospitaler";
            self::trace( "Complete reinitialization of board game" );
      }
  	} 
  	
  	// TODO: defines your action entry points there
    
    public function play()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $die = self::getArg( "die", AT_alphanum, true );
        $room_id = self::getArg( "room_id", AT_posint, true );
        $value = self::getArg( "value", AT_posint, true );
        $decoration = self::getArg( "decoration", AT_alphanum, true );
        $room_id2 = self::getArg( "room_id2", AT_posint, false, null);
        $extra_die_source = self::getArg( "extra_die_source", AT_alphanum, false, '' );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        if ($room_id2==null && $extra_die_source==null){
            $this->game->play( $die, $room_id, $value, $decoration, true );
        } else {
           if ($decoration==SCREEN) {
                $this->game->screen($die, $room_id2);
                $this->game->play( $die, $room_id, $value, '', true );
            } else {
                $this->game->playTwice($die, $room_id, $value, $decoration, $room_id2, $extra_die_source);
            }

        }
        self::ajaxResponse( );
    }

    public function pass()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $die = self::getArg( "die", AT_alphanum, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->pass($die);

        self::ajaxResponse( );
    }

    /*
    
    Example:
  	
    public function myAction()
    {
        self::setAjaxMode();     

        // Retrieve arguments
        // Note: these arguments correspond to what has been sent through the javascript "ajaxcall" method
        $arg1 = self::getArg( "myArgument1", AT_posint, true );
        $arg2 = self::getArg( "myArgument2", AT_posint, true );

        // Then, call the appropriate method in your game logic, like "playCard" or "myAction"
        $this->game->myAction( $arg1, $arg2 );

        self::ajaxResponse( );
    }
    
    */

  }
  

