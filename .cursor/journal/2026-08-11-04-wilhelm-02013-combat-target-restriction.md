# Wilhelm Dünst (_02013) — Combat target restriction fix

## Misread from prior audit

2026-03-17 audit parsed "Wilhelm may only issue [Combat] challenges to Villains, Sorcerers, and Monsters" as TWO rules:
1. Only Combat challenges allowed (block Finesse/Influence)
2. Targets must be V/S/M

User correction: the `[Combat]` qualifier attaches to the **target restriction**, not a blanket challenge-type ban. Wilhelm may issue Finesse/Influence challenges to anyone; only **Combat** challenges are limited to Villains, Sorcerers, and Monsters.

## Fix

- Removed `canChallenge()` override — it filtered all challenge types to require V/S/M opponents at location, blocking Wilhelm from Finesse/Influence card-action performers.
- `eventCheck`: early-return when `CHALLENGE_STAT != STAT_COMBAT`; apply V/S/M defender check only for Combat.
- City Action unchanged — `Action_02013::doEffect` adds Sorcerer before Combat issue, so eventCheck still passes.

## Pattern note (inverse of Térence)

Térence `_03028` bans Combat challenges entirely via eventCheck. Wilhelm restricts **Combat targets only** — same eventCheck hook point, different gate: stat check then trait check, not a stat ban.

Do NOT re-add canChallenge override or non-Combat throw without re-reading card text.
