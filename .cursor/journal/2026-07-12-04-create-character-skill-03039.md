# create-character skill update — Íñigo (_03039) learnings

## Why

Eddie asked to capture Íñigo session learnings into create-character (same as Damya/Sanjay skill updates). Future agents would otherwise invent a Weapon-bonus bool flag, compare hand sizes before the adversary discard, engage on the Home move, miss `EventHandlers.js` for the discard Confirm button, or double-CRLF the PHP files.

## What was added

1. **Canonical ref** `_03039` Íñigo (Avispa Mordedora)
2. **Ability-shape table** — Weapon-equipped +N Stat; −N Thrust parenthetical gate; adversary discards; post-discard hand En Garde; EndOfRound move Home
3. **Pattern A** — "While equipped with a Weapon (count-transition, not a bool flag)"
4. **Pattern E** — −N Thrust/Riposte cost; Adversary discards a card (Technique); Post-discard hand-size En Garde + EndOfRound move Home
5. **Reference table** — `_03039`, `Technique_03039`, `_01040` Rena, `Technique_01093` Maya
6. **When You Finish** — item 4 clarified (duel transitions map); item 7 EventHandlers.js note; item 35 Íñigo journal; items 45–49 (Weapon passive, −N Thrust, adversary discard, post-discard/Home, **line endings**)

## Feelings

The `\r\r\n` screw-up was embarrassing and user-visible immediately — worth the explicit When You Finish bullet so the next agent doesn't "helpfully" normalize endings. Biggest gameplay regression risk without this update: comparing hand sizes before discard (wrong En Garde) or engaging on the Home move (contradicts printed text vs `_01053`).
