<?php

declare(strict_types=1);

namespace dktapps\LightLevelHUD;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;

class Main extends PluginBase implements Listener{

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$this->getScheduler()->scheduleRepeatingTask(new class($event->getPlayer()) extends Task{
			/** @var Player */
			private $player;

			public function __construct(Player $player){
				$this->player = $player;
			}

			private function line(Vector3 $pos, string $label) : string{
				$level = $this->player->getLevel();
				assert($level instanceof Level);
				return "$label ($pos->x, $pos->y, $pos->z): block: " . $level->getBlockLightAt($pos->x, $pos->y, $pos->z) . ", sky: " . $level->getBlockSkyLightAt($pos->x, $pos->y, $pos->z);
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
