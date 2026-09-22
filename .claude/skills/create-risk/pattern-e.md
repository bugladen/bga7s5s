> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern E — Passive on the Risk class

For "While [condition] …" or other always-on effects of the Risk itself (typically combat-card cost discounts or in-duel stat modifiers), override `eventCheck` / `handleEvent` directly on the Risk class. Don't create an Action/Reaction file for passives.

```php
class _NNNNN extends Risk
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->cardId == $this->Id)
        {
            // ... modify the event
        }
    }
}
```

Combat-card cost discounts ("this card has -1 cost when …") live on the **Maneuver** via `getManeuverFromCombatCardDiscount`, not on the Risk class — the discount applies only when this card is being played as a combat-card maneuver. Gate on `$owner->Id == $combatCard->Id` so copied/other cards do not inherit it. Common predicates:

| Printed condition | Gate |
|---|---|
| "While the adversary is engaged" | `$adversary->Engaged` — `Maneuver_01084` (**Wealth discount only** — not a Maneuver availability cost; contrast `_04058` "If the adversary is engaged • +1 Riposte" where Engaged is the Maneuver cost — see pattern-c.md) |
| "If your participant has more [Finesse] than the adversary" | `$actor->ModifiedFinesse > $adversary->ModifiedFinesse` — `Maneuver_03036` |
| "While the adversary has more wounds than your participant" | `$adversary->Wounds > $actor->Wounds` — `Maneuver_04007a` |
| "If this card was gambled" | `$theah->game->globals->get(Game::DUEL_GAMBLED, false)` — `Maneuver_03048` |

Use Modified stats for Combat/Finesse/Influence comparisons and parse the operator literally (`>` vs `>=`). **Wounds are not Modified stats** — compare `$character->Wounds` directly (`_04007`). For the gambled predicate, `DUEL_GAMBLED` is set in `actChooseGambleCard` before `PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD`, so it is live at discount time. Push a translated explanation into `$explanations` when the discount applies.

### Dual Maneuvers + one card-level "-1 cost" clause

`Card::getManeuverFromCombatCardDiscount` iterates **every** Maneuver on the Risk and **sums** their discounts. A printed card-level clause ("While … this card has -1 cost") must live on **exactly one** Maneuver — typically the first (`Maneuver_NNNNNa`). Putting the same `+= 1` on both `a` and `b` double-counts to −2.

**WHY not put it on the Risk class instead:** combat-card pay only consults Maneuver (and Reaction) discount hooks via that Card loop — there is no separate Risk-class channel at pay time. Pattern E.2 Action discounts are a different path (`getActionFromHandDiscount`).

**WHY not invent a third "discount-only" Maneuver:** the card already prints real Maneuvers; hang the shared clause on one of them (same as single-Maneuver cards `_01084` / `_03036` / `_03048`).

Reference: `_04007` / `Maneuver_04007a` (discount) + `Maneuver_04007b` (calc only).

### Pattern E.2 — Action-only "-1 cost if …" (no Maneuver printed)

When printed text discounts the card's Wealth cost based on an **out-of-duel** condition (Leader traits, **performer** traits, etc.) **and the Risk has an Action/City Action but no Maneuver**, put the discount on the **Action** via `getActionFromHandDiscount` — not on the Risk class, and **not** on a fabricated Maneuver.

```php
public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
{
    $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

    // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
    // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
    if ($action->Id == $this->Id)
    {
        $owner = $this->getOwningCard($theah);
        $leader = $theah->getLeaderByPlayerId($owner->ControllerId);
        if ($leader !== null && ($leader->hasTrait('Villain') || $leader->hasTrait('Pirate')))
        {
            $discount += 1;
            $explanations[] = sprintf(
                $theah->game->translate("%s: -1 because Leader is a Villain or Pirate."),
                $owner->getInjectCode()
            );
        }
    }

    return $discount;
}
```

**Performer-trait variant** (Seek Each Devil `_04018`: **"While your performer is an Academic or Hunter, this card has -1 cost"**; also Rapsodia `_04039`: **"While your performer is a Zealot, Academic, or Bard, this card has -1 cost"**):

```php
if ($action->Id == $this->Id)
{
    if ($performer === null)
    {
        return $discount;
    }

    if ($performer->hasTrait("Academic") || $performer->hasTrait("Hunter"))
    {
        $discount += 1;
        $owner = $this->getOwningCard($theah);
        $explanations[] = sprintf(
            $theah->game->translate("%s: -1 because your performer is an Academic or Hunter."),
            $owner->getInjectCode()
        );
    }
}
```

