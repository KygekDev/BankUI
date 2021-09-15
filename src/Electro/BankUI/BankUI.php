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

    public function onEnable() {
        $this->saveDefaultConfig();
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
        date_default_timezone_set($this->getConfig()->get("timezone"));
        if ($this->getConfig()->get("enable-interest") == true) {
            $this->getScheduler()->scheduleRepeatingTask(new InterestTask($this), 1100);
        }
    }

    public function dailyInterest(){
        if (date("H:i") === "12:00"){
            foreach (glob($this->getDataFolder() . "Players/*.yml") as $players) {
                $playerBankMoney = new Config($players);
                $interest = ($this->getConfig()->get("interest-rates") / 100 * $playerBankMoney->get("Money"));
                $playerBankMoney->set("Money", round($playerBankMoney->get("Money") + $interest));
                $playerBankMoney->save();
                if ($playerBankMoney->get('Transactions') === 0){
                    $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aInterest $" . round($interest) . "\n");
                } else {
                    $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §a$" . round($interest) . " from interest" . "\n");
                }
                $playerBankMoney->save();
            }
            foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayers){
                $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $onlinePlayers->getName() . ".yml", Config::YAML);
                $onlinePlayers->sendMessage("§aYou have earned $" . round(($this->getConfig()->get("interest-rates") / 100) * $playerBankMoney->get("Money")) . " from bank interest");
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if (!file_exists($this->getDataFolder() . "Players/" . $player->getName() . ".yml")) {
            new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML, array(
                "Money" => 0,
                "Transactions" => 0,
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
                if (isset($args[0]) && $sender->hasPermission("bankui.admin") || isset($args[0]) && $sender->isOp()){
                    if (!file_exists($this->getDataFolder() . "Players/" . $args[0] . ".yml")){
                        $sender->sendMessage("§c§lError: §r§aThis player does not have a bank account");
                        return true;
                    }
                    $this->otherTransactionsForm($sender, $args[0]);
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
                    break;
                case 3:
                    $this->transactionsForm($player);
            }
        });

        $form->setTitle("§lBank Menu");
        $form->setContent("Balance: $" . $playerBankMoney->get("Money"));
        $form->addButton("§lWithdraw Money\n§r§dClick to withdraw...",0,"textures/ui/icon_book_writable");
        $form->addButton("§lDeposit Money\n§r§dClick to deposit...",0,"textures/items/map_filled");
        $form->addButton("§lTransfer Money\n§r§dClick to transfer...",0,"textures/ui/FriendsIcon");
        $form->addButton("§lTransactions\n§r§dClick to open...",0,"textures/ui/lock_color");
        $form->addButton("§l§cEXIT\n§r§dClick to close...",0,"textures/ui/cancel");

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
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") . "\n");
                    } else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") . "\n");
                    }
                    $playerBankMoney->set("Money", 0);
                    $playerBankMoney->save();
                    break;
                case 1:
                    EconomyAPI::getInstance()->addMoney($player, $playerBankMoney->get("Money") / 2);
                    $player->sendMessage("§aYou have withdrew $" . $playerBankMoney->get("Money") /2 . " from the bank");
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") / 2 . "\n");
                    } else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $playerBankMoney->get("Money") / 2 . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") / 2);
                    $playerBankMoney->save();
                    break;
                case 2:
                    $this->withdrawCustomForm($player);
            }
        });

        $form->setTitle("§lWithdraw Menu");
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

            if ($data[1] <= 0){
                $player->sendMessage("§aYou must enter an amount greater than 0");
                return true;
            }

            EconomyAPI::getInstance()->addMoney($player, $data[1]);
            $player->sendMessage("§aYou have withdrew $" . $data[1] . " from the bank");

            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aWithdrew $" . $data[1] . "\n");
            } else {
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aWithdrew $" . $data[1] . "\n");
            }

            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[1]);
            $playerBankMoney->save();
        });

        $form->setTitle("§lWithdraw Menu");
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
                case 0;
                    $playerMoney = EconomyAPI::getInstance()->myMoney($player);
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerMoney == 0){
                        $player->sendMessage("§aYou do not have enough money to deposit into the bank");
                        return true;
                    }
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney . "\n");
                    } else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + $playerMoney);
                    $player->sendMessage("§aYou have deposited $" . $playerMoney . " into the bank");
                    EconomyAPI::getInstance()->reduceMoney($player, $playerMoney);
                    $playerBankMoney->save();
                    break;
                case 1;
                    $playerMoney = EconomyAPI::getInstance()->myMoney($player);
                    $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
                    if ($playerMoney == 0){
                        $player->sendMessage("§aYou do not have enough money to deposit into the bank");
                        return true;
                    }
                    if ($playerBankMoney->get('Transactions') === 0){
                        $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney / 2 . "\n");
                    } else {
                        $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $playerMoney / 2 . "\n");
                    }
                    $playerBankMoney->set("Money", $playerBankMoney->get("Money") + ($playerMoney / 2));
                    $player->sendMessage("§aYou have deposited $" . $playerMoney / 2 . " into the bank");
                    EconomyAPI::getInstance()->reduceMoney($player, $playerMoney / 2);
                    $playerBankMoney->save();
                    break;
                case 2:
                    $this->depositCustomForm($player);
            }
        });

        $form->setTitle("§lDeposit Menu");
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

            $playerMoney = EconomyAPI::getInstance()->myMoney($player);
            $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);

            if ($playerMoney < $data[1]){
                $player->sendMessage("§aYou do not have enough money to deposit $" . $data[1] . " into the bank");
                return true;
            }
            if (!is_numeric($data[1])){
                $player->sendMessage("§aYou did not enter a valid amount");
                return true;
            }
            if ($data[1] <= 0){
                $player->sendMessage("§aYou must enter an amount greater than 0");
                return true;
            }

            $player->sendMessage("§aYou have deposited $" . $data[1] . " into the bank");

            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aDeposited $" . $data[1] . "\n");
            } else {
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aDeposited $" . $data[1] . "\n");
            }
            $playerBankMoney->set("Money", $playerBankMoney->get("Money") + $data[1]);
            EconomyAPI::getInstance()->reduceMoney($player, $data[1]);
            $playerBankMoney->save();
        });

        $form->setTitle("§lDeposit Menu");
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

            if ($playerBankMoney->get("Money") == 0){
                $player->sendMessage("§aYou have no money in the bank to transfer money");
                return true;
            }
            if ($playerBankMoney->get("Money") < $data[2]){
                $player->sendMessage("§aYou do not have enough money in your bank to transfer $" . $data[2]);
                return true;
            }

            if (!is_numeric($data[2])){
                $player->sendMessage("§aYou did not enter a valid amount");
                return true;
            }
            if ($data[2] <= 0){
                $player->sendMessage("§aYou must transfer at least $1");
                return true;
            }

            $player->sendMessage("§aYou have transferred $" . $data[2] . " into " . $playerName . "'s bank account");

            if ($this->getServer()->getPlayer($playerName)) {
                $otherPlayer = $this->getServer()->getPlayer($playerName);
                $otherPlayer->sendMessage("§a" . $player->getName() . " has transferred $" . $data[2] . " into your bank account");
            }

            if ($playerBankMoney->get('Transactions') === 0){
                $playerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §aTransferred $" . $data[2] . " into " . $playerName . "'s bank account" . "\n");
                $otherPlayerBankMoney->set('Transactions', date("§b[d/m/y]") . "§e - §a" . $player->getName() . " Transferred $" . $data[2] . " into your bank account" . "\n");
            } else {
                $otherPlayerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §a" . $player->getName() . " Transferred $" . $data[2] . " into your bank account" . "\n");
                $playerBankMoney->set('Transactions', $playerBankMoney->get('Transactions') . date("§b[d/m/y]") . "§e - §aTransferred $" . $data[2] . " into " . $playerName . "'s bank account" . "\n");
            }

            $playerBankMoney->set("Money", $playerBankMoney->get("Money") - $data[2]);
            $otherPlayerBankMoney->set("Money", $otherPlayerBankMoney->get("Money", 0) + $data[2]);
            $playerBankMoney->save();
            $otherPlayerBankMoney->save();
        });

        $form->setTitle("§lTransfer Menu");
        $form->addLabel("Balance: $" . $playerBankMoney->get("Money"));
        $form->addDropdown("Select a Player", $list);
        $form->addSlider("§rSelect amount to transfer", 1, $playerBankMoney->get("Money"));

        $player->sendForm($form);
    }

    public function transactionsForm(Player $player) {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player->getName() . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){});

        $form->setTitle("§lTransactions Menu");
        if ($playerBankMoney->get('Transactions') === 0){
            $form->setContent("You have not made any transactions yet");
        } else {
            $form->setContent($playerBankMoney->get("Transactions"));
        }
        $form->addButton("§l§cEXIT\n§r§dClick to close...",0,"textures/ui/cancel");

        $player->sendForm($form);
    }

    public function otherTransactionsForm($sender, Player $player)
    {
        $playerBankMoney = new Config($this->getDataFolder() . "Players/" . $player . ".yml", Config::YAML);
        $form = new SimpleForm(function (Player $player, int $data = null){});

        $form->setTitle("§l" . $player . "'s Transactions");
        if ($playerBankMoney->get('Transactions') === 0){
            $form->setContent($player . " has not made any transactions yet");
        } else {
            $form->setContent($playerBankMoney->get("Transactions"));
        }
        $form->addButton("§l§cEXIT\n§r§dClick to close...",0,"textures/ui/cancel");

        $player->sendForm($form);
    }

    public static function getInstance(): BankUI {
        return self::$instance;
    }

}
