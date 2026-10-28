# Syrneth Hand stopgap removal

User asked to remove the "STOPGAP remove-after-deploy" block. That was the load-time former-host heal from `2026-10-22-01-syrneth-hand-steal.md`.

## Removed

- `Theah::repairAttachmentModifiersOnFormerHosts` and its `buildCity` call
- `$notify` param + silent `updateCardObject` block on `releaseAttachmentStillOnOtherCharacters` — method always notifies now
- Card_01204 test "reload strips +1 Combat from a former host who still lists the hand"

## Kept (deliberately)

Equip-time `releaseAttachmentStillOnOtherCharacters` + `statCorrection`. WHY: Syrneth Hand still throws on unequip, `queueEvent` still swallows it, so a future illegal-or-edge equip path still needs the strip when the card actually changes hands. Load heal was only for tables already saved in the bad dual-host state after deploy.

## Tests

`php tests/run.php regression/core/Card_01204_Test.php` — 5 passed (equip strip + move transfer still covered).
