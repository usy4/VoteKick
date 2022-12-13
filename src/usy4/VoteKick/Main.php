<?php

namespace usy4\VoteKick;

use usy4\VoteKick\commands\VoteKickCommand;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerQuitEvent;

use Vecnavium\FormsUI\CustomForm;
use Vecnavium\FormsUI\ModalForm;

class Main extends PluginBase implements Listener{

    public $VoteKickCount = [];

    public function onEnable() : void{
        $this->getServer()->getCommandMap()->register($this->getName(), new VoteKickCommand($this)); 
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getConfig();
        $this->saveDefaultConfig();
    }

    public function voteForm(Player $player) : void {
        $list = [];
        $onlineList = [];
        $listagain = [];
        $onlineListAgain = [];
        foreach($this->getServer()->getOnlinePlayers() as $p){
            $list[] = $p->getName();
            if(isset($this->VoteKickCount[$p->getName()])){
                $listagain[] = $p->getName() . " §cVotes: ".$this->VoteKickCount[$p->getName()];
            } else {
                $listagain[] = $p->getName() . " §cVotes: 0";
            }
            $onlineList[$player->getName()] = $list;
            $onlineList[$player->getName()."with votes"] = $listagain;
        }
        $ol = $onlineList[$player->getName()];
        $olv = $onlineList[$player->getName()."with votes"];
        $form = new CustomForm(function (Player $player, $data) use ($ol){
            if($data === null) {
                return true;
            }
            $target = $this->getServer()->getPlayerByPrefix($ol[$data[0]]);
            if($target === null) {
                $player->sendMessage("§cError, Player not found!");
                return true;
            }else if($target->getName() === $player->getName()) {
                $player->sendMessage("§cError, You cant vote on yourself!");
                return true;
            }else if($target->hasPermission($this->getConfig()->get("kick.protection.votekick", "kick.protection.votekick"))) {
                $player->sendMessage("§cError, I think this player is protected, do you agree with me?");
                return true;
            }
            $this->confirmVote($player, $target, $data);
        });
        $form->addDropdown("Select player to vote", $olv);
        $form->addInput("Reason:", "hacking, rude, etc.");
        $form->setTitle("VoteKick");
        $player->sendForm($form);
    }

    public function confirmVote(Player $player, $target, $d){
        $form = new ModalForm(function (Player $player, $data) use ($d, $target){
            if($data === null)
                return false;
            switch($data){
                case true:
                    if(!isset($this->VoteKickCount[$target->getName()])){
                        $this->VoteKickCount[$target->getName()] = 1;
                        $this->VoteKickCount[$target->getName().$player->getName()] = true;
                        $this->getServer()->broadcastMessage($player->getName() . " §avoted on §r" . $target->getName() . " §ato kick him §r(" .  $this->VoteKickCount[$target->getName()] . "/" . $this->getConfig()->get("VoteCount", 8) . ")§a, §bReason: §r" . $d[1]);
                    } else if(!isset($this->VoteKickCount[$target->getName().$player->getName()])){
                        $this->VoteKickCount[$target->getName()]++;
                        $this->getServer()->broadcastMessage($player->getName() . " §avoted on §r" . $target->getName() . " §ato kick him §r(" .  $this->VoteKickCount[$target->getName()] . "/" . $this->getConfig()->get("VoteCount", 8) . ")§a, §bReason: §r" . $d[1]);
                    } else {
                        $this->VoteKickCount[$target->getName()]--;
                        unset($this->VoteKickCount[$target->getName().$player->getName()]);
                        $this->getServer()->broadcastMessage($player->getName() . " §acanceled the vote on §r" . $target->getName() . " §ato kick him §r(" .  $this->VoteKickCount[$target->getName()] . "/" . $this->getConfig()->get("VoteCount", 8) . ")§a, §bReason: §r" . $d[1]);
                    }
                    if($this->VoteKickCount[$target->getName()] == $this->getConfig()->get("VoteCount", 8)){
                        $this->getServer()->broadcastMessage($this->VoteKickCount[$target->getName()] . "§7 players have voted to kick §r" . $target->getName() . "§7 out");
                        $target->kick($this->VoteKickCount[$target->getName()] . "§7 players have voted to kick you out,\nReason: " . $d[1]);
                    }
                    break;
            }
        });
        if(!isset($this->VoteKickCount[$target->getName()])){
            $form->setTitle("§cAdd Vote");
            $form->setContent("Are you sure you want to vote on " . $target->getName() . "?");
            $form->setButton1("§aYes");
            $form->setButton2("§cNo");
        } else if(!isset($this->VoteKickCount[$target->getName().$player->getName()])){
            $form->setTitle("§cAdd Vote");
            $form->setContent("Are you sure you want to vote on " . $target->getName() . "?");
            $form->setButton1("§aYes");
            $form->setButton2("§cNo");
        } else {
            $form->setTitle("§cCancel Vote");
            $form->setContent("Are you sure you want to cancel your vote on " . $target->getName() . "?");
            $form->setButton1("§aYes");
            $form->setButton2("§cNo");
        }
        $player->sendForm($form);
    }

    public function onQuit(PlayerQuitEvent $event){
        if(isset($this->VoteKickCount[$event->getPlayer()->getName()])){
            unset($this->VoteKickCount[$event->getPlayer()->getName()]);
        }
        foreach($this->getServer()->getOnlinePlayers() as $p){
            if(isset($this->VoteKickCount[$event->getPlayer()->getName().$p->getName()])){
                unset($this->VoteKickCount[$event->getPlayer()->getName().$p->getName()]);
            }
            if(isset($this->VoteKickCount[$p->getName().$event->getPlayer()->getName()])){
                unset($this->VoteKickCount[$p->getName().$event->getPlayer()->getName()]);
            }
        }
    }

}
