# BankUI

[![Discord](https://img.shields.io/discord/856281149503963166?label=Discord)](https://discord.gg/TstDS9jZf7)

A PocketMine-MP plugin which allows players to store their money in a bank. This plugin is a **forked version**, maintained by KygekDev.

**Original Developer:** ElectroGamesYT\
**Fork Maintainer:** KygekDev

# Features

- Withdraw Money into Bank
- Deposit Money from Bank
- Transfer Money to Other Players Bank Accounts
- Command Permission (`bankui.bank`)
- Transaction Log
- Daily Interest
- Configurable Interest Rates
- Admins Can View Other Players Bank Transactions With "/bank {PlayerName}"

# Command

**Command:** `/bank [player]`\
**Description:** Opens The BankUI\
**Permission:** `bankui.bank`\
**Default:** `true`

# Permission

Permission for Admins to View Other Players Bank Transactions: `bankui.admin`

# Config

```
# If true, players will get daily interest for the money in their bank
enable-interest: = true

# Interst Rates is in percentage so if interst-rates = 50, it means 50% Interest Rates, if it is set at 1, it means 1% interest rates. (It is recommended to keep this low)
interest-rates: 1

# Timezones can be found at https://www.php.net/manual/en/timezones.php if you don't know what your doing, keep this at "America/Chicago" (OR IT WILL BREAK THE PLUGIN). Players will recieve their daily interest at 12pm in this timezone.
timezone: America/Chicago
```

# Download

Download the latest build of this plugin in [Poggit CI](https://poggit.pmmp.io/ci/kdpls/BankUI/~).

# Important

This plugin is a **fork** of the [original BankUI plugin](https://github.com/ElectroGamesYT/BankUI). There might be no support from the original developer if you use this plugin. This plugin is also under BETA. There may be some bugs. **Use this plugin with caution. DSIDWY!**

This plugin requires EconomyAPI plugin by onebone.

# Images (Upstream)

### Bank Menu

![Bank1](https://user-images.githubusercontent.com/34932094/124204221-37c3c280-daa4-11eb-826f-8c6511cf9649.png)

### Withdraw Menu

![Bank2](https://user-images.githubusercontent.com/34932094/122729370-b7e55f00-d23e-11eb-8aa6-1d8e8b47e70f.PNG)

### Deposit Menu

![Bank3](https://user-images.githubusercontent.com/34932094/122729371-b7e55f00-d23e-11eb-8a94-ee292bab50f8.PNG)

### Transfer Menu

![Bank4](https://user-images.githubusercontent.com/34932094/122729372-b7e55f00-d23e-11eb-9a8c-f44571718108.PNG)

### Transactions Menu

![Bank5](https://user-images.githubusercontent.com/34932094/124215248-48cafe80-dab9-11eb-930d-df1b113a7d3d.PNG)

# Credits

Original plugin code by [@ElectroGamesYT](https://github.com/ElectroGamesYT).

Icon made by Freepik from www.flaticon.com.