Null-check `$performer` before `hasTrait`. Gate `$action->Id == $this->Id`. Multi-trait OR lists are common (`Academic or Hunter`, `Zealot, Academic, or Bard`, `Merchant or Scoundrel` on E.2.1). Same hook — do not invent a Maneuver when none is printed.

**WHY not invent a discount-only Maneuver:** `_01159` (Appealing), `_01160` (Bleed Out), `_03071` (Leverage), `_04018` (Seek Each Devil), and `_04039` (Rapsodia) all print a general "-1 cost if …" clause with **only** an Action. Combat-card play of those Risks pays full `WealthCost`. Inventing a Maneuver invents a printed ability.

**When the Risk also prints a real Maneuver** and the card-level "-1 cost while your performer is …" clause should apply at combat-card pay too, see Pattern E.2.1 — do not leave combat-card at full WealthCost if the user/rules expect parity across both pay paths.

**Gates:**
- `$action->Id == $this->Id` (sticky discount must not leak to other hand Actions).
- Leader variant: null-check `getLeaderByPlayerId` (Leader can be destroyed mid-game — `_01160` historically skipped this; new cards should not).
- Performer variant: null-check `$performer` (discount is consulted before/without a performer in some pay-arg paths).

**Contrast Pattern E Maneuver discounts:** duel-relative predicates (engaged adversary, Finesse comparison, wounds comparison, `DUEL_GAMBLED`) belong on Maneuvers because they only make sense at combat-card pay time. Leader- / performer-trait discounts on Action-only cards belong on the Action.

References: `Action_03071`, `Action_01160`, `Action_01159`, `Action_04018` (performer Academic/Hunter), `Action_04039` (performer Zealot/Academic/Bard).

### Pattern E.2.1 — Card-level performer-trait "-1 cost" with **both** City Action and Maneuver

When printed text says **"While your performer is a [Trait], this card has -1 cost"** (or "… or …") as a **card-level** clause and the Risk prints **both** a City Action **and** a Maneuver, the framework has **two separate pay channels** — there is no single Risk-class hook at pay time. Implement **both**:

| Pay path | Hook | "Performer" at discount time |
|---|---|---|
| City Action from hand | `getActionFromHandDiscount` on the **Action** | `$performer` (chosen performer; null-check) |
| Combat card / Maneuver from hand | `getManeuverFromCombatCardDiscount` on the **Maneuver** | `$theah->getDuelRoundActor()` (duel participant; null-check) |

```php
// Action_04030 — City Action pay
if ($action->Id == $this->Id && $performer !== null
    && ($performer->hasTrait('Merchant') || $performer->hasTrait('Scoundrel')))
{
    $discount += 1;
    // ... explanation
}

// Maneuver_04030 — combat-card pay
$owner = $this->getOwningCard($theah);
if ($owner->Id == $combatCard->Id)
{
    $actor = $theah->getDuelRoundActor();
    if ($actor !== null
        && ($actor->hasTrait('Merchant') || $actor->hasTrait('Scoundrel')))
    {
        $discount += 1;
        // ... same explanation wording as Action path
    }
}
```

**WHY two hooks, not Action-only E.2:** `Card::getManeuverFromCombatCardDiscount` iterates Maneuvers at combat-card pay — it never calls `getActionFromHandDiscount`. Conversely, Action pay never consults Maneuver discount hooks. Duplicating the clause on both ability objects is correct; putting it only on the Action leaves Maneuver pay at full `WealthCost` (regression on `_04030`).

**WHY not put performer-trait discount on the Risk class:** same as Pattern E — no Risk-class channel at either pay time.

**WHY `getDuelRoundActor()` for Maneuver path:** at combat-card pay, "your performer" = your duel participant for this round — the same role `$performer` plays during a City Action.

**Single Maneuver discipline:** hang `getManeuverFromCombatCardDiscount` on the one real Maneuver (same as wounds discount on `Maneuver_04007a` only). Do not duplicate on dual a/b Maneuvers.

**Contrast E.2:** Action-only Risks (`_01159`, `_04018`) need only the Action hook. **Contrast Pattern E Maneuver discounts:** duel-relative predicates (wounds, Finesse, `DUEL_GAMBLED`, engaged adversary) belong on Maneuvers only — they do not need an Action mirror.

Reference: `_04030` / `Action_04030` / `Maneuver_04030`.

### Pattern E.1 — Forced on the Risk class (no player choice)

`<b>Forced:</b>` abilities with no chooser belong on the Risk's `handleEvent`, not a separate ability class. There is no Forced base class and no sub-state. Common shape for duel-line Forced effects:

