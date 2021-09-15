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

use pocketmine\scheduler\Task;

class InterestTask extends Task {

    /**
     * @var BankUI
     */
    private $plugin;

    public function __construct(BankUI $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($tick) {
        $this->plugin->dailyInterest();
    }

}