<?php

use SwHawk\BgaPhpUnit\BGATestCase;

function clienttranslate($text){
	return $text;
}

class test extends BGATestCase{

	protected $gameClassName = DiceHospitalER::class;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		\Table::$projectDir = __DIR__."/../src/";
		parent::__construct($name, $data, $dataName);
	}

	public function test1(){
		//given
		$roomId = 200;

		//when + then
		$this->assertTrue($this->game->isEvenRow($roomId));
	}

	public function testGetGameProgression() {
		//Given 
		$cardNumber = 12;
		$this->game->cards->expects($this->once())
		    ->method('countCardInLocation')
			->with('deck')
			->will($this->returnValue($cardNumber));

		$this->gamestate->expect($this->once())
			->method('nextState')
			->with('transition');

		$this->expectGamestateTransition('transition');

		// When + Then
		$this->assertEqualDelta($this->game->getGameProgression(), 50.0);
	}
}