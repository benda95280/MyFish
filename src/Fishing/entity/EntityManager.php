<?php

declare(strict_types = 1);

namespace Fishing\entity;


use Fishing\entity\projectile\FishingHook;
use Fishing\Fishing;
use pocketmine\entity\Entity;

class EntityManager extends Entity {
	public static function init(): void{
		// Projectiles ////
		self::registerEntity(FishingHook::class, true, ['FishingHook', 'minecraft:fishinghook']);
	}
}
