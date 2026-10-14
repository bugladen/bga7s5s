# _05Forsythe Syrneth Morosometer — Faction Attachment

## Printed text

1. May only equip to your Academic.
2. **Covert Action:** Engage this card • Move an adjacent Renown to this location.

Test CAD card — no `clienttranslate` / `_()` (user request; same as Overcoat/Volk).

## Classification

| Clause | Pattern |
|---|---|
| Academic equip only | Pattern A dual-gate (`_04036`) |
| Covert Action engage + move adjacent Renown here | Pattern C AttachmentAction + Covert uncontrolled gate + Guillén/Overcoat adjacent-Renown from-picker |

## WHY decisions

- **AttachmentAction not CharacterAction:** host is the FactionAttachment; performer = equipped Academic via `getOwningCharacter`.
- **Covert uncontrolled gate** (availability + resolve re-check): same Valeri parenthetical as Overcoat — Reminder omitted on card but keyword is Covert. No pressure / claim half — just Renown relocate.
- **Engage this card on resolve** with the move (`Action_04036a`), not on `EventActionTriggered` — single commit when destination chosen.
- **From-picker = adjacent with Renown > 0** (Overcoat / Guillén `01064`), destination fixed to host location. Inverted from Ciphered Tome `04036a` (from here → elsewhere).
- **Renown batch:** Moving + Removed + Added(`isMove=true`) + shared `batchId` (`_04034` / Overcoat).
- **File rename** `05Forsythe.php` → `_05Forsythe.php`: `getCardClassName('05Forsythe')` builds `cards\cad\_05Forsythe`.
- **Image** → `05Forsythe.v4.jpg` (asset in image_store).
- **CardNumber = 5** kept (scaffold); collides with Bruno `_05Thomas` but CAD already duplicates (Gary/Coleman = 3). State `405005` free (Thomas has no HD picker state).
- **Skill fold-in (user request):** create-faction-attachment updated — Covert Action + adjacent-Renown-in shape table rows; pattern-c subsections; checklist 16f/16g; references Forsythe; helpers/wiring CAD+Covert notes; compose Academic+Covert combo. WHY separate from Character Overcoat pattern: attachment has no pressure/claim half — only share uncontrolled gate + Renown-in recipe.

## Files

- `_05Forsythe.php` — Academic dual-gate + Actions wire
- `actions/Action_05Forsythe.php`
- `States/cad/State_highDramaPhase05Forsythe.php`
- `States.php` `HIGH_DRAMA_PLAYER_TURN_05FORSYTHE = 405005`
- `states.inc.php` `"05Forsythe"` transition
- CAD JS enter / update / leave (plain strings)

## Done

Implemented. `php -l` clean. No regression suite for CAD Forsythe yet.

## Skill fold-in

Updated `.claude/skills/create-faction-attachment/` with Forsythe learnings (Covert Action on AttachmentAction, renown-in vs renown-out, CAD `_05Name.php` autoload + plain-string prototype).
