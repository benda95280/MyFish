<?php

declare(strict_types = 1);

namespace Fishing;

use Fishing\entity\projectile\FishingHook;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use pocketmine\Server as PMServer;

class Session {
	/** @var bool */
	public $fishing = false;
	/** @var null | FishingHook */
	public $fishingHook = null;
	/** @var array */
	public $clientData = [];
	/** @var Player */
	private $player;

	public function __construct(Player $player){
		$this->player = $player;
	}

	public function __destruct(){
		$this->unsetFishing();
	}

	public function unsetFishing(){
		$this->fishing = false;

		if($this->fishingHook instanceof FishingHook){
			$this->fishingHook->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_TEASE, null, $this->fishingHook->getViewers());

			if(!$this->fishingHook->isFlaggedForDespawn()){
				$this->fishingHook->flagForDespawn();
			}

			$this->fishingHook = null;
		}
	}

	public function getPlayer(): Player{
		return $this->player;
	}

	public function getServer(): PMServer{
		return $this->player->getServer();
	}


}
