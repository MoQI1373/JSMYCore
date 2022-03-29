<?php

namespace Core\task;

use Core\Loader;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class SettingsTask extends Task{

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getLevel() === $this->plugin->getServer()->getDefaultLevel()){

                $showPlayer = new Config($this->plugin->getDataFolder() . "settings/showPlayer.yml", Config::YAML);

                if ($showPlayer->exists($player->getName())){
                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $players){
                        $player->hidePlayer($players);
                    }
                } else {
                    foreach ($this->plugin->getServer()->getOnlinePlayers() as $players){
                        $player->showPlayer($players);
                    }
                }
            }
        }
    }
}
