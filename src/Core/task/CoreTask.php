<?php

declare(strict_types=1);

namespace Core\task;

use pocketmine\scheduler\Task;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use pocketmine\Player;

use Core\Loader;

class CoreTask extends Task
{

    public function __construct(Loader $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player) {
            if ($player->hasPermission("hyperiummc.staff")) {
                $particle1 = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                $particle2 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);
                $particle3 = new Config($this->plugin->getDataFolder() . "particles/Flame.yml", Config::YAML);
                $particle4 = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                $particle5 = new Config($this->plugin->getDataFolder() . "particles/WaterDrip.yml", Config::YAML);
                $particle6 = new Config($this->plugin->getDataFolder() . "particles/Redstone.yml", Config::YAML);

                if (!$particle1->exists($player->getName())) {
                    $particle1->set($player->getName(), 1);
                    $particle1->save();
                }
                if (!$particle2->exists($player->getName())) {
                    $particle2->set($player->getName(), 1);
                    $particle2->save();
                }
                if (!$particle3->exists($player->getName())) {
                    $particle3->set($player->getName(), 1);
                    $particle3->save();
                }
                if (!$particle4->exists($player->getName())) {
                    $particle4->set($player->getName(), 1);
                    $particle4->save();
                }
                if (!$particle5->exists($player->getName())) {
                    $particle5->set($player->getName(), 1);
                    $particle5->save();
                }
                if (!$particle6->exists($player->getName())){
                    $particle6->set($player->getName(), 1);
                    $particle6->save();
                }
            } elseif ($player->hasPermission("hyperiummc.hyper")) {
                $particle1 = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                $particle2 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);

                if (!$particle1->exists($player->getName())) {
                    $particle1->set($player->getName(), 1);
                    $particle1->save();
                }
                if (!$particle2->exists($player->getName())) {
                    $particle2->set($player->getName(), 1);
                    $particle2->save();
                }
            } elseif ($player->hasPermission("hyperiummc.prime")) {
                $particle1 = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);

                if (!$particle1->exists($player->getName())) {
                    $particle1->set($player->getName(), 1);
                    $particle1->save();
                }
            } elseif ($player->hasPermission("hyperiummc.titan")) {
                $particle1 = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                $particle2 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);
                $particle3 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);
                $particle4 = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);

                if (!$particle1->exists($player->getName())) {
                    $particle1->set($player->getName(), 1);
                    $particle1->save();
                }
                if (!$particle2->exists($player->getName())) {
                    $particle2->set($player->getName(), 1);
                    $particle2->save();
                }
                if (!$particle3->exists($player->getName())) {
                    $particle3->set($player->getName(), 1);
                    $particle3->save();
                }
                if (!$particle4->exists($player->getName())) {
                    $particle4->set($player->getName(), 1);
                    $particle4->save();
                }
            } elseif ($player->hasPermission("hyperiummc.naga")) {
                $particle1 = new Config($this->plugin->getDataFolder() . "particles/Heart.yml", Config::YAML);
                $particle2 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);
                $particle3 = new Config($this->plugin->getDataFolder() . "particles/Laser.yml", Config::YAML);
                $particle4 = new Config($this->plugin->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                $particle5 = new Config($this->plugin->getDataFolder() . "particles/WaterDrip.yml", Config::YAML);
                $particle6 = new Config($this->plugin->getDataFolder() . "particles/Redstone.yml", Config::YAML);


                if (!$particle1->exists($player->getName())) {
                    $particle1->set($player->getName(), 1);
                    $particle1->save();
                }
                if (!$particle2->exists($player->getName())) {
                    $particle2->set($player->getName(), 1);
                    $particle2->save();
                }
                if (!$particle3->exists($player->getName())) {
                    $particle3->set($player->getName(), 1);
                    $particle3->save();
                }
                if (!$particle4->exists($player->getName())) {
                    $particle4->set($player->getName(), 1);
                    $particle4->save();
                }
                if (!$particle5->exists($player->getName())) {
                    $particle5->set($player->getName(), 1);
                    $particle5->save();
                }
                if (!$particle6->exists($player->getName())){
                    $particle6->set($player->getName(), 1);
                    $particle6->save();
                }
            }
        }
    }
}