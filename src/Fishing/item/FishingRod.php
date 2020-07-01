<?php


declare(strict_types = 1);

namespace Fishing\item;

use Fishing\entity\projectile\FishingHook;
use Fishing\Fishing;
use Fishing\Session;
use Fishing\utils\FishingLevel;
use Fishing\utils\FishingLootTable;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Tool;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\level\Level;
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;


class FishingRod extends Tool {
	public function __construct($meta = 0){
		parent::__construct(Item::FISHING_ROD, $meta, "Fishing Rod");
	}

	public function getMaxStackSize(): int{
		return 1;
	}

	public function getMaxDurability(): int{
		return 355; // TODO: Know why it breaks early at 65
	}

	public function onClickAir(Player $player, Vector3 $directionVector): bool{
			$session = Fishing::getInstance()->getSessionById($player->getId());
			if($session instanceof Session){
				$playerFishingLevel = FishingLevel::getFishingLevel($player);
				if(!$session->fishing) {
					//Cannot fish at night when under level 3
					$time = $player->getLevel()->getTimeOfDay();
					if (($time < Level::TIME_SUNSET || $time > Level::TIME_SUNRISE) && $playerFishingLevel < 3) {
						$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->yaw, $player->pitch);

						/** @var FishingHook $projectile */
						$projectile = Entity::createEntity($this->getProjectileEntityType(), $player->getLevel(), $nbt, $player);
						if($projectile !== null){
							//Level impact ThrowForce
							$throwForce = $this->getThrowForce();
							
							$throwForcePercent = $playerFishingLevel*6-36;
							$throwForceToAdd = ($throwForce / 100) * $throwForcePercent ;
							$throwForce = $throwForce + $throwForceToAdd;
							$projectile->setMotion($projectile->getMotion()->multiply($throwForce));
						}
						
						//change the location where sending hook projectile

							$degreeToRand = 35/$playerFishingLevel;
							$randomRotation = floor(rand()/getrandmax()*($degreeToRand*2)-$degreeToRand) ;
							$randomRotationRadian = $randomRotation * (M_PI/180);
							$hookMotion = $projectile->getMotion();
							$theta = deg2rad($randomRotation);
							$cos = cos($theta);
							$sin = sin($theta);
							$px = $hookMotion->x * $cos - $hookMotion->z * $sin; 
							$pz = $hookMotion->x * $sin + $hookMotion->z * $cos;
							$projectile->setMotion(new Vector3($px, $hookMotion->y, $pz));

						if($projectile instanceof Projectile){
							$player->getServer()->getPluginManager()->callEvent($projectileEv = new ProjectileLaunchEvent($projectile));
							if($projectileEv->isCancelled()){
								$projectile->flagForDespawn();
							}else{
								$projectile->spawnToAll();
								$player->getLevel()->addSound(new LaunchSound($player), $player->getViewers());
							}
						}

						//Todo: Wait weather support
						// $weather = Fishing::$weatherData[$player->getLevel()->getId()];
						// if(($weather->isRainy() || $weather->isRainyThunder())){
							// $rand = mt_rand(15, 50);
						// }else{
							$rand = mt_rand(30, 100);
						// }
						if($this->hasEnchantments()){
							foreach($this->getEnchantments() as $enchantment){
								switch($enchantment->getId()){
									case Enchantment::LURE:
										$divisor = $enchantment->getLevel() * 0.50;
										$rand = intval(round($rand / $divisor)) + 3;
										break;
								}
							}
						}

						$projectile->baseTimer = $rand * 20;

						$session->fishingHook = $projectile;
						$session->fishing = true;
					}
					else {
						$player->sendTip(Fishing::getInstance()->lang["lvltoolownight"]);
					}
				}else{
					$projectile = $session->fishingHook;
					if($projectile instanceof FishingHook){
						$session->unsetFishing();

						if($player->getLevel()->getBlock($projectile->asVector3())->getId() == Block::WATER || $player->getLevel()->getBlock($projectile)->getId() == Block::WATER){
							$damage = 5;
						}else{
							$damage = mt_rand(10, 15); // TODO: Implement entity / block collision properly
						}

						$this->applyDamage($damage);

						if($projectile->coughtTimer > 0){
							//$weather = Fishing::$weatherData[$player->getLevel()->getId()];
							$lvl = 0;
							if($this->hasEnchantments()){
								if($this->hasEnchantment(Enchantment::LUCK_OF_THE_SEA)){
									$lvl = $this->getEnchantment(Enchantment::LUCK_OF_THE_SEA)->getLevel();
								}
							}
						//	if(($weather->isRainy() || $weather->isRainyThunder()) && $lvl == 0){
						//		$lvl = 2;
						//	}else{
						//		$lvl = 0;
						//	}
							//Level of player impact chance to catch something
							//Level of Enchant LUCK_OF_THE_SEA impact chance too
							 if (mt_rand($playerFishingLevel, intval(round(11+$lvl+sqrt($playerFishingLevel+2)*2))) <= round(2+$lvl+sqrt($playerFishingLevel)*4.4)) {
								$item = FishingLootTable::getRandom($lvl);
								if($item->getId() === 349 OR $item->getId() === 460) {
									$size = round(5 * $playerFishingLevel * (($lvl+2)/3) * (((-1/15)*$projectile->lightLevelAtHook)+2));
									$item->setCustomBlockData(new CompoundTag("", [
										new StringTag("FishSize", strval($size))
									]))->setLore(array(Fishing::getInstance()->lang["fishsize"].": ".$size." cm"));
								}
								$player->getInventory()->addItem($item);
								FishingLevel::addFishingExp(mt_rand(3, 6), $player);
								$player->addXp(mt_rand(2, 4), false);
							}
							else {
								FishingLevel::addFishingExp(mt_rand(1, 3), $player);
								$player->addXp(mt_rand(1, 2),false);
								$player->knockBack($projectile, 0, $player->x - $projectile->x, $player->z - $projectile->z, (0.3/$playerFishingLevel));
								$player->sendTip(Fishing::getInstance()->lang["fishhasgoneaway"]);
							}
							

						}
					}
				}
			}

		return true;
	}

	public function getProjectileEntityType(): string{
		return "FishingHook";
	}

	public function getThrowForce(): float{
		return 1.6;
	}
}