## Bloody Entrance 03032 — City Action → Action

User: Action text wrongly said "Sorcerer City Action"; should be "Sorcerer Action" and usable when sorcerer is at Home.

**Root cause:** Action extended `RiskCityAction`, which filters performers to `cardInCity` only and gates availability on having any city character. Printed keyword without "City" = Pattern B (`RiskAction`) — performer pool is home + city.

**Fix:**
1. `_03032.php` Text: `Sorcerer City Action` → `Sorcerer Action`
2. `Action_03032` base: `RiskCityAction` → `RiskAction`

Destination logic already allowed Home→city (and city→Home); only the base-class city-only performer filter was wrong. Mirrors Action_03009 / Action_03045.

Skill already cites `_03032` under Pattern B.1 (wound+move) and A.2 (EXTRA_ACTION_PERFORMER) — A.2 is the follow-up-action mechanic, not "must be City Action."
