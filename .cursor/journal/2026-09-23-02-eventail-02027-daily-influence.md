# Éventail (02027) — stacked Influence bug (Guillén)

## Report
User: Guillén (01064, base 2 Inf) en garde with Éventail → reads 4 Inf at start of next day / at home. Suspected day transition refresh.

## Root cause (confirmed)
Not a daily refresh. Stack from silent move-engage + dusk engarde.

1. Equip while en garde → +1 (Inf 3). Correct.
2. City-to-city Move Action: `createCardMovingEvent(..., engage=true)`. EventHub sets `Engaged=true` on `EventCardMoved` **without** firing `EventCardEngaged`.
3. 02027 only listened to `EventCardEngaged` for the −1, so bonus stayed on while engaged.
4. Dusk cleanup: if Engaged → `EventCardEngarded` → 02027 +1 again → Inf 4.

Matches "very start of next day" / "4 while at home" — dusk moves home then engardes.

Known quirk (Nazem audit 2026-03-26, Angeline): move engage is silent; cards that care must handle `EventCardMoved` with `$event->engage`.

Home→city move uses `engage=false` (first leave home). Stack needs at least one engaging move (or other silent engage) before dusk engarde.

## Fix
`_02027.php`: on `EventCardMoved` when attached char, `engage`, and not already Engaged → `modifyInfluence(-1)`. Same timing as `EventCardEngaged` (`runEventHubAfterCards=true`).

## Not done
- No Condition/idempotent guard — Eddie previously rejected Condition as overengineered; targeted event coverage matches other cards.
- Live games already at 4 stay wrong until unequip/re-equip or engage/engarde cycle after deploy.

## Guillén note
Diplomat+Merchant, Inf 2, only dynamic Combat from renown — not the extra Inf source.
