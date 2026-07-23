> Part of **create-city-event-card**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pre-Commit Hook Gotchas (from `.githooks/pre-commit`)

| Pattern | Required |
|---|---|
| `implements ISorcererAbility` | `createSorcererAbilityStartEvent()` AND `createSorcererAbilityPlayedEvent()` |
| `extends Attachment/Card/Character/Risk/RiskCity/Scheme/SchemeCityAction` | `createActionResolvedEvent()` |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()` |
| `extends RiskReaction` | Check `Location == Game::LOCATION_HAND` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` |
| **Forbidden** | Implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on one class |
| **Forbidden in subclasses** | `setUsed`/`resetPlayerPassCount`/`announceAction` in `CharacterAction/AttachmentAction/SchemeAction/SchemeCityAction` |

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line (see `.cursor/rules/php-brace-style.mdc`).
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action: `...\cards\<expansion>\actions`
  - Reaction: `...\cards\<expansion>\reactions`
  - State: `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- Hand-discard interactive states: wire `factionHand.setSelectionMode('single')` in OnEntering, Confirm via `onCardDiscarded` in OnUpdateActionButtons, clear selection mode in OnLeaving, **and** add the state name to `EventHandlers.js` so `actChooseDiscardCards` enables when a card is selected (easy to miss — see `highDramaPhase03042` / `highDramaPhase04cd09_2`).
- bas State IDs: `404XXXX` (see Knives Out `4040009` / `40400092` / `40400093`).
