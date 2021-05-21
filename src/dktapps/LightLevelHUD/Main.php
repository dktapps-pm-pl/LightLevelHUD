<?php

declare(strict_types=1);

namespace dktapps\LightLevelHUD;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

class Main extends PluginBase implements Listener{
	/** @var TaskHandler[] */
	private $tasks = [];

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$id = $event->getPlayer()->getId();
		if(isset($this->tasks[$id])){
			$this->tasks[$id]->cancel();
			unset($this->tasks[$id]);
		}
	}

	private function line(World $world, Vector3 $pos, string $label) : string{
		if(($chunk = $world->getChunk($pos->getFloorX() >> 4, $pos->getFloorZ() >> 4)) === null || $chunk->isLightPopulated() !== true){
			return "$label ($pos->x, $pos->y, $pos->z): " . TextFormat::RED . "LIGHT NOT CALCULATED" . TextFormat::RESET;
		}
		return "$label ($pos->x, $pos->y, $pos->z): block: " .
			$world->getBlockLightAt($pos->x, $pos->y, $pos->z) .
			", sky (potential): " .
			$world->getPotentialBlockSkyLightAt($pos->x, $pos->y, $pos->z) .
			", sky (current): " .
			$world->getRealBlockSkyLightAt($pos->x, $pos->y, $pos->z);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "lighthud":
				if($sender instanceof Player){
					if(isset($this->tasks[$sender->getId()]) and !$this->tasks[$sender->getId()]->isCancelled()){
						$this->tasks[$sender->getId()]->cancel();
						unset($this->tasks[$sender->getId()]);
					}else{
						$this->tasks[$sender->getId()] = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function() use ($sender) : void{
							if(!$sender->isConnected()){
								unset($this->tasks[$sender->getId()]);
								throw new CancelTaskException();
							}
							$world = $sender->getWorld();
							$f = $sender->getPosition()->floor();
							$g = $f->subtract(0, 1, 0);
							$h = $f->add(0, 1, 0);

							$sender->sendTip(
								$this->line($world, $h, "Head") . "\n" .
								$this->line($world, $f, "Feet") . "\n" .
								$this->line($world, $g, "Ground")
							);
						}), 2);
					}
				}
				return true;
			default:
				return false;
		}
	}
}
