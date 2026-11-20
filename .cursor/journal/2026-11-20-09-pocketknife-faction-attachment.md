# _05Pocketknife El Estoque Oculto

Finishing CAD FactionAttachment via create-faction-attachment skill. User: no clienttranslate / translate / _().

## Orientation
Prior today: Rum scheme. Pocketknife Text:
1. Covert Technique: Unequip → top of deck; adversary discards.
2. Gambling Maneuver: Wound adversary; put a dueling-line card into hand.

Art: WealthCost II (=2), Riposte 0 / Parry 3 / Thrust +2. Traits Weapon/Sword/Melee/Flourish/Unique. CardNumber stub 24 (collides with Rum/etc.).

## Text → shapes
1. Covert Technique → Pattern E + Covert uncontrolled gate (Corncob). Unequip→removeFromPlay→faction deck ON TOP (02055 sink but onTop=true). Adversary discard = Maya/04017 hand picker + GameState.
2. Gambling Maneuver → Pattern E Gambling gate (IN_DUEL + DUEL_GAMBLED). Wound like Maneuver_05Henry. Dueling-line→hand picker = button-per-card + GameState (02043b).

No equip restriction / engage / Pattern A–D.

## WHY decisions
- Covert Technique duel-only (IN_DUEL + actor==owner): adversary discard needs duel context; skip challenge path (unlike Corncob/PlusOneParry).
- Unequip self must be attached; gate isAttached via owning character + Covert on host location.
- sourceId = attachment for technique transition (04017).
- Queue unequip (MEDIUM) + createTechniqueTransitionEvent (HIGHEST): discard UI runs first while still equipped (source hydration), then unequip drains. Printed order is slightly reversed in animation — deliberate for FrameworkActionsTrait.
- Maneuver uses createTransitionEvent (default MEDIUM) so FIFO keeps Wound before picker (03036).
- Empty adversary hand / empty dueling line → notify + skip (checklist 9).
- Maneuver does NOT require equipped — combat cards play from hand → LOCATION_DUELING_LINE; Technique and Maneuver are mutually exclusive use cases in practice.
- State ids: 52105242 / 52505242 (CardNumber 24 collision cluster — Quack/Rum/…).
- WealthCost 2 from art (stub had 0).
- createCardAddedToHandEvent for line→hand (EventHub moveCard + ControllerId).

## Shipped
- `_05Pocketknife` + `Technique_05Pocketknife` + `Maneuver_05Pocketknife`
- States `DUEL_CHOOSE_TECHNIQUE_05POCKETKNIFE` / `DUEL_RESOLVE_MANEUVER_05POCKETKNIFE` + cad GameStates
- states.inc.php EVENTS keys `"05Pocketknife"`
- CAD JS enter/leave/buttons + EventHandlers discard enable
- Skill: Covert Technique / unequip-top / dueling-line→hand rows + Pocketknife reference
- `Card_05Pocketknife_Test` — 7 pass

## Unfinished / watch
- None known. If Rules want Covert Technique at challenge time, add Accept-gated discard like 04017.
