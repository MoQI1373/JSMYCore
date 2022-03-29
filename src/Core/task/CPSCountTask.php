<?php

namespace Core\task;

use Core\Loader;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class CPSCountTask extends Task{

    function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            $cpscount = new Config($this->plugin->getDataFolder() . "settings/cpsCounter.yml", Config::YAML);
            if ($cpscount->exists($player->getName())){
                $player->sendPopup(TextFormat::AQUA . "CPS: " . TextFormat::GOLD . $this->plugin->getCps($player));
            }
        }
    }
}
