<?php

namespace Core;

use Core\commands\BWCommand;
use Core\commands\PVPCommand;
use Core\task\CPSCountTask;
use Core\task\MotdTask;
use Core\task\SettingsTask;
use ImNotYourDev\PGToDiscord\PGTD;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use Scoreboards\Scoreboards;
use Core\commands\Broadcast;
use Core\commands\Staffchat;
use Core\player\HyperiumMCPlayer;
use Core\task\CoreTask;
use Core\task\LobbyTask;
use Core\task\Particles;
use pocketmine\block\Anvil;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\Wheat;
use pocketmine\entity\Skin;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\inventory\transaction\action\SlotChangeAction;
//use pocketmine\network\mcpe\protocol\types\inventory\TransactionData;
use pocketmine\item\ItemIds;
use pocketmine\player\GameMode;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\entity\EntityDamageEvent;;
//use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
//use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
//use pocketmine\network\mcpe\protocol\PlayStatusPacket;

use Core\commands\Hub;
use Core\Form\FormUI;
use Core\utils\Scoreboard;
use libs\xenialdan\apibossbar\BossBar;

use HyperiumMC\API\ZenLoader;


class Loader extends PluginBase implements Listener
{

    use FormUI;

    public $playerlist = [];
    public $particle1 = array("HeartParticles");
    public $particle2 = array("LaserParticles");
    public $particle3 = array("FlameParticles");
    public $particle4 = array("AngryVillagerParticles");
    public $particle5 = array("WaterDripParticles");
    public $particle6 = array("RedstoneParticles");
    public $name = array();
    protected $skin = [];

    private const ARRAY_MAX_SIZE = 100;

    /** @var bool */
    private $countLeftClickBlock;

    /** @var array[] */
    private $clicksData = [];

    public function onLoad(): void
    {
        $this->getServer()->getLogger()->info("Loading JSMYCore");
    }

    public function onEnable(): void
    {
        $this->getLogger()->info("Loaded JSMY lobbycore");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getScheduler()->scheduleRepeatingTask(new CoreTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new Particles($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new LobbyTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new SettingsTask($this), 20);
        $this->getScheduler()->scheduleRepeatingTask(new MotdTask($this), 100);
        $this->getScheduler()->scheduleRepeatingTask(new CPSCountTask($this), 1);

        $this->getServer()->getCommandMap()->register("hub", new Hub($this));
        $this->getServer()->getCommandMap()->register("staffchat", new Staffchat($this));
        $this->getServer()->getCommandMap()->register("broadcast", new Broadcast($this));
        $this->getServer()->getCommandMap()->register("pvp", new PVPCommand($this));
        $this->getServer()->getCommandMap()->register("bedwarshyperium", new BWCommand($this));

        $this->purePerms = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
        $this->bossbar = new BossBar();
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "particles");
        @mkdir($this->getDataFolder() . "capes");
        @mkdir($this->getDataFolder() . "settings");
    }

    public function addBossbar($player)
    {
        $name = TF::BOLD . TF::AQUA . "JSMY " . TF::YELLOW . "- " . TF::GREEN . "Lobby";
        $this->bossbar->setTitle($name);
        $this->bossbar->setPercentage(100);
        $this->bossbar->addPlayer($player);
    }

    public function removeBossbar($player)
    {
        $this->bossbar->removePlayer($player);
    }

    public function onPlayerCreation(PlayerCreationEvent $event){
        $hyperiummcplayer = HyperiumMCPlayer::class;
        $event->setBaseClass($hyperiummcplayer);
    }

    public function initPlayerClickData(Player $p) : void{
        $this->clicksData[$p->getLowerCaseName()] = [];
    }

    public function addClick(Player $p) : void{
        array_unshift($this->clicksData[$p->getLowerCaseName()], microtime(true));
        if(count($this->clicksData[$p->getLowerCaseName()]) >= self::ARRAY_MAX_SIZE){
            array_pop($this->clicksData[$p->getLowerCaseName()]);
        }
    }

