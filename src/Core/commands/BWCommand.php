<?php

namespace Core\commands;

use Core\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class BWCommand extends Command{

    public function __construct(Loader $plugin)
    {
        parent::__construct("bedwars", "join bedwars", " ", ["bw"]);
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if ($sender instanceof Player){
            $this->BWForm($sender);
        }
    }

    public function BWForm($player){
        $form = $this->plugin->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->plugin->getServer()->dispatchCommand($player, "bwsolo random");
                    break;
                case 1:
                    $this->plugin->getServer()->dispatchCommand($player, "bwduo random");
                    break;
            }
        });
        $form->setTitle("§rBedWars");
        $form->setContent("Trios and Squad soon");
        $form->addButton("§b§lSolo");
        $form->addButton("§b§lDuo");
        $form->sendToPlayer($player);
        return $form;
    }
}
