# Smash and Grab (_05Teens) Covert Action

Previous context today: Teapot / BoyBand / Hulk, same CAD prototype rule (no translate on this card - plain strings in PHP and JS).

Printed: Covert Action: Engage your performer . Destroy target opposing character's equipped attachment. You may take control of it and equip it to your character paying all costs.

## Shape (no exact row in the create-risk skill; composed)

- A.13 (`_04040`): Target character -> attachment name buttons -> `EventCharacterTargeted` hook; destroy only if the hook survives.
- B.9 / Robbery (`_04029`, `Action_01113`): pull attachment to hand -> `PAY_STATE_EQUIP_ATTACHMENT` -> equip.
- Hulk (`_05Hulk`): Engage at `announceAction`.
- Covert gate from Forsythe/Overcoat: performer's City location must be uncontrolled.

4 GameStates in `States/cad/`, ids 405019 / ...2 / ...3 / ...4. Only `05Teens`, `05Teens_3`, `05Teens_4` are keys under `HIGH_DRAMA_PLAYER_TURN_EVENTS` (`_2` is reached directly from state 1). JS trio in the `*.cad.js` files + `EventHandlers.js` `payForCard` for `_4`.

## WHY these choices

- **RiskAction, not RiskCityAction**: heading says "Covert", not "City". City-ness comes from Covert (uncontrolled City location), checked explicitly (`cardInCity` + `!isControlled()`). Home performers are filtered out by that, not by the base class.
- **Engage at announce, so no Back button anywhere**: printed before the bullet = cost. Irreversible, so states 1-3 have no `back` transition and the JS has no `<` button.
- **Real destroy, then pull back out** (not the `_04029` stage-through-player-discard shortcut): text says "Destroy ... You may take control of it", so the card really lands in a discard pile (destroy triggers fire). Step 3 reads where it landed: `Discard-{OwnerId}` -> `createCardRemovedFromPlayerDiscardPileEvent`, `City Discard` (CityAttachment) -> `createCardRemovedFromCityDiscardPileEvent`. `EventCardDiscardedFromPlay` handler moves synchronously, so queuing the `05Teens_3` transition after the discard (FIFO) is enough - no LOW priority trick like William.
- **Transition to `_3` is queued inside the targeting-hook handler**, not in the act: a canceled target (Unyielding Loyalty etc.) then queues nothing, same as `Action_04040`.
- **"your character" = any of your characters in play (home or city)**, not just the performer. The print says "your character", distinct from "your performer". Candidates are filtered by `canAttachTo`, `hasEquipRestrictions`, and affordability (`getEquipDiscount`). No Risk wealth reservation like `_04029`: the Risk has already left the hand by the time `_3` runs.
- **CHOSEN_PERFORMER is re-pointed at the equipping character in `_3`.** WHY: `Theah::calculateInHandPayDiscount` for `PAY_STATE_EQUIP_ATTACHMENT` reads CHOSEN_PERFORMER as the equip target (William does the same). The real performer already paid Engage at announce.
- **FakeAttachment excluded** from "equipped" (same as `_04040` / `_03072`). Not verified what the right behavior is for destroying a RiskAttachment; skipped on purpose.
- **No runtime re-check of "uncontrolled" after announce** (Forsythe has one). WHY: Engage is already paid and the Risk is spent; throwing in the chooser would strand the player.
- `IRiskThatTargetsCharacters` on the Risk + `IAbilityThatTargetsCharacters` on the Action (print says "target opposing character"). Not `IAbilityThatTargetsCards`: the attachment is a sub-choice, and the hook forbids both on one class. No Cesca allow-list change.
- Pass on `_3` is always legal ("may"), and resolves the action.

## Not done / open

- `_3` has no UI cue for *which* attachment was destroyed besides the log. Could add the name to the title.
- Did not update the create-risk skill (user did not ask this time). A new row would be "Covert Action: Target opposing equipped -> destroy -> may take control + equip" composing A.13 + B.9.
- If the equip is cancelled after pay, the attachment would sit in hand (same exposure as `_04029` / Robbery). Did not investigate.

## Tests

`tests/regression/cad/Action_05Teens_Test.php`, 10 cases. `setup()` is reserved by the harness `TestCase` - use another name for helpers. Single-suite path is relative to `tests/` (`php tests/run.php regression/cad/...`). Full run: 5048 passed, 0 failed.
Gotcha: PowerShell 5 `Set-Content -Encoding UTF8` adds a BOM, which breaks `declare(strict_types=1)` - strip it.

## Opinion

Biggest cost was the four states; the logic itself is all reuse. The one real judgement call was "destroy then take" vs the Robbery shortcut; I went literal. FIFO + synchronous discard made it cheap.

## Skill update (create-risk)

User asked afterwards. Added Pattern B.13 to `pattern-b.md`, a shape-table row + companion-index text + trigger phrases + sibling sentence + finish-list trap clause in `SKILL.md`, checklist item 79 (+ B.13 in item 1's list), and a `references.md` row. WHY a B pattern and not A: heading is "Covert Action" so it is `RiskAction`; the reusable lessons are Covert-as-gate-not-base-class, Engage at announce => no Back, real destroy then pull from the pile it landed in (vs `_04029` shortcut), "your character" != "your performer", and re-pointing `CHOSEN_PERFORMER` for the equip discount. Also recorded test-harness traps (`setup()` reserved, `handWealthCount` stub = 99, PS5 BOM).
Skill files are CRLF; I normalized after inserting LF content so the git diff stays minimal.