    /**
     * @param Player $player
     * @param float $deltaTime Interval of time (in seconds) to calculate CPS in
     * @param int $roundPrecision
     * @return float
     */
    public function getCps(Player $player, float $deltaTime = 1.0, int $roundPrecision = 1) : float{
        if(!isset($this->clicksData[$player->getLowerCaseName()]) || empty($this->clicksData[$player->getLowerCaseName()])){
            return 0.0;
        }
        $ct = microtime(true);
        return round(count(array_filter($this->clicksData[$player->getLowerCaseName()], static function(float $t) use ($deltaTime, $ct) : bool{
                return ($ct - $t) <= $deltaTime;
            })) / $deltaTime, $roundPrecision);
    }

    public function removePlayerClickData(Player $p) : void{
        unset($this->clicksData[$p->getLowerCaseName()]);
    }

    public function packetReceive(DataPacketReceiveEvent $e) : void{
        if(
            isset($this->clicksData[$e->getPlayer()->getLowerCaseName()]) &&
            (
                ($e->getPacket()::NETWORK_ID === InventoryTransactionPacket::NETWORK_ID && $e->getPacket()->trData instanceof UseItemOnEntityTransactionData) ||
                ($e->getPacket()::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID && $e->getPacket()->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE) ||
                ($this->countLeftClickBlock && $e->getPacket()::NETWORK_ID === PlayerActionPacket::NETWORK_ID && $e->getPacket()->action === PlayerActionPacket::ACTION_START_BREAK)
            )
        ){
            $this->addClick($e->getPlayer());
        }
    }

