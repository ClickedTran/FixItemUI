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
       	$player->sendMessage($config->getNested("money.message-fail"));
       } else {
           $this->fixMoney($player);   
      }
      break;
     
      case 2:
      $config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
      $exp = $player->getXpManager()->getXpLevel();
      $price_exp = $config->getNested("exp.price");
       if($exp < $price_exp){
       	$player->sendMessage($config->getNested("exp.message-fail"));
       } else {
           $this->fixExp($player);  
      }
      break;
     }
   });
   $form->setTitle("§l§a•[ §bMenu §cSửa Chữa Item§a ]•");
   $form->addButton("§l§a• §cThoát§a •" );
   
   if($money < $price_money){
       $form->addButton("§l§a• §cFix §bMoney§a •\n§bBạn Không Đủ Tiền Để Sửa Chữa");
   }else{
   	$form->addButton("§l§a• §cFix §bMoney§a •");
   }
   
   if($exp < $price_exp){
        $form->addButton("§l§a• §cFix §bExp§a •\n§bBạn Không Đủ Tiền Để Sửa Chữa");
   }else{
   	 $form->addButton("§l§a• §cFix §bExp§a •");
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
      $meta = $item->getDamage();
      $cash = $meta * $config->getNested("money.percent");
          if($money >= $cash){
            $eco->reduceMoney($player, $cash);
            $item = $player->getInventory()->getItemInHand();
				      if($item instanceof Armor or $item instanceof Tool){
				        $id = $item->getId();
					      $meta = $item->getDamage();
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
    $form->setTitle("§l§a•[ §cSửa Chữa Item§b Money§a ]•");
    $form->addButton("§l§a• §cKhông §a•\n§bCho Tôi Quay Lại");
    $form->addButton("§l§a• §cCó§a •");
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
      $meta = $item->getDamage();
      $cash = $meta * $config->getNested("exp.percent");
          if($exp >= $cash){
            $player->getXpManager()->setXpLevel($exp - $cash);
            $item = $player->getInventory()->getItemInHand();
				      if($item instanceof Armor or $item instanceof Tool){
				        $id = $item->getId();
					      $meta = $item->getDamage();
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
    $form->setTitle("§l§a•[ §cSửa Chữa Item§b EXP§a ]•");
    $form->addButton("§l§a• §cKhông §a•\n§bCho Tôi Quay Lại");
    $form->addButton("§l§a• §cCó§a •");
    $form->sendToPlayer($player);
  }
}
    
   
     
