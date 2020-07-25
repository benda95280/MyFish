# About it
* *Main use for the plugin was for my child* , i continue only for **you**
If you wanna help me, you will find on what i'm stuck at the bottom of the page


**Main idea and code - Thanks to CortexPE and Altay**

https://github.com/CortexPE/TeaSpoon/
https://github.com/TuranicTeam/Altay

# MyFish
Plugin that add support to be able to fish in PocketMine !
This plugin is not intended to be like Vanilla

# Feature :
Works based on level system (Max 10)
- The distance (Force) of the hook change beased on your level
- You've chance to catch nothing
- Precision when you launch the hook (35°/-35°) (More level, more precise)
- Light Level change the time to catch a fish
- OnScreen display when XP, show current % of the level
- A plugin is availaible in my repo for ScoreBoard
- Fish have a size (CustomNBT name "FishSize") based on EnchantLevel, PlayerLevel, LightLevel
- Player under level 4 cannot fish at night

#Math
- Max distance of the Hook in block : Between 7 and 25
  - Calc : ((25 - (10 - $playerFishingLevel)*2)
- 42% Chance to catch something at lvl 1 (Enchant Level can change it to 46% at Enchant lvl 1, to 52% lvl 3)
- 77% Chance to catch something at lvl 10 (Enchant Level can change it to 80% at Enchant lvl 1, to 83% lvl 3)
  - Calc : (mt_rand($playerFishingLevel, round(11+$lvl+sqrt($playerFishingLevel+2)*2)) <= round(2+$lvl+sqrt($playerFishingLevel)*4.4))
- Fish size is between 3cm to 166cm
  - Calc : round(5 * $playerFishingLevel * (($lvlEnchant+2)/3) * (((-1/15)*$lightLevelAtHook)+2));

# Screenshot :
![Screen1](https://i.imgur.com/K5x2rv6.png)




-----------------



Any return, idea on it (Issue on GH / Discord) would be apreciated !

# Looking to add :
- [ ] Fishing calc is ... weird !
- [ ] Calculation of the LightLevel of the Hook change when in water :(
- [ ] Animation for fish that move the Hook (Like real fish)
- [ ] Animation / Particle to water like Vanilla
- [ ] Add configuration files
- [ ] Add Multi-language support