    public function onDrop(PlayerDropItemEvent $event)
    {
        $p = $event->getPlayer();
        $dl = $this->getServer()->getDefaultLevel()->getFolderName();
        $l = $p->getLevel()->getFolderName();
        $i = $event->getItem();
        if ($i->hasCustomName() && $l == $dl) {
            $event->setCancelled();
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $plvl = $player->getLevel()->getFolderName();
        $lvl = $this->getServer()->getDefaultLevel()->getFolderName();

        $autoSprint = new Config($this->getDataFolder() . "settings/autoSprint.yml", Config::YAML);

        if (!$player->isSprinting()) {
            if ($autoSprint->exists($player->getName())) {
                $player->setSprinting(true);
            }
        }

        if ($plvl == $lvl) {
            /*
            $block = BlockIds::IRON_BLOCK;
            if ($player->getLevel()->getBlock($player->floor()->subtract(0, 1, 0)) == $block){
                $distance = 33;

                $motFlat = $player->getDirectionPlane()->normalize()->multiply($distance * 3.75 / 20);//Seems to work almost perfectly
                $mot = new Vector3($motFlat->x, 0.5, $motFlat->y);
                $player->setMotion($mot);

                try {
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                    $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                } catch (\Exception $exception){
                }
                $player->getLevel()->addSound(new BlazeShootSound($player->getPosition()));
            }
            */

            if ($player->getLocation()->getYaw() < -5) {
                $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());

                $player->getInventory()->setItem(0, ItemFactory::get(ItemIds::COMPASS, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Travel"));

                $player->getInventory()->setItem(3, ItemFactory::get(ItemIds::FEATHER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Super Jump"));
                $player->getInventory()->setItem(4, ItemFactory::get(ItemIds::ENCHANTED_BOOK, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Social"));

                $player->getInventory()->setItem(5, ItemFactory::get(ItemIds::SLIME_BALL, 3, 1)->setCustomName(TF::BOLD . TF::AQUA . "Report"));

                $player->getInventory()->setItem(6, ItemFactory::get(ItemIds::RED_FLOWER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Cosmetics"));

                $player->getInventory()->setItem(7, ItemFactory::get(ItemIds::PAPER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Statistics"));

                $player->getInventory()->setItem(8, ItemFactory::get(ItemIds::BLAZE_ROD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Settings"));

                if ($player->hasPermission("hyperiummc.staff")) {
                    $player->getInventory()->setItem(1, ItemFactory::get(ItemIds::EMERALD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Staff Tools"));
                }
            }
        }
    }

    public function onUpdate(BlockUpdateEvent $event)
    {
        if ($event->getBlock() instanceof Wheat) {
            $event->setCancelled();
        }
    }

    public function onUse(PlayerItemConsumeEvent $event)
    {
        $player = $event->getPlayer();
        $plvl = $player->getLevel()->getFolderName();
        $lvl = $this->getServer()->getDefaultLevel()->getFolderName();
        if ($plvl == $lvl) {
            $event->setCancelled();
        }
    }

    public function onTransaction(InventoryTransactionEvent $event)
    {

        $transaction = $event->getTransaction();
        foreach ($transaction->getActions() as $action) {
            $sources = $transaction->getSource();
            if ($sources instanceof Player) {

                $plvl = $sources->getLevel()->getFolderName();
                $lvl = $this->getServer()->getDefaultLevel()->getFolderName();
                if ($plvl == $lvl) {
                    if ($action instanceof SlotChangeAction) {
                        if ($action->getInventory() instanceof PlayerInventory) {
                            $event->setCancelled(true);

                            if ($sources->hasPermission("jsmy.staff")) {
                                $event->setCancelled(false); //OP就没有
                            }
                        }
                    }
                }
            }
        }
    }

    public function onThrow(ProjectileLaunchEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $plvl = $entity->getLevel()->getFolderName();
            $lvl = $this->getServer()->getDefaultLevel()->getFolderName();
            if ($plvl == $lvl) {
                $event->setCancelled();
            }
        }
    }

    public function onLogin(PlayerLoginEvent $event)
    {
        $player = $event->getPlayer();

        $player->teleport($this->getServer()->getDefaultLevel()->getSafeSpawn());
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();

        $name = $player->getDisplayName();
        $event->setJoinMessage(" ");
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        //$player->getLevel()->addSound(new GhastShootSound($player));
        $this->joinForm($player);
        $player->setHealth(20);
        $player->setMaxHealth(20);
        $player->setFood(20);
        $player->setGamemode($player::ADVENTURE);
        $player->setScale(1.0);
        $player->removeAllEffects();
        $player->setDisplayName(TF::GRAY . TF::BOLD . "[" . TF::RESET . $this->getPlayerRank($player) . TF::BOLD . TF::GRAY . "]" . TF::RESET . " " . $player->getName());
        $player->getInventory()->setHeldItemIndex(1);

        $this->addBossbar($player);

        $player->getInventory()->setItem(0, ItemFactory::get(ItemIds::COMPASS, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Travel"));

        $player->getInventory()->setItem(3, ItemFactory::get(ItemIds::FEATHER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Super Jump"));
        $player->getInventory()->setItem(4, ItemFactory::get(ItemIds::ENCHANTED_BOOK, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Social"));

        $player->getInventory()->setItem(5, ItemFactory::get(ItemIds::SLIME_BALL, 3, 1)->setCustomName(TF::BOLD . TF::AQUA . "Report"));

        $player->getInventory()->setItem(6, ItemFactory::get(ItemIds::RED_FLOWER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Cosmetics"));

        $player->getInventory()->setItem(7, ItemFactory::get(ItemIds::PAPER, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Statistics"));

        $player->getInventory()->setItem(8, ItemFactory::get(ItemIds::BLAZE_ROD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Settings"));

        if ($player->hasPermission("jsmy.staff")) {
            $player->getInventory()->setItem(1, ItemFactory::get(ItemIds::EMERALD, 0, 1)->setCustomName(TF::BOLD . TF::AQUA . "Staff Tools"));
            $this->getServer()->broadcastMessage(TF::BOLD . "[" . $this->getPlayerRank($player) . "] " . $player->getName() . TF::RESET . TF::GRAY . " Joined!");
        }

        $this->initPlayerClickData($player);
    }

    public function onChange(EntityLevelChangeEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player) {
            $scoreboard = Scoreboards::getInstance();
            $scoreboard->remove($entity);
            foreach ($this->getServer()->getOnlinePlayers() as $players) {
                if ($entity->hasPermission("jsmy.staff")) {
                    $this->removeBossbar($entity);
                    $entity->showPlayer($players);
                    $entity->setDisplayName($entity->getName());
                    return false;
                }

                $l = $this->getServer()->getDefaultLevel()->getFolderName();
                $plvl = $entity->getLevel()->getFolderName();
                if ($plvl == $l) {
                    $this->removeBossbar($entity);
                    $entity->showPlayer($players);
                    $entity->setDisplayName($entity->getName());
                    $entity->setAllowFlight(false);
                }
            }
            //$packet = new ChangeDimensionPacket();
            //$packet->dimension = 2;
            //$packet->position = $entity->getDirectionVector();
            //$packet->respawn = true;
            //$entity->dataPacket($packet);
            //$pk = new PlayStatusPacket();
            //$pk->status = 3;
            //$entity->dataPacket($pk);

        }
    }

    public function getPlayerRank(Player $player): string
    {

        $group = $this->purePerms->getUserDataMgr()->getData($player)['group'];

        if ($group !== null) {
            return $group;
        } else {
            return "No Rank";
        }
    }

    public function joinForm($player)
    {
        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $player->sendTitle(TF::GREEN."Welcome to", TF::AQUA."JSMY Network", 3, 8, 2);
                    break;
            }
        });
        $form->setTitle("§bJSMY §aBETA");
        $form->setContent(" §bRules \n §e1. §aTreat others with respect. \n §e2. §aOffensive content is not allowed. \n §e3. §aKeep chat family friendly. \n §e4. §aAdvertising is not allowed. \n §e5. §aAllow moderators to do their jobs. \n §e6. §aSpamming is not allowed. \n §e7. §aCheating is not tolerated. \n §e8. §aDont ask/beg for rank \n §e9. §aDont beg to be a staff");
        $form->addButton("§rClose");
        $form->sendToPlayer($player);
        return $form;
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $this->removePlayerClickData($player);
        $event->setQuitMessage(" ");
    }

    public function onInteract(PlayerInteractEvent $event)
    {

        $player = $event->getPlayer();
        $name = $player->getName();
        $inv = $player->getInventory();
        $item = $event->getItem();

        if ($event->getAction() == $event::RIGHT_CLICK_AIR) {

            if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Report") {
                $this->getServer()->dispatchCommand($player, "report");
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Statistics") {
                $this->stats($player);
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Travel") {
                $this->navi($player);
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Social") {
                $this->social($player);
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Settings") {
                $this->setting($player);
            } elseif ($item->getCustomName() == TF::BOLD . TF::AQUA . "Cosmetics") {
                $this->cosmetic($player);
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Staff Tools") {
                if ($player->hasPermission("jsmy.staff")) {
                    $this->stafftools($player);
                }
            } else if ($item->getCustomName() == TF::BOLD . TF::AQUA . "Super Jump"){
                $slowdown = new Config($this->getDataFolder() . "slowdown.yml", Config::YAML);

                if ($slowdown->get($player->getName()) == 5){
                    $player->sendMessage(TF::RED . "You must wait 5s to use this again!");
                    return false;
                } elseif ($slowdown->get($player->getName()) == 4){
                    $player->sendMessage(TF::RED . "You must wait 4s to use this again!");
                    return false;
                } elseif ($slowdown->get($player->getName()) == 3){
                    $player->sendMessage(TF::RED . "You must wait 3s to use this again!");
                    return false;
                } elseif ($slowdown->get($player->getName()) == 2){
                    $player->sendMessage(TF::RED . "You must wait 2s to use this again!");
                    return false;
                } elseif ($slowdown->get($player->getName()) == 1){
                    $player->sendMessage(TF::RED . "You must wait 1s to use this again!");
                    return false;
                } else {
                    if ($player->isImmobile()){
                        $distance = 18;

                        $motFlat = $player->getDirectionPlane()->normalize()->multiply($distance * 3.75 / 20);//Seems to work almost perfectly

                        $mot = new Vector3(0.5, $motFlat->y, 0.5);
                        $player->setMotion($mot);
                    } else {
                        $distance = 23;

                        $motFlat = $player->getDirectionPlane()->normalize()->multiply($distance * 3.75 / 20);//Seems to work almost perfectly

                        $mot = new Vector3($motFlat->x, 0.5, $motFlat->y);
                        $player->setMotion($mot);

                        try {
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new FlameParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                            $player->getLevel()->addParticle(new RedstoneParticle($player->getPosition()->add(random_int(0, 10) / 10 - 0.5, random_int(0, 4) / 10 - 0.2, random_int(0, 10) / 10 - 0.5)));
                        } catch (\Exception $exception) {
                        }
                        $player->getLevel()->addSound(new BlazeShootSound($player->getPosition()));

                        $slowdown->set($player->getName(), 5);
                        $slowdown->save();
                    }
                }
            }
        }
    }

    //public function onInvChange(EntityInventoryChangeEvent $event){
    //$entity = $event->getEntity();
    //if ($entity instanceof Player){
    //$dl = $this->getServer()->getDefaultLevel()->getFolderName();
    //$p = $entity->getLevel()->getFolderName();

    //if ($p == $dl){
    //$event->setCancelled();
    //}
    //}
    //}

    public function navi($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->getServer()->dispatchCommand($player, "pvp");
                    break;
                case 1:
                    $this->getServer()->dispatchCommand($player, "sw random");
                    break;
                case 2:
                    $this->getServer()->dispatchCommand($player, "bedwars");
            }
        });
        $form->setTitle("§rTravel");
        $form->addButton("§rPVP");
        $form->addButton("§rSkywars");
        $form->addButton("§rBedwars");
        $form->sendToPlayer($player);
        return $form;
    }

    public function setting(Player $player)
    {
        $form = $this->createSimpleForm(function (Player $player, int $data = null) {


            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $showPlayer = new Config($this->getDataFolder() . "settings/showPlayer.yml", Config::YAML);

                    if ($showPlayer->exists($player->getName())){
                        $showPlayer->remove($player->getName());
                        $showPlayer->save();

                        $player->sendMessage(TF::GREEN . "You are now show player");
                    } else {
                        $showPlayer->set($player->getName());
                        $showPlayer->save();

                        $player->sendMessage(TF::GREEN . "You are now hide player");
                    }
                    break;
                case 1:
                    $autoSprint = new Config($this->getDataFolder() . "settings/autoSprint.yml", Config::YAML);

                    if ($autoSprint->exists($player->getName())){
                        $autoSprint->remove($player->getName());
                        $autoSprint->save();

                        $player->sendMessage(TF::GREEN . "Disabled autosprint");
                    } else {
                        $autoSprint->set($player->getName());
                        $autoSprint->save();

                        $player->sendMessage(TF::GREEN . "Enabled autosprint");
                    }
                    break;
                case 2:
                    $cpscount = new Config($this->getDataFolder() . "settings/cpsCounter.yml", Config::YAML);

                    if ($cpscount->exists($player->getName())){
                        $cpscount->remove($player->getName());
                        $cpscount->save();

                        $player->sendMessage(TF::GREEN . "Disabled CPS counter");
                    } else {
                        $cpscount->set($player->getName());
                        $cpscount->save();

                        $player->sendMessage(TF::GREEN . "Enabled CPS counter");
                    }
                    break;
            }
        });
        $showPlayer = new Config($this->getDataFolder() . "settings/showPlayer.yml", Config::YAML);
        $autoSprint = new Config($this->getDataFolder() . "settings/autoSprint.yml", Config::YAML);
        $cpscount = new Config($this->getDataFolder() . "settings/cpsCounter.yml", Config::YAML);
        $form->setTitle("§rSettings");

        if ($showPlayer->exists($player->getName())){
            $form->addButton(TF::RESET . "Hide Player" . "\n" . TF::GREEN . "Enabled");
        } else {
            $form->addButton(TF::RESET . "Show Player" . "\n" . TF::RED . "Disabled");
        }

        if ($autoSprint->exists($player->getName())){
            $form->addButton(TF::RESET . "Auto Sprint" . "\n" . TF::GREEN . "Enabled");
        } else {
            $form->addButton(TF::RESET . "Auto Sprint" . "\n" . TF::RED . "Disabled");
        }

        if ($cpscount->exists($player->getName())){
            $form->addButton(TF::RESET . "CPS Counter" . "\n" . TF::GREEN . "Enabled");
        } else {
            $form->addButton(TF::RESET . "CPS Counter" . "\n" . TF::RED . "Disabled");
        }

        $form->sendToPlayer($player);

        return $form;
    }

    public function cosmetic($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->particles($player);
                    break;
            }
        });
        $form->setTitle("§rCosmetics");
        $form->addButton("§rParticles");

        $form->sendToPlayer($player);
        return $form;
    }

    public function particles($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $particle1 = new Config($this->getDataFolder() . "particles/Heart.yml", Config::YAML);
                    if ($particle1->exists($player->getName())) {
                        if (!in_array($player->getName(), $this->particle1)) {
                            $this->particle1[] = $player->getName();

                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);

                            $player->sendMessage(TF::GREEN . "Heart particles enabled");
                        } else {
                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            $player->sendMessage(TF::GREEN . "Heart particles disabled");
                        }
                    } else {
                        $this->buyparticle1($player);
                    }
                    break;
                case 1:
                    $particle2 = new Config($this->getDataFolder() . "particles/Laser.yml", Config::YAML);
                    if ($particle2->exists($player->getName())) {
                        if (!in_array($player->getName(), $this->particle2)) {
                            $this->particle2[] = $player->getName();

                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);

                            $player->sendMessage(TF::GREEN . "Laser particles enabled");
                        } else {
                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            $player->sendMessage(TF::GREEN . "Laser particles disabled");
                        }
                    } else {
                        $this->buyparticle2($player);
                    }
                    break;
                case 2:
                    $particle3 = new Config($this->getDataFolder() . "particles/Flame.yml", Config::YAML);
                    if ($particle3->exists($player->getName())) {
                        if (!in_array($player->getName(), $this->particle3)) {
                            $this->particle3[] = $player->getName();

                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);

                            $player->sendMessage(TF::GREEN . "Flame particles enabled");
                        } else {
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            $player->sendMessage(TF::GREEN . "Flame particles disabled");
                        }
                    } else {
                        $this->buyparticle3($player);
                    }
                    break;
                case 3:
                    $particle4 = new Config($this->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                    if ($particle4->exists($player->getName())) {
                        if (!in_array($player->getName(), $this->particle4)) {
                            $this->particle4[] = $player->getName();

                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);

                            $player->sendMessage(TF::GREEN . "Angry Villager particles enabled");
                        } else {
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            $player->sendMessage(TF::GREEN . "Angry Villager particles disabled");
                        }
                    } else {
                        $this->buyparticle4($player);
                    }
                    break;
                case 4:
                    $particle5 = new Config($this->getDataFolder() . "particles/WaterDrip.yml", Config::YAML);
                    if ($particle5->exists($player->getName())) {
                        if (!in_array($player->getName(), $this->particle5)) {
                            $this->particle5[] = $player->getName();

                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);

                            $player->sendMessage(TF::GREEN . "Water Drip particles enabled");
                        } else {
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);
                            $player->sendMessage(TF::GREEN . "Water Drip particles disabled");
                        }
                    } else {
                        $this->buyparticle5($player);
                    }
                    break;
                case 5:
                    $particle6 = new Config($this->getDataFolder() . "particles/Redstone.yml", Config::YAML);
                    if ($particle6->exists($player->getName())){
                        if (!in_array($player->getName(), $this->particle6)){
                            $this->particle6[] = $player->getName();

                            unset($this->particle1[array_search($player->getName(), $this->particle1)]);
                            unset($this->particle2[array_search($player->getName(), $this->particle2)]);
                            unset($this->particle3[array_search($player->getName(), $this->particle3)]);
                            unset($this->particle4[array_search($player->getName(), $this->particle4)]);
                            unset($this->particle5[array_search($player->getName(), $this->particle5)]);

                            $player->sendMessage(TF::GREEN . "Redstone particles enabled");
                        } else {
                            unset($this->particle6[array_search($player->getName(), $this->particle6)]);
                            $player->sendMessage(TF::GREEN . "Redstone particles disabled");
                        }
                    } else {
                        $this->buyparticle6($player);
                    }
            }
        });
        $form->setTitle("§rParticles");
        $form->addButton("§rHearts");
        $form->addButton(TF::RESET . "Laser");
        $form->addButton(TF::RESET . "Flame");
        $form->addButton(TF::RESET . "Angry Villager");
        $form->addButton(TF::RESET . "Water Drip");
        $form->addButton(TF::RESET . "Redstone");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle6($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 8500;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle6 = new Config($this->getDataFolder() . "particles/Redstone.yml", Config::YAML);
                        $particle6->set($player->getName(), 1);
                        $particle6->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "8500");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle5($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 8000;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle5 = new Config($this->getDataFolder() . "particles/WaterDrip.yml", Config::YAML);
                        $particle5->set($player->getName(), 1);
                        $particle5->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "8000");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle4($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 7500;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle4 = new Config($this->getDataFolder() . "particles/AngryVillager.yml", Config::YAML);
                        $particle4->set($player->getName(), 1);
                        $particle4->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "7500");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle3($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 6800;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle3 = new Config($this->getDataFolder() . "particles/Flame.yml", Config::YAML);
                        $particle3->set($player->getName(), 1);
                        $particle3->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "6800");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle2($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 6500;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle2 = new Config($this->getDataFolder() . "particles/Laser.yml", Config::YAML);
                        $particle2->set($player->getName(), 1);
                        $particle2->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "6500");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function buyparticle1($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                    $price = 5000;
                    $money = $economyapi->myMoney($player);
                    if ($money < $price) {
                        $player->sendMessage(TF::RED . "You dont have enough money to buy this particle!");
                    } else {
                        $particle1 = new Config($this->getDataFolder() . "particles/Heart.yml", Config::YAML);
                        $particle1->set($player->getName(), 1);
                        $particle1->save();
                        $economyapi->reduceMoney($player, $price);
                    }
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rParticles");
        $form->setContent(TF::RED . "You dont have this particle" . "\n\n" . TF::GREEN . "Do you want to buy it?" . "\n\n" . TF::RESET . "Price: " . TF::GOLD . "5000");
        $form->addButton(TF::GREEN . "Yes");
        $form->addButton(TF::RED . "No");

        $form->sendToPlayer($player);
        return $form;
    }

    public function stats($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->profile($player);
                    break;
                case 1:

                    break;
            }
        });
        $form->setTitle("§rStatistics");
        $form->addButton("§rProfile");
        $form->addButton("§rClose");
        $form->sendToPlayer($player);
        return $form;
    }

    public function stafftools($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->gamemode($player);
                    break;
                case 1:
                    $this->vanish($player);
                    break;
                case 2:
                    $this->staffteleport($player);
                    break;
                case 3:
                    $this->getServer()->dispatchCommand($player, "reportadmin");
                    break;
                case 4:
                    break;
            }
        });
        $form->setTitle("§rStaff");
        $form->addButton("§rGamemode");
        $form->addButton("§rInvisiblity");
        $form->addButton("§rTeleport");
        $form->addButton("§rReport (Admin UI)");
        $form->addButton("§rClose", 0, "textures/other/barrier");
        $form->sendToPlayer($player);
        return $form;
    }

