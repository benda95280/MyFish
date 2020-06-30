<?php

declare(strict_types = 1);

namespace Fishing\entity\projectile;

use Fishing\Fishing;
use Fishing\Session;
use pocketmine\block\StillWater;
use pocketmine\block\Water;
use pocketmine\block\Liquid;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\Player;
use pocketmine\level\particle\GenericParticle;
use pocketmine\level\particle\Particle;
use pocketmine\Server as PMServer;

class FishingHook extends Projectile {

	public const NETWORK_ID = self::FISHING_HOOK;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	public $coughtTimer = 0;
	public $bubbleTimer = 0;
	public $bubbleTicks = 0;
	public $bitesTicks = 0;
	public $attractTimer = 0;
	protected $gravity = 0.1;
	protected $drag = 0.05;
	protected $touchedWater = false;

	

	public function onUpdate(int $currentTick): bool{
		if($this->isFlaggedForDespawn() || !$this->isAlive()){
			return false;
		}
		
		$oe = $this->getOwningEntity();
		
		//Remove if Owner is null
		if ($oe === null) {
			if(!$this->isFlaggedForDespawn()){
				$this->flagForDespawn();
			}			
		}
			
		//Remove if Owner too far
		if($oe instanceof Player){
			if ($this->getPosition()->distance($oe->getPosition()) > 25) {
				$session = Fishing::getInstance()->getSessionById($oe->getId());
				if($session instanceof Session){
					$session->unsetFishing();
				}	
			}
		}

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
		
		if($this->isInsideOfSolid()){
			$random = new Random((int) (microtime(true) * 1000) + mt_rand());
			$this->motion->x *= $random->nextFloat() * 0.2;
			$this->motion->y *= $random->nextFloat() * 0.2;
			$this->motion->z *= $random->nextFloat() * 0.2;
		}		
		
		if (!$this->isInsideOfSolid()) {
			$f6 = 0.92;

			if($this->onGround or $this->isCollidedHorizontally){
				$f6 = 0.5;
			}
			
			$d10 = 0;
			$bb = $this->getBoundingBox();
			for($j = 0; $j < 5; ++$j){
				$d1 = $bb->minY + ($bb->maxY - $bb->minY) * $j / 5;
				$d3 = $bb->minY + ($bb->maxY - $bb->minY) * ($j + 1) / 5;
				$bb2 = new AxisAlignedBB($bb->minX, $d1, $bb->minZ, $bb->maxX, $d3, $bb->maxZ);
				if($this->isLiquidInBoundingBox($bb2)){
					$d10 += 0.2;
				}
			}

			if ($d10 > 0) {	
				//Little annimation floating
				if ($currentTick % 60 === 0) $this->motion->y =-0.02;
				//Wait Wait, we are waiting the fish
				if($this->attractTimer === 0){
					//Set bubble timer, fish is near !
					if ($this->bubbleTimer === 0 && $this->coughtTimer === 0) {
						$this->bubbleTimer = mt_rand(5, 10) * 20;
					}
					else if ($this->bubbleTimer > 0) {
						$this->bubbleTimer--;
					}
					
					//If bubble timer finished, catch it !
					if ($this->bubbleTimer <= 0 && $this->coughtTimer === 0) {
						$this->coughtTimer = mt_rand(3, 5) * 20;
						if($oe instanceof Player){
							$oe->sendTip("Il y a un poisson !");
						}
						$this->fishBites();
						$this->bitesTicks = mt_rand(1, 3) * 20;
					}
					//Else do animation every X ticks
					else {
						if ($this->bubbleTicks === 0) {
							$this->attractFish();
							$this->bubbleTicks = 10;
						}
						else {
							$this->bubbleTicks--;
						}
						
					}
				}
				elseif($this->attractTimer > 0){
					$this->attractTimer--;
				}
				
				if($this->coughtTimer > 0){
					$this->coughtTimer--;
					if ($this->bitesTicks === 0) {
						$this->fishBites();
						$this->bitesTicks = mt_rand(1, 3) * 20;
					}
					else {
						$this->bitesTicks--;
					}
	
					//Too late, fish has gone, reset timer
					if ($this->coughtTimer === 0)
					{
						$oe->sendTip("Trop tard, il est parti ...");
						$this->attractTimer = mt_rand(30, 100) * 20;
					}
				}
				
			}
			$d11 = $d10 * 2.0 - 1.0;
			
			$this->motion->y += 0.04 * $d11;
				if($d10 > 0.0){
				$f6 = $f6 * 0.9;
				$this->motion->y *= 0.8;
			}		
			
			$this->motion->x *= $f6;
			$this->motion->y *= $f6;
			$this->motion->z *= $f6;
		}
		// var_dump("attractTimer: ".$this->attractTimer." coughtTimer: ".$this->coughtTimer." bubbleTimer: ".$this->bubbleTimer);
		$this->timings->stopTiming();

		return $hasUpdate;
	}

	public function attractFish(){
		$oe = $this->getOwningEntity();
		if($oe instanceof Player){
			$this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_BUBBLE);
		}
		$this->level->addParticle(new GenericParticle(new Vector3($this->x, $this->y - 0.1, $this->z), Particle::TYPE_BUBBLE));
	}

	public function fishBites(){
		$oe = $this->getOwningEntity();
		if($oe instanceof Player){
			$this->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_HOOK);
		}
		$this->motion->y =-0.08;
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
		$this->server->getPluginManager()->callEvent(new ProjectileHitEntityEvent($this, $hitResult, $entityHit));

		$damage = $this->getResultDamage();

		if($this->getOwningEntity() === null){
			$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}else{
			$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}

		$entityHit->attack($ev);

		$entityHit->setMotion($this->getOwningEntity()->getDirectionVector()->multiply(-0.3)->add(0, 0.3, 0));

		$this->isCollided = true;
		$this->flagForDespawn();
	}

	public function getResultDamage(): int{
		return 1;
	}
	
	/**
	 * @param AxisAlignedBB $bb
	 * @param Liquid        $material
	 *
	 * @return bool
	 */
	public function isLiquidInBoundingBox(AxisAlignedBB $bb) : bool{
		$minX = (int) floor($bb->minX);
		$minY = (int) floor($bb->minY);
		$minZ = (int) floor($bb->minZ);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		for($x = $minX; $x < $maxX; ++$x){
			for($y = $minY; $y < $maxY; ++$y){
				for($z = $minZ; $z < $maxZ; ++$z){
					$block = $this->level->getBlockAt($x, $y, $z);

					if($block instanceof Liquid){
						$j2 = $block->getDamage();
						$d0 = $y + 1;

						if($j2 < 8){
							$d0 -= $j2 / 8;
						}

						if($d0 >= $bb->minY){
							return true;
						}
					}
				}
			}
		}

		return false;
	}
	
	protected function tryChangeMovement() : void{
	}

}
