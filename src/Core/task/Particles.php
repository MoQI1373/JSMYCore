<?php

namespace Core\task;

use Core\Loader;
use pocketmine\level\particle\AngryVillagerParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\RainSplashParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\math\Vector3;

class Particles extends Task{

    public function __construct(Loader $plugin){
        $this->plugin = $plugin;
    }

    public function onRun($currentTick)
    {
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $player){
            $plvl = $player->getLevel()->getFolderName();
            $lvl = $this->plugin->getServer()->getDefaultLevel()->getFolderName();

            if ($plvl == $lvl){
                $name = $player->getName();
                $inv = $player->getInventory();

                $players = $player->getLevel()->getPlayers();
                $level = $player->getLevel();

                $x = $player->getX();
                $y = $player->getYaw();
                $z = $player->getZ();

                if(in_array($name, $this->plugin->particle1)) {

                    $center = new Vector3($x, $y+1.0, $z);
                    $particle = new HeartParticle($center);

                    $time = 3;
                    $pi = 3.14159;
                    $time = $time+0.1/$pi;
                    for($i = 0; $i <= 2*$pi; $i+=$pi/8){
                        $x = $time*cos($i) + $center->x;
                        $y = exp(-0.1*$time)*sin($time) + $center->y;
                        $z = $time*sin($i) + $center->z;

                        $particle->setComponents($player->getX(), $player->getY() + 0.3, $player->getZ());
                        $level->addParticle($particle);

                    }
                }

                if(in_array($name, $this->plugin->particle2)) {

                    $center = new Vector3($x, $y+0.5, $z);
                    $particle = new FlameParticle($center);

                    $direction = $player->getDirectionVector();
                    for($i = 0; $i < 40; ++$i){
                        $x = $i*$direction->x+$player->x;
                        $y = $i*$direction->y+$player->y;
                        $z = $i*$direction->z+$player->z;

                        $particle->setComponents($x, $y, $z);
                        $level->addParticle($particle);

                    }
                }

                if(in_array($name, $this->plugin->particle3)) {

                    $center = new Vector3($x, $y, $z);
                    $particle = new FlameParticle($center);
                    $particle1 = new FlameParticle($center);
                    $particle2 = new FlameParticle($center);
                    $particle3 = new FlameParticle($center);
                    $particle4 = new FlameParticle($center);
                    $particle5 = new FlameParticle($center);
                    $particle6 = new FlameParticle($center);
                    $particle7 = new FlameParticle($center);
                    $particle8 = new FlameParticle($center);

                    for($yaw = 0, $y = $center->y; $y < $center->y + 2; $yaw += (M_PI * 2) / 20, $y += 1 / 20){
                        $x = -sin($yaw) + $center->x;
                        $z = cos($yaw) + $center->z;

                        $particle->setComponents($player->getX(), $player->getY() + 0.5, $player->getZ());
                        $level->addParticle($particle);

                        $particle1->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ());
                        $level->addParticle($particle1);

                        $particle2->setComponents($player->getX() - 1, $player->getY() + 0.5, $player->getZ());
                        $level->addParticle($particle2);

                        $particle3->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ() + 1);
                        $level->addParticle($particle3);

                        $particle4->setComponents($player->getX() - 1, $player->getY() + 0.5, $player->getZ() - 1);
                        $level->addParticle($particle4);

                        $particle5->setComponents($player->getX() + 2, $player->getY() + 1.0, $player->getZ() + 2);
                        $level->addParticle($particle5);

                        $particle6->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ() + 2);
                        $level->addParticle($particle6);

                        $particle7->setComponents($player->getX() - 2, $player->getY() + 0.5, $player->getZ() + 1);
                        $level->addParticle($particle7);

                        $particle8->setComponents($player->getX() - 2, $player->getY() + 0.5, $player->getZ() - 2);
                        $level->addParticle($particle8);

                    }
                }

                if(in_array($name, $this->plugin->particle4)) {

                    $center = new Vector3($x, $y, $z);
                    $particle = new AngryVillagerParticle($center);

                    $time = 1;
                    $pi = 3.14159;
                    $time = $time+0.1/$pi;
                    for($i = 0; $i <= 2*$pi; $i+=$pi/8){
                        $x = $time*cos($i) + $player->x;
                        $z = exp(-0.1*$time)*sin($time) + $player->z;
                        $y = $time*sin($i) + $player->y;

                        $particle->setComponents($x, $y + 0.3, $z);
                        $level->addParticle($particle);

                    }
                }

                if(in_array($name, $this->plugin->particle5)) {

                    $center = new Vector3($x, $y, $z);
                    $particle = new WaterDripParticle($center);

                    $time = 1;
                    $pi = 3.14159;
                    $time = $time+0.1/$pi;
                    for($i = 0; $i <= 2*$pi; $i+=$pi/8){
                        $x = $time*cos($i) + $center->x;
                        $y = exp(-0.1*$time)*sin($time) + $center->y;
                        $z = $time*sin($i) + $center->z;

                        $particle->setComponents($player->getX(), $player->getY() + 2.5, $player->getZ());
                        $level->addParticle($particle);

                    }
                }

                if (in_array($name, $this->plugin->particle6)){

                    $center = new Vector3($x, $y, $z);
                    $particle = new RedstoneParticle($center);
                    $particle1 = new RedstoneParticle($center);
                    $particle2 = new RedstoneParticle($center);
                    $particle3 = new RedstoneParticle($center);
                    $particle4 = new RedstoneParticle($center);
                    $particle5 = new RedstoneParticle($center);
                    $particle6 = new RedstoneParticle($center);
                    $particle7 = new RedstoneParticle($center);
                    $particle8 = new RedstoneParticle($center);

                    $particle->setComponents($player->getX(), $player->getY() + 0.5, $player->getZ());
                    $level->addParticle($particle);

                    $particle1->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ());
                    $level->addParticle($particle1);

                    $particle2->setComponents($player->getX() - 1, $player->getY() + 0.5, $player->getZ());
                    $level->addParticle($particle2);

                    $particle3->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ() + 1);
                    $level->addParticle($particle3);

                    $particle4->setComponents($player->getX() - 1, $player->getY() + 0.5, $player->getZ() - 1);
                    $level->addParticle($particle4);

                    $particle5->setComponents($player->getX() + 2, $player->getY() + 1.0, $player->getZ() + 2);
                    $level->addParticle($particle5);

                    $particle6->setComponents($player->getX() + 1, $player->getY() + 0.5, $player->getZ() + 2);
                    $level->addParticle($particle6);

                    $particle7->setComponents($player->getX() - 2, $player->getY() + 0.5, $player->getZ() + 1);
                    $level->addParticle($particle7);

                    $particle8->setComponents($player->getX() - 2, $player->getY() + 0.5, $player->getZ() - 2);
                    $level->addParticle($particle8);
                }
            }
        }
    }
}
