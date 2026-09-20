# 08 — Passives, Forced, and discounts

← [[07 — Reactions|Risk Guide Reactions]] · [[Index|Implementing a Risk Card]] · Next: [[09 — Wiring states and JavaScript|Risk Guide Wiring States And JavaScript]]

Not every printed clause is an Action, Maneuver, or Reaction. This page covers **Forced**, always-on modifiers, and **cost discounts**.

## Forced (no player choice) — on the Risk class

`<b>Forced:</b>` with no chooser belongs on `_NNNNN::handleEvent`, **not** a separate ability file. There is no Forced base class.

Always call `parent::handleEvent($event)` first.

### Common duel-line Forced

**"After your adversary is destroyed, if this card is in your dueling line • …"**

Gate all of:

1. `EventCharacterDestroyed`
2. `$this->Location == Game::LOCATION_DUELING_LINE`
3. `Game::IN_DUEL` is truthy
4. Destroyed character is **your adversary** (use challenger/defender ids — not "anyone in the duel")

Then heal / draw / etc. as printed.

| Effect | Mirror |
|---|---|
| Heal participant (if wounded and in play) | `_03033` Glorious |
| Draw a card | `_03073` Victorious |

## Combat-card cost discounts (with a Maneuver)

Printed "this card has −1 cost when …" during **combat-card pay** lives on the **Maneuver**:

```php
public function getManeuverFromCombatCardDiscount(
    Theah $theah,
    Card $combatCard,
    Maneuver $maneuver,
    array &$explanations
): int
```

Always gate `$owner->Id == $combatCard->Id` so copied/other cards do not inherit the discount.

| Printed condition | Gate |
|---|---|
| While the adversary is engaged | `$adversary->Engaged` — `Maneuver_01084` |
| Participant has more [Stat] than adversary | Modified-stat `>` — `Maneuver_03036` |
| If this card was gambled | `Game::DUEL_GAMBLED` — `Maneuver_03048` |

Push a translated explanation when the discount applies.

## Action-only Leader discounts (no Maneuver)

When text discounts Wealth based on Leader traits **and the Risk has an Action but no Maneuver**, put the discount on the Action:

```php
public function getActionFromHandDiscount(...): int
```

Gate `$action->Id == $this->Id`, null-check the Leader, then add `1` when traits match.

**Do not invent a Maneuver** solely to carry a combat-card discount. Cards like Appealing (`_01159`), Bleed Out (`_01160`), and Leverage (`_03071`) pay **full** WealthCost when played as combat cards.

## Other passives on the Risk class

Rare always-on duel modifiers can listen to events like `EventDuelCalculateCombatCardStats` on the Risk itself. Prefer Maneuver calc when the effect only matters while this card is the combat card.

## Forced vs Reaction (quick test)

| Question | Forced | Reaction |
|---|---|---|
| Does the player get a Use / Pass menu? | No | Yes |
| Separate PHP ability file? | No — on Risk | Yes — `RiskReaction` |
| Pays Wealth / discards the Risk? | Usually no | Yes (from hand) |

If the print says **Forced:** and there is no choice, do not build a Reaction "for consistency."

## Next

Need custom states or JS? → [[09 — Wiring states and JavaScript|Risk Guide Wiring States And JavaScript]]  
Otherwise → [[10 — Finish checklist|Risk Guide Finish Checklist]]
