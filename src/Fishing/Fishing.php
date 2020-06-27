<?php

/*
Bend95280 do not know what should i put here
*/

namespace Fishing;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use Fishing\utils\FishingLootTable;
use Fishing\utils\FishingLevel;
use Fishing\entity\EntityManager;
use Fishing\item\ItemManager;

class Fishing extends PluginBase {
	
	/** @var Fishing */
	private static $instance = null;
	/** @var Session[] */
	private $sessions = [];
	/** @var Config */
	public static $cacheFile;
	public static $levelFile;
	
	public static $randomFishingLootTables = true;
	public static $registerVanillaEnchantments = true;

	public static function getInstance(): Fishing{
		return self::$instance;
	}
	
	public function onLoad(){
	    if(!self::$instance instanceof Fishing){
	        self::$instance = $this;
	    }
		self::$cacheFile = new Config($this->getDataFolder() . "cache.json", Config::JSON);
	}
	
    public function onEnable(){
		FishingLootTable::init();
		FishingLevel::init();
		ItemManager::init();
		EntityManager::init();
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}
	
	public function getSessionById(int $id){
		if(isset($this->sessions[$id])){
			return $this->sessions[$id];
		}else{
			return null;
		}
	}	
	
	public function createSession(Player $player): bool{
		if(!isset($this->sessions[$player->getId()])){
			$this->sessions[$player->getId()] = new Session($player);
			$this->getLogger()->debug("Created " . $player->getName() . "'s Session");

			return true;
		}

		return false;
	}	
	
	public function destroySession(Player $player): bool{
		if(isset($this->sessions[$player->getId()])){
			unset($this->sessions[$player->getId()]);
			$this->getLogger()->debug("Destroyed " . $player->getName() . "'s Session");

			return true;
		}

		return false;
	}
}