**"After your adversary is destroyed, if this card is in your dueling line • …"**

Gate chain (all required):

1. `EventCharacterDestroyed`
2. `$this->Location == Game::LOCATION_DUELING_LINE`
3. `$game->globals->get(Game::IN_DUEL)` is truthy
4. Destroyed character is the **adversary of this card's controller** — not merely "someone in the duel"

Participant / winner lookup (same shape as `_02052` Gutter Full of Roses, scoped to `$this->ControllerId`):

```php
$challengerId = $theah->getDuelChallengerId();
$defenderId = $theah->getDuelDefenderId();
$destroyedId = $event->characterId;

$participantId = null;
if ($destroyedId == $defenderId
    && $theah->getCharacterById($challengerId)->ControllerId == $this->ControllerId)
{
    $participantId = $challengerId;
}
elseif ($destroyedId == $challengerId
    && $theah->getCharacterById($defenderId)->ControllerId == $this->ControllerId)
{
    $participantId = $defenderId;
}
```

**WHY not use `getDuelRoundActor()` / `getDuelRoundOpponent()` here:** those are round-relative. At destroy time the current round's actor may not be the surviving participant you need. Challenger/defender ids are stable for the whole duel.

**Heal discipline** (when the Forced effect heals): only queue `createCharacterBeingHealedEvent` when the participant is not in discard/locker **and** `$participant->Wounds > 0`. Mirror `Maneuver_01052`. Notify before queueing.

