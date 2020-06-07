<?php


declare(strict_types = 1);

namespace Fishing\item;

use Fishing\Fishing;
use pocketmine\item\{
	Item, ItemFactory
};

class ItemManager {
	public static function init(){
		ItemFactory::registerItem(new FishingRod(), true);
		Item::initCreativeItems();
	}
}
