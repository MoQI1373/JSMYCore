<?php

namespace Core\task;

use pocketmine\utils\Config;
use Scoreboards\Scoreboards;
use Core\Loader;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat as TF;

class LobbyTask extends Task{

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            if ($player->getLevel()->getFolderName() == $this->plugin->getServer()->getDefaultLevel()->getFolderName()){

                $scoreboard = Scoreboards::getInstance();
                $name = $player->getName();
                $api = $this->plugin->getServer()->getPluginManager()->getPlugin("ZenAPI");
                $economyapi = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                $online = count($this->plugin->getServer()->getOnlinePlayers());

                $scoreboard->new($player, 'lobby', "§l§eJSMY Network");
                $scoreboard->setLine($player, 1, "§r ");
                $scoreboard->setLine($player, 2, "§bName: " . TF::YELLOW . $name);
                $scoreboard->setLine($player, 3, "§bLevel: " . TF::YELLOW . $api->getLevel($player));
                $scoreboard->setLine($player, 4, "§b§r  ");
                $scoreboard->setLine($player, 5, "§bRank: " . TF::YELLOW . $this->plugin->getPlayerRank($player));
                $scoreboard->setLine($player, 6, "§r");
                $scoreboard->setLine($player, 7, "§bCoins: " . TF::YELLOW . $economyapi->myMoney($player));
                $scoreboard->setLine($player, 8, "§bPing: " . TF::YELLOW . $player->getPing());
                $scoreboard->setLine($player, 9, "           ");
                $scoreboard->setLine($player, 10, "§bPlayers: " . TF::YELLOW . $online);
                $scoreboard->setLine($player, 11, "          ");
                $scoreboard->setLine($player, 12, " " . TF::YELLOW . "play.jsmynetwork.my.to");
            }
            $slowdown = new Config($this->plugin->getDataFolder() . "slowdown.yml", Config::YAML);

            if ($slowdown->get($player->getName()) == 5){
                $slowdown->set($player->getName(), 4);
                $slowdown->save();
            } elseif ($slowdown->get($player->getName()) == 4){
                $slowdown->set($player->getName(), 3);
                $slowdown->save();
            } elseif ($slowdown->get($player->getName()) == 3){
                $slowdown->set($player->getName(), 2);
                $slowdown->save();
            } elseif ($slowdown->get($player->getName()) == 2){
                $slowdown->set($player->getName(), 1);
                $slowdown->save();
            } elseif ($slowdown->get($player->getName()) == 1){
                $slowdown->remove($player->getName());
                $slowdown->save();
            }
        }
    }
}
