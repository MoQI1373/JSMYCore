<?php

namespace Core\commands;

use Core\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class PVPCommand extends Command{

    public function __construct(Loader $plugin)
    {
        parent::__construct("pvp", "pvp selection command", " ", [" "]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            $this->PVPForm($sender);
        }
    }

    public function PVPForm($player){
        $form = $this->plugin->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->plugin->getServer()->dispatchCommand($player, "duels random");
                    break;
                case 1:
                    $this->plugin->getServer()->dispatchCommand($player, "ffa join");
                    break;
            }
        });
        $form->setTitle("§rPVP");
        $form->addButton("§b§lDuels");
        $form->addButton("§b§lFFA");
        $form->sendToPlayer($player);
        return $form;
    }
}