**Draw discipline** (when the Forced effect draws): after the adversary-lookup succeeds, queue `createCardDrawnEvent($this->ControllerId, …)` — no participant wound/in-play gate (the draw is for the card's controller, not a heal on the survivor). Mirror `_03073`. Notify before queueing.

**WHY on the Risk class:** Forced with no player input is a passive — `_01102` (Unfortunate: Forced equip from dueling line at end of round) is the same shape. Do not invent a `Forced_NNNNN` ability file.

References: `_03033` (Glorious — heal on adversary destroyed), `_03073` (Victorious — draw on adversary destroyed), `_01102` (Unfortunate — equip from dueling line), `_02052` (scheme Forced for "any player's adversary destroyed" — same challenger/defender lookup, different scope).

**"After the adversary performs a Technique, if this card is in your dueling line • they suffer a wound"** (Severing Arc `_04050` — may omit the Forced: keyword; still E.1 when no chooser):

Gate chain:
1. `EventResolveTechnique` with `$event->inDuel`
2. `$this->Location == Game::LOCATION_DUELING_LINE`
3. `$game->globals->get(Game::IN_DUEL)` is truthy
4. `$event->actorId` is this controller's duel adversary (challenger/defender lookup → `getDuelOpponentId`)
5. Adversary not in discard/locker

**WHY ResolveTechnique, not Activated / Used:** "after … performs" means the Technique completed. Cancel reactions (`Reaction_01047`) fire on `EventTechniqueActivated` and delete technique events before Resolve — cancelled Techniques must not wound. `EventTechniqueUsed` is only the `setUsed` notify path.

Notify + `createCharacterBeingWoundedEvent` with `eventCheck` before queue. Source/ability = this Risk's id.

**Not E.1:** Forced that only applies **while this Risk is equipped as a RiskAttachment** (e.g. "At the end of High Drama, if this card is equipped • Destroy it") lives on the FakeAttachment — Pattern B.2. The Risk is in `LOCATION_PERMANENTLY_HIDDEN` and will not see `EventHighDramaPhaseEnd` usefully for that clause.

### Pattern E.3 — "Treats their text box as blank" / cannot use abilities

When an equipped RiskAttachment blanks the host character's text box, stamp a **condition** on the Character (Harpoon/Shackles source-of-truth pattern) and gate ability use centrally. Do **not** delete Actions/Reactions from the Character's arrays.

**Constant:** `Game::FATES_SILENCE_CONDITION` (string shown in tooltips). Client mirror in `seventhseacityoffivesails.js` + `notif_fatesSilenceConditionStarted` / `Ended` in `Notifications.js` (push/filter `card.conditions` + `refreshTooltipForCard`).

**Stamp / clear on the FakeAttachment** (mirror `_03066` Shackles):

- `EventAttachmentEquipped` + `$event->attachmentId == $this->Id` → `addCondition` + DB update + notify
- `EventAttachmentUnequipped` → `removeCondition` + notify
- End-of-HD `removeRiskAttachment` already queues unequip first, so the clear path runs

**Central gates (already wired for `_04008` — reuse, do not re-invent):**

1. **`Character::abilitiesAreBlanked()`** → `hasCondition(FATES_SILENCE_CONDITION)`
2. **`Theah::runEvents` / `eventCheck`:** when blanked, call `handleCoreCharacterEvent` / `eventCheckCore` only — skip polymorphic `handleEvent`/`eventCheck` so **subclass Forced/passives** (Maryam cancel, Yevgeni thrust, etc.) do not fire, while wounds/Harpoon/Shackles/Lodestone condition gates still run
3. **`Card::handleEvent` / `eventCheck`:** early-return ability-object loops when the card is a blanked Character (defense in depth)
4. **Availability:** `CardAction` / `Maneuver` / `Technique` `isAvailableToPlayer` returns false when **`$owner instanceof Character && abilitiesAreBlanked()`**. Attachment-owned abilities (`OwnerId` = attachment) keep working — blanking is the **character's** text box, not equipped attachments'

**WHY condition + Theah skip (not array-stripping):** Turais Dall's cited counterplay is a **Reaction** (ability object) — skipping Character ability loops covers that. Character-class Forced/passives run *after* `parent::handleEvent` in subclasses, so only Theah's core-only dispatch blanks those without touching every Character file.

**WHY not blank attachment abilities:** reminder text "their abilities" = the equipped character's printed abilities. Attachments retain their own text boxes.

Reference: `_04008_Silence`, `Character::abilitiesAreBlanked` / `handleCoreCharacterEvent` / `eventCheckCore`, `Theah` blanked branches, `CardAction` / `Maneuver` / `Technique` availability gates.

### Pattern E.4 — "When paying for this card, [Trait] cards gain Wealth"

Automatic (not a Reaction). When the player pays **this Risk's** wealth cost (City Action from hand **or** combat-card maneuver), other cards in hand with the printed traits temporarily have Wealth (count as two; locker after paying). Italic reminder is the standard Wealth reminder (`_01170` / `Reaction_02021`) — it describes the granted trait, not Panacea itself.

**Grant** on `EventEnteringPayState` when `$event->cardId == $this->Id` and pay type is `PAY_STATE_IN_HAND_ACTION` or `PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD`. Scan the paying player's hand:

- Exclude the card being paid for (cannot discard itself as payment).
- Skip cards that already `hasTrait("Wealth")` — Opulence lockers itself; double-grant would double-locker.
- `addTrait($game, 'Wealth')` **not quietly** — JS pay UI uses `item.traits.includes('Wealth')` and only updates via `notif_traitAdded`.
- **Mutate the cached card** (`$theah->getCardById($id)`), not the object from `getCardObjectsAtLocation`. That helper unserializes a fresh DB copy; `runEvents` only persists `$theah->cards`, so a grant on the copy notifies then evaporates and pay validation (`getCardObjectFromDb`) still sees no Wealth. Anghos uses `getAttachmentById` for the same reason. JS `payForCard` should read `cardProperties[id].traits` (what `notif_traitAdded` updates), not HandStock `item.traits` (may be a clone).

Sticky `$GrantedWealthCardIds` on the **Risk** (not the Action) so locker/revoke still work after the Risk leaves hand (purgatory / discard / dueling line). `buildCity` loads those locations.

**Locker** on `EventCardDiscardedFromHand` + `AsPayment` for granted ids — same event as `_01170`. `runEventHubAfterCards = true` so the card is still in hand when cards handle, then EventHub moves it to discard, then the queued locker event fires.

**Revoke leftover Wealth** (cards granted but not discarded as payment):

- `EventActionTriggered` `sourceId == this` (after City Action pay)
- `EventManeuverActivated` `ownerId == this` (after combat-card pay — `ownerId` is the combat card id)
- `EventPlayerTurnEnd` / `EventDuelEnd` (Back from pay with no follow-up payment)
- A later `EventEnteringPayState` that is **not** `PAY_STATE_IN_HAND_REACTION` — Back then pay for something else. **Do not** revoke on nested reaction pay (Anghos `Reaction_02021` fires during Panacea's own pre-pay events).

**WHY not a Reaction:** printed text is automatic "when paying", not "you may". **WHY not FrameworkActionsTrait special-case** (Bravos Thugs): JS would still count those cards as 1 unless traits update. **WHY OR not AND** on "Alquimia and Discovery cards": cards of those types, not cards that have both traits.

Reference: `_04038`, `_01170` locker, `Reaction_02021` addTrait/locker/turn-end cleanup (optional chooser; Panacea is automatic + all matching cards).

