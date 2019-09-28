<?php

namespace BlackList;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

class main extends PluginBase implements Listener{
    
    public function onEnable(){
        if(!file_exists($this->getDataFolder())){
		@mkdir($this->getDataFolder(), 0744, true);
	}
        $this->blacklist = new Config($this->getDataFolder() . "blacklist.yml", Config::YAML);
        $this->blackreason = new Config($this->getDataFolder() . "blackreason.yml", Config::YAML);
        $this->blacktime = new Config($this->getDataFolder() . "blacktime.yml", Config::YAML);
	$this->blacklasttime = new Config($this->getDataFolder() . "blacklasttime.yml", Config::YAML);
	$this->blackip = new Config($this->getDataFolder() . "blackip.yml", Config::YAML);
        $this->permission = new Config($this->getDataFolder() . "permission.yml", Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this,$this); 
    } 
    
    public function onPlayerJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
        if($this->blacklist->exists($name)){
	    $this->blackip->set($name,$player->getAddress());
	    $this->blackip->save();
            foreach($this->getServer()->getOnlinePlayers() as $players){
                if($players->isOp() || $this->permission->exists($players->getName())){
                   $players->sendMessage("§l§6<staff>§fブラックリストの §e{$name} がサーバーに参加しました。");  
                }
            }
            return true;
        }
        if($player->isOp() || $this->permission->exists($player->getName())){
            foreach($this->getServer()->getOnlinePlayers() as $players){
                if($this->blacklist->exists($players->getName())){
                    $player->sendMessage("§l§6<staff>§fブラックリストの §e{$players->getName()} がオンラインです。");
                }
            }
            return true;
        }
    }
    
    public function onPlayerQuit(PlayerQuitEvent $event){
        $player = $event->getPlayer();
        $name = $player->getName();
	$time = date("Y年n月j日G時i分");
        if($this->blacklist->exists($name)){
	    $this->blacklasttime->set($name,$time);
	    $this->blacklasttime->save();
            foreach($this->getServer()->getOnlinePlayers() as $players){
                if($players->isOp() || $this->permission->exists($players->getName())){
                   $players->sendMessage("§l§6<staff>§fブラックリストの §e{$name} がサーバーを退出しました。");
                }
            }
            return true;
        }
    }
	
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
	    switch($command->getName()){
	        case "black":
	        if(!$sender instanceof Player){
	            $sender->sendMessage("§cゲーム内で実行してください");
	        }elseif(!isset($args[0])){
	            $sender->sendMessage("/black list");
	            $sender->sendMessage("/black check <name>");
	            $sender->sendMessage("/black add <name> <reason>");
	            $sender->sendMessage("/black del <name>");
	            $sender->sendMessage("/black permission");
	        }else{
	            switch($args[0]){
	                case "add":
	                if(!isset($args[1]) || !isset($args[2])){
	                    $sender->sendMessage("/black add <name> <reason>");
	                }elseif($this->blacklist->exists($args[1])){
	                    $sender->sendMessage("§b【運営】 >>> §cすでに §e{$args[1]} §cはブラックリストに追加されています。");
	                }else{
	                    $this->blacklist->set($args[1],$sender->getName());
	                    $this->blacklist->save();
	                    $this->blackreason->set($args[1],$args[2]);
	                    $this->blackreason->save();
	                    $time = date("Y年n月j日G時i分");
	                    $this->blacktime->set($args[1],$time);
	                    $this->blacktime->save();
	                    $sender->sendMessage("§b【運営】 >>> §e{$args[1]} §aをブラックリストに追加しました。");
	                    $sender->sendMessage("§b【運営】 >>> §a理由 : {$args[2]}");
	                }
	                break;
	                
	                case "del":
	                if(!isset($args[1])){
	                    $sender->sendMessage("/black del <name>");
	                }elseif(!$this->blacklist->exists($args[1])){
	                    $sender->sendMessage("§b【運営】 >>> §cすでに §e{$args[1]} §cはブラックリストに追加されていません。");
	                }else{
	                    $this->blacklist->remove($args[1]);
	                    $this->blacklist->save();
	                    $this->blackreason->remove($args[1]);
	                    $this->blackreason->save();
			    $this->blacktime->remove($args[1]);
			    $this->blacktime->save();
			    if($this->blacklasttime->exists($args[1])){
				   $this->blacklasttime->remove($args[1]);
			           $this->blacklasttime->save();
			    }
			    if($this->blackip->exists($args[1])){
				   $this->blackip->remove($args[1]);
			           $this->blackip->save();
			    }
	                    $sender->sendMessage("§b【運営】 >>> §e {$args[1]} §aをブラックリストから削除しました。");
	                }
	                break;
				    
			case "delban":
			foreach($this->blacklist->getAll() as $key=>$value){
			    $swap = 0;
	                    if($key->isBanned()){
				    $this->blacklist->remove($key);
				    $this->blacklist->save();
	                            $this->blackreason->remove($key);
				    $this->blackreason->save();
			            $this->blacktime->remove($key);
			            $this->blacktime->save();
			            if($this->blacklasttime->exists($key)){
					    $this->blacklasttime->remove($key);
			                    $this->blacklasttime->save();
				    }
				    if($this->blackip->exists($key){
					    $this->blackip->remove($key);
					    $this->blackip->save();
				    }
				    
				    $swap++;
			    }
				       
			    if($swap == 0){
				    $sender->sendMessage("§b【運営】 >>> §cブラックリストにbanされているプレイヤーはいませんでした");
			    }else{
				    $sender->sendMessage("§b【運営】 >>> §e{$swap}人のbanされているプレイヤーをブラックリストから削除しました");
			    }
	                }
			
	                isBanned
	                case "list":
	                $sender->sendMessage("§aブラックリスト");
	                foreach($this->blacklist->getAll() as $key=>$value){
	                    $sender->sendMessage("Warnig : {$key}");
	                    $sender->sendMessage("Reason : {$this->blackreason->get($key)}");
	                }
	                return true;
	                
	                case "check":
	                if(!isset($args[1])){
	                    $sender->sendMessage("/black check <name>");
	                }elseif(!$this->blacklist->exists($args[1])){
	                    $sender->sendMessage("§b【運営】 >>> §e{$args[1]} §cはブラックリストに追加されていません。");
	                }else{
	                    $adduser = $this->blacklist->get($args[1]);
	                    $reason = $this->blackreason->get($args[1]);
	                    $time = $this->blacktime->get($args[1]);
	                    $sender->sendMessage("§e{$args[1]} §aの詳細");
	                    $sender->sendMessage("ブラック追加者 : {$adduser}");
	                    $sender->sendMessage("ブラック理由 : {$reason}");
	                    $sender->sendMessage("ブラック時刻 : {$time}");
	                    if($this->blacklasttime->exists($args[1])){
				   $lasttime = $this->blacklasttime->get($args[1]);
				   $sender->sendMessage("退出時刻 : {$lasttime}");
			    }else{
				   $sender->sendMessage("退出時刻 : データ不足");
			    }
			    if($this->blackip->exists($args[1])){
				   $ip = $this->blackip->get($args[1]);
				   $sender->sendMessage("ipアドレス : {$ip}");
			    }else{
				   $sender->sendMessage("ipアドレス : データ不足");
			    }
	                }
	                break;
	                
	                case "permission":
	                if(!isset($args[1])){
	                    $sender->sendMessage("/black permission add <name>");
	                    $sender->sendMessage("/black permission del <name>");
	                    $sender->sendMessage("/black permission list");
	                }else{
	                   switch($args[1]){
	                       case "add":
	                       if(!isset($args[2])){
	                           $sender->sendMessage("/black permission add <name>");
	                       }elseif($this->permission->exists($args[2])){
	                           $sender->sendMessage("§b【運営】 >>> §cすでに §e{$args[2]} §cはブラックリストが表示されています。");
	                       }else{
	                           $this->permission->set($args[2],$sender->getName());
	                           $this->permission->save();
	                           $sender->sendMessage("§b【運営】 >>> §e{$args[2]} §aにブラックリストを表示するようにしました。");
	                       }
	                       break;
	                       
	                       case "del":
	                       if(!isset($args[2])){
	                           $sender->sendMessage("/black permission del <name>");
	                       }elseif(!$this->permission->exists($args[2])){
	                           $sender->sendMessage("§b【運営】 >>> §cすでに{$args[2]}はブラックリストが表示されません。");
	                       }else{
	                           $this->permission->remove($args[2]);
	                           $this->permission->save();
	                           $sender->sendMessage("§b【運営】 >>> §e{$args[2]} §aにブロックリストを表示しないようにしました。");
	                       }
	                       break;
	                       
	                       case "list":
	                       $sender->sendMessage("§aブラックリスト表示者");
	                       foreach($this->permission->getAll() as $key=>$value){
	                           $sender->sendMessage("表示済み : {$key} 表示許可者 : {$value}");
	                       }
	                       return true;
	                       
	                       default:
	                       $sender->sendMessage("/black permission add <name>");
	                       $sender->sendMessage("/black permission del <name>");
	                       $sender->sendMessage("/black permission list");
	                       break;
	                   }
	                }
	                break;
	                
	                default:
	                $sender->sendMessage("/black list");
	                $sender->sendMessage("/black check <name>");
	                $sender->sendMessage("/black add <name> <reason>");
	                $sender->sendMessage("/black del <name>");
	                $sender->sendMessage("/black permission");
	                break;
	            }
	        }
	        break;
	    }
	    return true;
	}
}
