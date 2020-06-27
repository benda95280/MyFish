<?php


declare(strict_types = 1);

namespace Fishing\utils;

use Fishing\Fishing;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\network\mcpe\protocol\LevelEventPacket;


class FishingLevel {

	/** @var Config */
	private static $levelFile;

	public static function init(): void{
		//Load level file
		self::$levelFile = new Config(Fishing::getInstance()->getDataFolder() . "level.yml", Config::YAML);	
		
		//Save To File Task every minute
		Fishing::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends \pocketmine\scheduler\Task{
			  public function onRun(int $currentTick) : void{
				 FishingLevel::saveConfig();
			  }
		}, 1200);
		
	}
	
	public static function getFishingExp(Player $player):int {
		$playerId = trim(strtolower($player->getName()));
		return self::$levelFile->get($playerId,0);
	}
	
	public static function addFishingExp(int $exp, Player $player):void {
		$previousLevel = self::getFishingLevel($player);
		$playerId = trim(strtolower($player->getName()));
		$currentExp = self::$levelFile->get($playerId,0);
		self::$levelFile->set($playerId, $currentExp + $exp);
		if ($previousLevel != self::getFishingLevel($player) ) {
			$player->sendTip("! Fishing LVL Up !");
			$te = new LevelEventPacket();
			$te->evid = LevelEventPacket::EVENT_SOUND_TOTEM;
			$te->position = $player->add(0, $player->eyeHeight, 0);
			$te->data = 0;
			$player->dataPacket($te);				
		}
	}

	public static function getFishingLevel(Player $player):int {
		$playerId = trim(strtolower($player->getName()));
		$currentExp = self::$levelFile->get($playerId,0);
		
		$scale = 2;
		$level = intval(floor(pow($currentExp/100,1/$scale)));
		if ($level > 10) $level = 10;
		return $level;
	}
	
	public static function saveConfig() {
		self::$levelFile->save();
	}
	
}