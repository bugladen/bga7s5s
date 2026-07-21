# Matushka's Shears (_03007) Implementation

Implemented FactionAttachment _03007 ("Matushka's Shears", Vodacce) — Strega-only weapon with a Sorcerer City Reaction.

## Card Text
> May only equip to your **Strega**.
> **Sorcerer City Reaction:** When an opposing character is sent to **The Locker**, engage this card • Their controller wounds their **Leader** unless they sink two cards from their hand.

## Design

Two files:
1. `_03007.php` — FactionAttachment, IHasReactions
2. `reactions/Reaction_03007.php` — AttachmentReaction, ISorcererAbility

### Equip restriction (_03007)
Followed the dual pattern from `_01073` (Cavalier Hat): `eventCheck` throws on `EventAttachmentEquipping` if the target lacks the Strega trait, plus `canAttachTo()` returns false for non-Strega. WHY both: `eventCheck` enforces at the framework level when equip is actually attempted; `canAttachTo` gates the UI/discoverability before the player tries.

Used `\Bga\GameFramework\UserException` per memory (BgaUserException is deprecated) — older _01050/_02006 still use BgaUserException, but new code should follow the current convention.

### Reaction stages
Followed Reaction_03006's stage-machine pattern with a private string `$stage`:
- `''` (idle)
- `'offer'` — owner's UI: "Engage and Force Choice" / "Pass"
- `'choose'` — opponent's UI: "Sink two cards" / "Wound Leader"
- `'pick1'`, `'pick2'` — opponent's UI: one button per card in hand

WHY a stage machine: the reaction transitions ownership between the owner and the opponent over multiple steps. The opponent picks specific cards (Reaction_03006 pattern), not random — so each pick is a separate reaction step.

### Trigger
`handleEvent` only handles `EventCardSentToLocker`. Validates:
- Reaction available (`$this->isAvailable()`)
- Owner is attached (`$this->ownerIsAttached()`) — required by pre-commit hook
- Owner is not already engaged (engage cost)
- Locker'd card is a Character (the event fires for schemes/attachments too)
- `$event->playerId` is the opposing controller (opposing = different player)

### Pre-commit hook compliance
AttachmentReaction + ISorcererAbility class requires:
- `createSorcererAbilityStartEvent` — fired on accepting offer
- `createSorcererAbilityPlayedEvent` — fired on finalize (after wound or pick2)
- `$this->setUsed()` — in `finalize()`
- `$this->isAvailable()` — guard in `handleEvent`
- `$this->ownerIsAttached()` — guard in `handleEvent`

### Edge cases handled
- Opponent has < 2 cards in hand AND no Leader → no effect, just reset (still pays engage)
- Opponent has < 2 cards but has Leader → auto-wound (skip 'choose' stage)
- Opponent has ≥ 2 cards but no Leader → 'choose' offers only "Sink"
- Opponent has both → 'choose' offers both buttons

### Not implemented
- `IAbilityThatTargetsCharacters` — the Leader isn't a player-selected target. Reaction_03006 also skips this for analogous opponent-targeting effects. If this ever needs to be cancellable by ability-cancelers (e.g. Reaction_01122 patterns), revisit.
