<?php

namespace Core\commands;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\utils\{Config, TextFormat as TE};

use Core\Loader;

class Staffchat extends Command {

    private $plugin;

    public function __construct(Loader $plugin){
        parent::__construct("staffchat", "staffchat command");
        $this->setDescription("send message to staff");
        $this->setPermission("hyperiummc.staff");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("Use command in the game");
            return false;
        }
        if($sender->hasPermission("hyperiummc.staff")){
            if(isset($args[0])){
                foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
                    if($pl->hasPermission("hyperiummc.staff")){
                        $pl->sendMessage(TE::BOLD.TE::AQUA."STAFFCHAT"." ".TE::RESET.TE::GRAY.$sender->getName().TE::GOLD." » ".TE::DARK_AQUA.implode(" ", $args));
                    }
                }
            }else{
                $sender->sendMessage(TE::RED."Usage : /staffchat <message>");
            }
        }
        return true;
    }
}