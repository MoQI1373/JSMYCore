<?php
/**
 * Created by PhpStorm.
 * User: jkorn2324
 * Date: 2019-11-30
 * Time: 18:39
 */

declare(strict_types=1);

namespace Core\player;

use pocketmine\item\FoodSource;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\Player;

abstract class HyperiumMCPlayer extends Player {

    /** @var string */
    protected $version = ProtocolInfo::MINECRAFT_VERSION_NETWORK;

    protected function broadcastMovement(bool $teleport = false): void
    {

        $pk = new MoveActorAbsolutePacket();
        $pk->entityRuntimeId = $this->id;
        $pk->position = $this->getOffsetPosition($this);

        //this looks very odd but is correct as of 1.5.0.7
        //for arrows this is actually x/y/z rotation
        //for mobs x and z are used for pitch and yaw, and y is used for headyaw
        $pk->xRot = $this->pitch;
        $pk->yRot = $this->yaw;
        $pk->zRot = $this->yaw;

        if($teleport){
            $pk->flags |= MoveActorAbsolutePacket::FLAG_TELEPORT;
        }
        $this->getPlayer()->batchDataPacket($pk);

        // For those who are 1.14.3 & above, broadcasts position to them.
        $this->sendPosition($this->asVector3(), $this->yaw, $this->pitch, MovePlayerPacket::MODE_NORMAL, $this->getPlayer()->getPlayer());
    }

    public function getPlayer()
    {
        return parent::getPlayer(); // TODO: Change the autogenerated stub
    }

    public function handleEntityEvent(ActorEventPacket $packet): bool
    {
        if (!$this->spawned or !$this->isAlive()) {
            return true;
        }

        $this->doCloseInventory();

        $itemID = $packet->data;
        $entityID = $packet->entityRuntimeId;

        switch ($packet->event) {

            case ActorEventPacket::PLAYER_ADD_XP_LEVELS:

                if ($itemID === 0) {
                    return false;
                }
                $this->dataPacket($packet);
                $this->server->broadcastPacket($this->getViewers(), $packet);
                break;

            case ActorEventPacket::EATING_ITEM:

                if ($itemID === 0 or $entityID !== $this->getId()) {
                    return false;
                }

                $itemInHand = $this->inventory->getItemInHand();
                if ($itemInHand->getId() !== $itemID) {
                    return false;
                } elseif ($itemInHand->getId() === $itemID and !$this->isUsingItem()) {
                    return false;
                }

                if ($itemInHand instanceof FoodSource and $itemInHand->requiresHunger() and !$this->isHungry()) {
                    return false;
                }

                $this->dataPacket($packet);
                $this->server->broadcastPacket($this->getViewers(), $packet);
                break;

            default: return false;
        }

        return true;
    }

    public function handleLogin(LoginPacket $packet): bool
    {

        $this->version = (string)$packet->clientData["GameVersion"];

        return parent::handleLogin($packet);
    }


    /**
     * @return string
     *
     * Gets the game version of the player.
     */
    public function getVersion() : string {
        return $this->version;
    }
}