    public function gamemode($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $player->setGamemode($player::ADVENTURE);
                    $player->sendMessage("§aSucessfully change your gamemode to Adventure Mode");
                    break;
                case 1:
                    $player->setGamemode($player::SURVIVAL);
                    $player->sendMessage("§aSucessfully change your gamemode to Survival Mode");
                    break;
                case 2:
                    $player->setGamemode($player::CREATIVE);
                    $player->sendMessage("§aSucessfully change your gamemode to Creative Mode");
                    break;
                case 3:
                    $player->setGamemode($player::SPECTATOR);
                    $player->sendMessage("§aSucessfully change your gamemode to Spectator Mode");
                    break;
                case 4:
                    $this->stafftools($player);
                    break;
            }
        });
        $form->setTitle("§rGamemode");
        $form->addButton("§rAdventure");
        $form->addButton("§rSurvival");
        $form->addButton("§rCreative");
        $form->addButton("§rSpectator");
        $form->addButton("§rBack", 0, "textures/other/back");
        $form->sendToPlayer($player);
        return $form;
    }

    public function vanish($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $player->setInvisible(true);
                    $player->sendMessage("§aYou are now invisible");
                    break;
                case 1:
                    $player->setInvisible(false);
                    $player->sendMessage("§aYou are now visible");
                    break;
                case 2:
                    $this->stafftools($player);
                    break;
            }
        });
        $form->setTitle("§rInvisiblity");
        $form->addButton("§rEnable");
        $form->addButton("§rDisable");
        $form->addButton("§rBack", 0, "textures/other/back");
        $form->sendToPlayer($player);
        return $form;
    }

    public function staffteleport(Player $player)
    {
        $list = [];
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $list[] = $player->getName();
        }

        $this->playerlist[$player->getName()] = $list;

        $form = $this->createCustomForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }

            $index = $data[1];
            $playername = $this->playerlist[$player->getName()][$index];
            $target = $this->getServer()->getPlayer($playername);

            if ($target instanceof Player){
                $player->teleport($target);
                $player->sendMessage("§aTeleported to " . $target->getName());
            }
        });
        if (empty($this->getServer()->getOnlinePlayers())){
            $player->sendMessage(TF::RED . "No Player!");
            return true;
        }
        $form->setTitle("§rTeleport");
        $form->addDropdown("Select Player:", $this->playerlist[$player->getName()]);
        $form->sendToPlayer($player);

        return $form;
    }

    public function profile($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->stats($player);
                    break;
            }
        });
        $api = $this->getServer()->getPluginManager()->getPlugin("ZenAPI");
        $economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
        $form->setTitle("§rProfile");
        $form->setContent("§rName: §b" . $player->getName() . "\n\n" . "§rLevel: §b" . $api->getLevel($player) . "\n\n" . "§rExp: §b" . $api->getExp($player) . "/1500" . "\n\n" . "§rRank: §b" . $this->getPlayerRank($player) . "\n\n" . "§rCoins: §b" . $economyapi->myMoney($player));
        $form->addButton("§rBack");
        $form->sendToPlayer($player);

        return $form;
    }

    public function social($player)
    {

        $form = $this->createSimpleForm(function (Player $player, int $data = null) {

            $result = $data;
            if ($result === null) {
                return true;
            }
            switch ($result) {
                case 0:
                    $this->getServer()->dispatchCommand($player, "party");
                    break;
                case 1:
                    $this->getServer()->dispatchCommand($player, "friend");
                    break;
            }
        });
        $form->setTitle("§rSocial Menu");
        $form->addButton("§b§lParty", 0, "textures/other/partyinvite");
        $form->addButton("§b§lFriend", 0, "textures/other/friend");
        $form->sendToPlayer($player);
        return $form;
    }

    public function onExhaust(PlayerExhaustEvent $event)
    {
        $player = $event->getPlayer();

        $plevel = $player->getLevel()->getFolderName();
        $level = $this->getServer()->getDefaultLevel()->getFolderName();

        if ($plevel === $level) {
            $event->setCancelled();
        }
    }

    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();

        if ($entity instanceof Player) {
            $plevel = $entity->getLevel()->getFolderName();
            $level = $this->getServer()->getDefaultLevel()->getFolderName();
            if ($plevel === $level) {
                $event->setCancelled();
            }
        }
    }

    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $plevel = $player->getLevel()->getFolderName();
        $level = $this->getServer()->getDefaultLevel()->getFolderName();
        if ($plevel == $level) {
            $event->setCancelled();

            if ($player->hasPermission("hyperiummc.staff")) {
                $event->setCancelled(false);
            }
        }
    }

    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        $plevel = $player->getLevel()->getFolderName();
        $level = $this->getServer()->getDefaultLevel()->getFolderName();
        if ($plevel == $level) {
            $event->setCancelled();

            if ($player->hasPermission("hyperiummc.staff")) {
                $event->setCancelled(false);
                
                if ($event->getBlock() instanceof Anvil) {
                    $event->setCancelled(); //fix staff can place anvil on lobby
                }
            }
        }
    }

    public function onChangeSkin(PlayerChangeSkinEvent $event)
    {
        $player = $event->getPlayer();

        //$this->skin[$player->getName()] = $player->getSkin();
        $player->setSkin($player->getSkin());
    }

    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        $msg = $event->getMessage();

        //$event->setCancelled(true);

        $recipients = $event->getRecipients();
        foreach($recipients as $key => $recipient){
            if($recipient instanceof Player){
                if($recipient->getLevel() !== $player->getLevel()){
                    unset($recipients[$key]);
                }
            }
        }
        $event->setRecipients($recipients);

    }
}