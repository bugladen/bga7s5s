> Part of **create-city-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Reference Implementations

| Card | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd05.php` + `States/faf/State_duelGambleSetup_03cd05.php` | **Canonical CityAttachment.** Forced wound on equip via `handleEvent` listening for `EventAttachmentEquipped`. Steady-state `getNumberOfGambleCardsToReveal` override (rather than global mutation). New `EventGambleSetup` event and a `DUEL_GAMBLE_SETUP` state pair inserted before the existing auto-state to enable a mid-duel player choice. `actFromCardWithId` on the card class dispatches by state. |
| `modules/php/cards/bas/_04cd01.php` + `actions/Action_04cd01.php` + `Action_04cd01b.php` + `_04cd01_RiskClone.php` | **Two Actions on one CityAttachment.** Engage-this-card + move to adjacent City location. City Action: sink to City Deck bottom at commit + Improvising-style play Risk from opponent discard (RiskClone). First `bas` State/JS module set. See Pattern C. |
| `modules/php/cards/_7s5s/_01106.php` + `actions/Action_01106.php` + `_01106_RiskClone.php` | **Source pattern** for play-Risk-from-opponent-discard. Attachment ports must not subtract owning-card hand wealth. |
| `modules/php/cards/_7s5s/_01198.php` | Passive grant ("equipped character gains Duelist") via paired `EventAttachmentEquipped` / `EventAttachmentUnequipped` handling. |
| `modules/php/cards/tac/_02047.php` | Same passive-grant pattern in a `FactionAttachment` (sibling base class) — useful template even though not a CityAttachment. |
| `modules/php/cards/_7s5s/_01187.php` + `actions/Action_01187.php` | Simple `AttachmentAction` with a "destroy this card" cost — useful template for the unequip + **city discard** pair. |
| `modules/php/cards/_7s5s/_01191.php` + `actions/Action_01191.php` | `AttachmentAction` with the `isAttached()` guard before the destroy step (important for copied effects). |
| `modules/php/cards/faf/actions/Action_03055.php` | Engage-this-card + choose-location move (filtered destinations). Pay engage on location resolve. |
| `modules/php/cards/_7s5s/_01198.php` + `actions/Action_01198.php` | City Action on a CityAttachment that issues a challenge. |
| `modules/php/cards/_7s5s/_01181.php` + `reactions/Reaction_01181.php` | `AttachmentReaction` with cancel+release+skipNextEvent pattern. Engage-cost reaction. |
| `modules/php/cards/_7s5s/_01075.php` | Tabard of the Fallen Musketeer — established the precedent of Forced abilities in attachment `handleEvent` (not via Reaction). Note: this is a `FactionAttachment`, not CityAttachment. |
| `modules/php/cards/faf/_03cd21.php` | **Canonical Pattern G CityAttachment.** "Forced: each Day, the first time an opponent's Risk targets the equipped character • cancel the effects." Intercepts all five Risk-targeting event types, filters by `$source->ControllerId != $this->ControllerId`, manual discard for the `EventAttachmentEquipping` branch, once-per-Day condition cleared at `EventDuskEndOfDay`. Full chip + tooltip plumbing across PHP/JS/CSS. |
| `modules/php/cards/_7s5s/_01186.php` | Maryam Benu Pleroma — same five-event Pattern G shape on a `CityCharacter`. Use as the side-by-side reference when porting to an attachment. Note: contains the chip-id-mismatch bug — do not copy the removal handler verbatim. |
| `modules/php/cards/tac/reactions/Reaction_02048.php` | Blood Like Winter — a `RiskReaction` variant of Pattern G with a player-chosen pressure response. `isFromOpponentRiskThatTargetsCharacters` is the canonical "opponent's Risk" filter. |
| `modules/php/cards/Attachment.php` | The base class — read for helpers: `isAttached`, `attachedTo`, `getRequiredAttachTargetId`, `canAttachTo`. `$this->Title` is the attachment subtitle. |
