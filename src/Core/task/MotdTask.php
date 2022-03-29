<?php

namespace Core\task;

use Core\Loader;
use pocketmine\scheduler\Task;

class MotdTask extends Task{

    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun(int $currentTick)
    {
        $motd = array("§l§bJSMY Network", "§l§aNEW §r§cFFA, §bSkywars", "§l§eNEW §bAutoSprint");

        $this->plugin->getServer()->getNetwork()->setName($motd[array_rand($motd)]);
    }
}
