<?php


declare(strict_types = 1);

// FYI: Event Priorities work this way: LOWEST -> LOW -> NORMAL -> HIGH -> HIGHEST -> MONITOR

namespace Fishing;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\player\{PlayerItemHeldEvent, PlayerLoginEvent, PlayerQuitEvent, PlayerDeathEvent};
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\network\mcpe\protocol\PlayStatusPacket;
use pocketmine\plugin\Plugin;	
use pocketmine\Server as PMServer;

class EventListener implements Listener {

	/** @var Plugin */
	public $plugin;

	public function __construct(Plugin $plugin){
		$this->plugin = $plugin;
	}

	/**
	 * @param PlayerItemHeldEvent $ev
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onItemHeld(PlayerItemHeldEvent $ev){
		$session = Fishing::getInstance()->getSessionById($ev->getPlayer()->getId());
		if($session instanceof Session){
			if($session->fishing){
				$inventory = $ev->getPlayer()->getInventory();
				$oldItem = $inventory->getItemInHand();
				$newItem = $ev->getItem();
				if ($oldItem !== $newItem) {
					$session->unsetFishing();					
				}
			}
		}
	}
	
	public function onDeath(PlayerDeathEvent $ev) {
		$session = Fishing::getInstance()->getSessionById($ev->getPlayer()->getId());
		if($session instanceof Session){
			if($session->fishing){
				$session->unsetFishing();	
			}					

		}		
	}

	
	/**
	 * @param PlayerLoginEvent $ev
	 *
	 * @priority LOWEST
	 */
	public function onLogin(PlayerLoginEvent $ev){
		Fishing::getInstance()->createSession($ev->getPlayer());
	}
	
	/**
	 * @param PlayerQuitEvent $ev
	 *
	 * @priority LOWEST
	 */	
	public function onLeave(PlayerQuitEvent $ev){
		Fishing::getInstance()->destroySession($ev->getPlayer());
	}
}