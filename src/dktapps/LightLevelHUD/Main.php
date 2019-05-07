<?php

declare(strict_types=1);

namespace dktapps\LightLevelHUD;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\world\World;

class Main extends PluginBase implements Listener{
	/** @var TaskHandler[] */
	private $tasks = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "lighthud":
				if($sender instanceof Player){
					if(isset($this->tasks[$sender->getId()]) and !$this->tasks[$sender->getId()]->isCancelled()){
						$this->tasks[$sender->getId()]->cancel();
						unset($this->tasks[$sender->getId()]);
					}else{
						$this->tasks[$sender->getId()] = $this->getScheduler()->scheduleRepeatingTask(new class($sender) extends Task{
							/** @var Player */
							private $player;

							public function __construct(Player $player){
								$this->player = $player;
							}

							private function line(Vector3 $pos, string $label) : string{
								$world = $this->player->getWorld();
								assert($world instanceof World);
								return "$label ($pos->x, $pos->y, $pos->z): block: " . $world->getBlockLightAt($pos->x, $pos->y, $pos->z) . ", sky: " . $world->getBlockSkyLightAt($pos->x, $pos->y, $pos->z);
							}

							public function onRun(int $currentTick) : void{
								if(!$this->player->isConnected()){
									$this->getHandler()->cancel();
									return;
								}
								$f = $this->player->asVector3()->floor();
								$g = $f->subtract(0, 1, 0);
								$h = $f->add(0, 1, 0);

								$this->player->sendTip(
									$this->line($h, "Head") . "\n" .
									$this->line($f, "Feet") . "\n" .
									$this->line($g, "Ground")
								);
							}
						}, 2);
					}
				}
				return true;
			default:
				return false;
		}
	}
}
