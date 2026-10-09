# Combat-card Maneuver/Technique column notes — fill remaining gap

## Ask
Wire `recordDuelRoundColumnNote` for every card with the So It Begins gap (combat-card R/P/T mod with no column home).

## Already done (prior turn)
01183 So It Begins, 01125 Guile, 01116 Yevgeni, Technique_01204.

## This pass
**Passives (maneuver column):**
- `_01043` Uwe +1 Thrust
- `_01122` Torsten +1 Thrust
- `_03037` Sanjay +1 Riposte (gambled)
- `_01121` Ren -1 Parry (combat-card path only — Maneuver/Technique calc already have columns)
- `_01195` Eager Blade +1 Riposte
- `Action_04009` Rattle the Rigging +1 Riposte

**Deferred (maneuver/technique column):**
- `Technique_01193` Burnished Cuirass -1 Thrust → technique column (twin of 01204)
- `Maneuver_01084` Valroux +1 Thrust next → maneuver
- `Maneuver_01135` Mireli's -2 Thrust → maneuver; added `sourceName` to pending global (inject code stays for chat)
- `Reaction_02017` Panzerhand -1 Riposte → maneuver

**Also found during implement:** Unravel (`EventHub`) +1 Parry on Sorcery — same gap, note in hub (`note_unravel`) because card may already be back in deck.

Skip dashed R/P/T everywhere. Plain Name, not inject code.

## Not touched
Lethal-only, escape/side effects, same-round Maneuver/Technique calc.
