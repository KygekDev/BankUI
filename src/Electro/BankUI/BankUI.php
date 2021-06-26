<?php

/*
 * [FORKED] A BankUI Plugin For PocketMine-MP
 * Copyright (C) 2021 ElectroGamesYT, KygekDev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace Electro\BankUI;

use onebone\economyapi\EconomyAPI;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;

class BankUI extends PluginBase implements Listener{

    private const IS_BETA = true;

    private static $instance;

    public function onEnable()
    {
        self::$instance = $this;
        $this->getLogger()->warning("You are using forked version of BankUI, maintained by KygekDev. There might be no support from the original developer if you use this plugin.");
        /** @phpstan-ignore-next-line */
        if (self::IS_BETA) {
            $this->getLogger()->warning("This plugin is under BETA. There may be some bugs. Use this plugin with caution. DSIDWY!");
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        if (!file_exists($this->getDataFolder() . "Players")){
            mkdir($this->getDataFolder() . "Players");
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (!file_exists($this->getDataFolder() . "Players/" . $player->getName() . ".yml")) {
            new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML, array(
                "Money" => 0,
            ));
        }
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool{
        switch($command->getName()){
            case "bank":
                if (!$sender instanceof Player) {
                    $sender->sendMessage("§aThis command can only be executed by a player!");
                    return true;
                }
                $this->bankForm($sender);
        }
        return true;
    }

    public function bankForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);

        $form = new SimpleForm(function (Player $player, int $data = null) use ($playerBankMoney) {
            if ($data === null) {
                return true;
            }

            switch ($data) {
                case 0:
                    $this->withdrawForm($player);
                    break;
                case 1:
                    $this->depositForm($player);
                    break;
                case 2:
                    if ($playerBankMoney->get("Money") == 0){
                        $player->sendMessage("§aYou have no money in the bank to transfer money");
                        return true;
                    }
                    $this->transferCustomForm($player);
            }
        });

        $form->setTitle("§lBank");
        $form->setContent("Balance: $" . $playerBankMoney->get("Money"));
        $form->addButton("§lWithdraw Money\n§r§dClick to withdraw...",0,"textures/ui/icon_book_writable");
        $form->addButton("§lDeposit Money\n§r§dClick to deposit...",0,"textures/items/map_filled");
        $form->addButton("§lTransfer Money\n§r§dClick to transfer...",0,"textures/ui/FriendsIcon");
        $form->addButton("§l§cEXIT\n§r§dClick to Close...",0,"textures/ui/cancel");

        $player->sendForm($form);
    }

    public function withdrawForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);

        $form = new SimpleForm(function (Player $player, int $data = null) use ($playerBankMoney) {
            if ($data === null) {
                return true;
            }

            if ($playerBankMoney->get("Money") == 0){
                $player->sendMessage("§aYou have no money in the bank to withdraw");
                return true;
            }

            switch ($data) {
                case 0:
                    EconomyAPI::getInstance()->addMoney($player, $playerBankMoney->get("Money"));
                    $player->sendMessage("§aYou have withdrew $" . $playerBankMoney->get("Money") . " from the bank");
                    $playerBankMoney->set("Money", 0);
                    $playerBankMoney->save();
                    break;
                case 1:
                    EconomyAPI::getInstance()->addMoney($player, $playerBankMoney->get("Money") / 2);
                    $player->sendMessage("§aYou have withdrew $" . $playerBankMoney->get("Money") . " from the bank");
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") / 2);
                    $playerBankMoney->save();
                    break;
                case 2:
                    $this->withdrawCustomForm($player);
            }
        });

        $form->setTitle("§lWithdraw");
        $form->setContent("Balance: $" . $playerBankMoney->get("Money"));
        $form->addButton("§lWithdraw All\n§r§dClick to withdraw...",0,"textures/ui/icon_book_writable");
        $form->addButton("§lWithdraw Half\n§r§dClick to withdraw...",0,"textures/ui/icon_book_writable");
        $form->addButton("§lWithdraw Custom\n§r§dClick to withdraw...",0,"textures/ui/icon_book_writable");
        $form->addButton("§l§cEXIT\n§r§dClick to Close...",0,"textures/ui/cancel");

        $player->sendForm($form);
    }

    public function withdrawCustomForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);

        $form = new CustomForm(function (Player $player, $data) use ($playerBankMoney) {
            if ($data === null) {
                return true;
            }

            EconomyAPI::getInstance()->addMoney($player, $data[1]);
            $player->sendMessage("§aYou have withdrew $" . $data[1] . " from the bank");
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[1]);
            $playerBankMoney->save();
        });

        $form->setTitle("§lWithdraw");
        $form->addLabel("Balance: $" . $playerBankMoney->get("Money"));
        $form->addSlider("§rSelect amount to withdraw", 1, $playerBankMoney->get("Money"));

        $player->sendForm($form);
    }


    public function depositForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $playerMoney = EconomyAPI::getInstance()->myMoney($player);

        $form = new SimpleForm(function (Player $player, int $data = null) use ($playerBankMoney, $playerMoney){
            if ($data === null) {
                return true;
            }

            if ($playerMoney == 0){
                $player->sendMessage("§aYou do not have enough money to deposit into the bank");
                return true;
            }

            switch ($data) {
                case 0:
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + $playerMoney);
                    $player->sendMessage("§aYou have deposited $" . $playerMoney . " into the bank");
                    EconomyAPI::getInstance()->reduceMoney($player, $playerMoney);
                    $playerBankMoney->save();
                    break;
                case 1:
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + ($playerMoney / 2));
                    $player->sendMessage("§aYou have deposited $" . $playerMoney / 2 . " into the bank");
                    EconomyAPI::getInstance()->reduceMoney($player, $playerMoney / 2);
                    $playerBankMoney->save();
                    break;
                case 2:
                    $this->depositCustomForm($player);
            }
        });

        $form->setTitle("§lDeposit");
        $form->setContent("Balance: $" . $playerBankMoney->get("Money"));
        $form->addButton("§lDeposit All\n§r§dClick to deposit...",0,"textures/items/map_filled");
        $form->addButton("§lDeposit Half\n§r§dClick to deposit...",0,"textures/items/map_filled");
        $form->addButton("§lDeposit Custom\n§r§dClick to deposit...",0,"textures/items/map_filled");
        $form->addButton("§l§cEXIT\n§r§dClick to Close...",0,"textures/ui/cancel");

        $player->sendForm($form);
    }

    public function depositCustomForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $playerMoney = EconomyAPI::getInstance()->myMoney($player);

        $form = new CustomForm(function (Player $player, $data) use ($playerBankMoney) {
            if ($data === null) {
                return true;
            }

            $player->sendMessage("§aYou have deposited $" . $data[1] . " into the bank");
            $playerBankMoney->set("Money", $playerBankMoney->get("Money", 0) + $data[1]);
            EconomyAPI::getInstance()->reduceMoney($player, $data[1]);
            $playerBankMoney->save();
        });

        $form->setTitle("§lDeposit");
        $form->addLabel("Balance: $" . $playerBankMoney->get("Money"));
        $form->addSlider("§rSelect amount to deposit", 1, (int) floor($playerMoney));

        $player->sendForm($form);
    }

    public function transferCustomForm(Player $player) {

        $list = [];
        foreach ($this->getServer()->getOnlinePlayers() as $players){
            if ($players->getName() !== $player->getName()) {
                $list[] = $players->getName();
            }
        }

        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);

        $form = new CustomForm(function (Player $player, $data) use ($list, $playerBankMoney) {
            if ($data === null) {
                return true;
            }

            $playerName = $list[$data[1]];
            $otherPlayerBankMoney = new Config($this->getDataFolder() . "Players/" . $playerName . ".yml", Config::YAML);
            $otherPlayer = $this->getServer()->getPlayer($playerName);

            $player->sendMessage("§aYou have transferred $" . $data[2] . " into " . $playerName . "'s bank account");
            $otherPlayer->sendMessage("§a" . $player->getName() . " has transferred $" . $data[2] . " into your bank account");
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[2]);
            $otherPlayerBankMoney->set("Money", $otherPlayerBankMoney->get("Money", 0) + $data[2]);
            $playerBankMoney->save();
            $otherPlayerBankMoney->save();
        });

        $form->setTitle("§lWithdraw");
        $form->addLabel("Balance: $" . $playerBankMoney->get("Money"));
        $form->addDropdown("Select a Player", $list);
        $form->addSlider("§rSelect amount to transfer", 1, $playerBankMoney->get("Money"));

        $player->sendForm($form);
    }

    public static function getInstance(): BankUI {
        return self::$instance;
    }

}
