# Economic victory: most-Renown tiebreak missing

## Bug report

User: Day 4 Plunder, 7 Renown vs 10 Renown. Both at 7+. Game said multiple economic victories → another day. User correctly expected the 10-Renown player to win (most Renown is the first economic tiebreak).

## Rules (Comprehensive Rulebook)

- Economic Victory: 7+ Renown during Plunder.
- If two+ achieve Economic at once → **player with most Renown wins**.
- If those players are **tied** on Renown → play another Day (max 5).
- Day 5 Renown ties then go locations → Influence → Leader wounds → draw.

## What was wrong

`stPlunderCheckEconomicVictory` treated `count($winners) > 1` as always "another day" when `$day < 5`. It never compared Renown among the 7+ players. The regression suite encoded that wrong behavior (`8 vs 9 → another day`).

## Fix

When multiple players are at 7+:
1. Find unique highest Renown among them → that player wins Economic immediately.
2. Exact Renown tie → "another day" message if day &lt; 5; on day 5 fall through silently to `stPlunderCheckFifthDayVictory`.

Updated tests:
- 7 vs 10 on day 4 → Player Two Economic win (user's case)
- 8 vs 8 before day 5 → another day
- 8 vs 8 on day 5 → silent fallthrough

## Why not reuse fifth-day logic

Fifth-day also handles &lt;7 Renown and deeper location/Influence/wounds tiers. Economic mid-game only uses Renown among 7+ players, then "another day" — keep it local and short.
