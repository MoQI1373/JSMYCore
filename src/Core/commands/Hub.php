<?php

namespace Core\commands;

use pocketmine\command\Command;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use Scoreboards\Scoreboards;
use pocketmine\utils\TextFormat as TF;
use pocketmine\command\PluginCommand;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;

use Core\Loader;
use Core\utils\Scoreboard;

class Hub extends Command
{
	
	private $plugin;
	
	public function __construct(Loader $plugin)
	{
		parent::__construct("hub", "Back to lobby");
		$this->plugin = $plugin;
	}
	
	public function execute(CommandSender $d, $commandLabel, array $args)
	{
		if($d instanceof Player){
			$d->getInventory()->clearAll();
		    $d->getArmorInventory()->clearAll();
		    $d->removeAllEffects();
		    $d->setGamemode(2);
		    $d->setHealth(20);
		    $d->setMaxHealth(20);
		    $d->setFood(20);
		    $d->setScale(1);
		    $d->setAllowFlight(false);
		    $d->teleport($this->plugin->getServer()->getDefaultLevel()->getSafeSpawn());
		    $d->getInventory()->setHeldItemIndex(1);

            $d->setDisplayName(TF::GRAY . TF::BOLD . "[" . TF::RESET . $this->plugin->getPlayerRank($d) . TF::GRAY . TF::BOLD . "]" . TF::RESET . " " . $d->getName());

            $this->plugin->bossbar->addPlayer($d);

            $d->getInventory()->setItem(0, ItemFactory::get(ItemIds::COMPASS, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Travel"));

            $d->getInventory()->setItem(3, ItemFactory::get(ItemIds::FEATHER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Super Jump"));
            $d->getInventory()->setItem(4, ItemFactory::get(ItemIds::ENCHANTED_BOOK, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Social"));

            $d->getInventory()->setItem(5, ItemFactory::get(ItemIds::SLIME_BALL, 3, 1)->setCustomName(TF::BOLD . TF::AQUA . "Report"));

            $d->getInventory()->setItem(6, ItemFactory::get(ItemIds::RED_FLOWER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Cosmetics"));

            $d->getInventory()->setItem(7, ItemFactory::get(ItemIds::PAPER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Statistics"));

            $d->getInventory()->setItem(8, ItemFactory::get(ItemIds::BLAZE_ROD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Settings"));

            if ($d->hasPermission("hyperiummc.staff")) {
                $d->getInventory()->setItem(1, ItemFactory::get(ItemIds::EMERALD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Staff Tools"));
            }
            $scoreboard = Scoreboards::getInstance();
            $scoreboard->remove($d);

		}
	}
}

