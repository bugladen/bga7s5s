# Sanjay +1 Riposte vs Roll the Bones additive stats

## Bug

User clarification: Sanjay only gets +1 Riposte on his **gambled combat card**. The card chosen via Roll the Bones (01114) is **not** a combat card — its R/P/T are added to 01114.

`stApplyCombatCardStats` already tried to exclude RtB additive from `$event->gambled`, but the exclusion only gated the FREE/`DUEL_GAMBLED` half:

```php
$event->gambled = $gambled == 1 || (! $isRollTheBonesGambleStats && DUEL_GAMBLED);
```

When Sanjay gambles for 01114 itself (`duel_round.gambled = 1`) then uses RtB, the additive pass still saw `$gambled == 1` → Sanjay fired a **second** +1 Riposte on values that aren't from a combat card.

Pure RtB (01114 from hand) was already fine — DB flag null + RtB exclusion on the OR second half.

## Fix

Always force `gambled=false` for `$isRollTheBonesGambleStats`:

```php
$event->gambled = ! $isRollTheBonesGambleStats && ($gambled == 1 || DUEL_GAMBLED);
```

WHY here not in Sanjay: any "gambled combat card" passive should share the same semantics. Sanjay already got +1 on 01114's own stats pass when 01114 was the gambled combat card.

## Docs

Skill Pattern A / SKILL.md previously said `$event->gambled` "includes Roll-the-Bones paths" — that was wrong/misleading. Updated to say RtB additive forces gambled=false.

## Tests

`Card_03037_Test` — gambled=true gets +1; gambled=false (RtB additive shape) does not.
