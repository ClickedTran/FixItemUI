<?php

namespace ClickedTran\FixItem;

use pocketmine\item\{Item, ItemFactory, Tool, Armor};

use pocketmine\player\Player;
use pocketmine\Server;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\{Command, CommandSender};

use pocketmine\world\sound\AnvilUseSound;

use pocketmine\utils\Config;

use jojoe77777\FormAPI\{CustomForm, SimpleForm};
use onebone\economyapi\EconomyAPI;

class FixItemUI extends PluginBase implements Listener{

   public function onEnable(): void{
     $this->getServer()->getPluginManager()->registerEvents($this, $this);
     $form = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
     $eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
     @mkdir($this->getDataFolder());
        $this->saveResource("config.yml");
        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->saveDefaultConfig();
     
     if(count($this->getDescription()->getAuthors()) !== 1 || !in_array("ClickedTran", $this->getDescription()->getAuthors())){
          $this->getServer()->getPluginManager()->disablePlugin($this);
            $this->getLogger()->warning('DCM DOI AUTHOR CAI BA GIA MAY');
     }
  }
  
  public function onCommand(CommandSender $sender, Command $command, String $label, Array $args): bool{
   switch($command->getName()){
    case "fix":
     $this->menuForm($sender);
   return true;
   }
   return true;
  }
  
  public function menuForm(Player $player){
   $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
   $eco = EconomyAPI::getInstance();
   $money = $eco->myMoney($player);
   $exp = $player->getXpManager()->getXpLevel();
   $price_money = $config->getNested("money.price");
   $price_exp = $config->getNested("exp.price");
   
   $form = new SimpleForm(function (Player $player, $data){
    $result = $data;
     if($result === null){
          return;
     }
     switch($result){
      case 0:
      break;
      case 1:
      $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
      $eco = EconomyAPI::getInstance();
      $money = $eco->myMoney($player);
      $price_money = $config->getNested("money.price");
      
       if($money < $price_money){
       	$player->sendMessage(str_replace(["{price}"], [$price_money], $config->getNested("money.fail")));
       } else {
           $this->fixMoney($player);   
      }
      break;
     
      case 2:
      $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
      $exp = $player->getXpManager()->getXpLevel();
      $price_exp = $config->getNested("exp.price");
      
      $cash = $config->getNested("exp.percent");
      
       if($exp < $price_exp){
       	$player->sendMessage(str_replace(["{price}"], [$price_exp], $config->getNested("exp.fail")));
       } else {
           $this->fixExp($player);  
      }
      break;
     }
   });
   $form->setTitle($config->getNested("menu.title"));
   $form->addButton($config->getNested("menu.exit"));
   
   if($money < $price_money){
       $form->addButton($config->getNested("money.button-not-enough"));
   }else{
   	$form->addButton($config->getNested("money.button-enough"));
   }
   
   if($exp < $price_exp){
        $form->addButton($config->getNested("exp.button-not-enough"));
   }else{
   	 $form->addButton($config->getNested("exp.button-enough"));
   }
   
   $form->sendToPlayer($player);
   return $form;
  }
  
  public function fixMoney(Player $player){
   $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
   $form = new SimpleForm(function (Player $player, $data){
    if($data == null){
        $this->menuForm($player);
         return true;
    }
    switch($data){
     case 0:
     break;
     case 1:
     $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
      $eco = EconomyAPI::getInstance();
      $money = $eco->myMoney($player);
      $item = $player->getInventory()->getItemInHand();
      $meta = $item->getMeta();
      $cash = $meta * $config->getNested("money.percent");
          if($money >= $cash){
            $eco->reduceMoney($player, $cash);
            $item = $player->getInventory()->getItemInHand();
				      if(($item instanceof Armor) or ($item instanceof Tool)){
				        $id = $item->getId();
					      $meta = $item->getMeta();
					      $player->getInventory()->removeItem(ItemFactory::getInstance()->get($id, $meta, 1));
					      $newitem = ItemFactory::getInstance()->get($id, 0, 1);
					      if($item->hasCustomName()){
						       $newitem->setCustomName($item->getCustomName());
						    }
					      if($item->hasEnchantments()){
						        foreach($item->getEnchantments() as $enchants){
						            $newitem->addEnchantment($enchants);
						       }
						     }
						  if($item->getLore()){
							  $newitem->setLore($item->getLore());
					      }
					      $player->getInventory()->addItem($newitem);
					      $player->sendMessage(str_replace(["{price}", "{item_name}"], [$cash, $item->getName()], $config->getNested("money.successfully")));
					      return true;
					    } else {
				        	$player->sendMessage($config->get("not-items-or-armor"));
					        return false;
					    }
            return true;
          } else {
            $player->sendMessage(str_replace(["{price}"], [$cash], $config->getNested("money.fail")));
            return true;
          }
       break;
      }
    });
    $form->setTitle($config->getNested("money.title"));
    $form->addButton($config->getNested("money.no"));
    $form->addButton($config->getNested("money.confirm"));
    $form->sendToPlayer($player);
  }
  
  public function fixExp(Player $player){
    $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
    $form = new SimpleForm(function (Player $player, $data){
    if($data == null){
        $this->menuForm($player);
         return true;
    }
    switch($data){
     case 0:
     break;
     case 1:
     $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
      $exp = $player->getXpManager()->getXpLevel();
      $item = $player->getInventory()->getItemInHand();
      $meta = $item->getMeta();
      $cash = $meta * $config->getNested("exp.percent");
          if($exp >= $cash){
            $player->getXpManager()->setXpLevel($exp - $cash);
            $item = $player->getInventory()->getItemInHand();
				      if(($item instanceof Armor) or ($item instanceof Tool)){
				        $id = $item->getId();
					      $meta = $item->getMeta();
					      $player->getInventory()->removeItem(ItemFactory::getInstance()->get($id, $meta, 1));
					      $newitem = ItemFactory::getInstance()->get($id, 0, 1);
					      if($item->hasCustomName()){
						       $newitem->setCustomName($item->getCustomName());
						    }
					      if($item->hasEnchantments()){
						        foreach($item->getEnchantments() as $enchants){
						            $newitem->addEnchantment($enchants);
						       }
						     }
						  if($item->getLore()){
							  $newitem->setLore($item->getLore());
					      }
					      $player->getInventory()->addItem($newitem);
					      $player->sendMessage(str_replace(["{price}", "{item_name}"], [$cash, $item->getName()], $config->getNested("exp.successfully")));
					      return true;
					    } else {
				        	$player->sendMessage($config->get("not-items-or-armor"));
					        return false;
					    }
            return true;
          } else {
            $player->sendMessage(str_replace(["{price}"], [$cash], $config->getNested("exp.fail")));
            return true;
          }
       break;
      }
    });
    $form->setTitle($config->getNested("exp.title"));
    $form->addButton($config->getNested("exp.no"));
    $form->addButton($config->getNested("exp.confirm"));
    $form->sendToPlayer($player);
  }
}
    
   
     
