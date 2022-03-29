<?php

namespace Core\commands;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\{Command, CommandSender, PluginCommand};
use pocketmine\utils\{Config, TextFormat as TE};

use Core\Loader;

class Broadcast extends Command {

    private $plugin;

    public function __construct(Loader $plugin){
        parent::__construct("broadcast", "Broadcast command");
        $this->setPermission("hyperiummc.staff");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args){
        if(!$sender instanceof Player){
            $sender->sendMessage("Use command in the game");
            return false;
        }
        if(isset($args[0])){
            foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
                $pl->sendMessage(TE::BOLD.TE::RED."BroadCast"." ".TE::RESET.$sender->getDisplayName().TE::YELLOW." » ".TE::DARK_AQUA.implode(" ", $args));

            }
            }else{
                $sender->sendMessage(TE::RED."Usage : /broadcast <message>");
            }
        return true;
    }
}