# _05Shakedown Blackmail

Finishing CAD Risk via create-risk. User: no clienttranslate / translate / _().

## Orientation
Prior today: GaryFlipped challenge technique, duel starting-threat lethal icon. Recent CAD risks: Stash (D.9+C.11), RedTape (B.15).

## Text → shapes
1. **E.2** If performer is Scoundrel or Villain → -1 cost. Action-only → `getActionFromHandDiscount` on Action (no invented Maneuver).
2. **Covert B.2** Covert Action: Equip this card to target opposing character. RiskAttachment shell. Printed "target" → Cesca.
3. Equipped: -1 Influence (`InfluenceModifier = -1`) + gains Action: Engage this character • Destroy this card → AttachmentAction on shell.

## WHY
- Covert ≠ City Action: same discipline as Teens/Vulture — `RiskAction` + uncontrolled City gate on performer.
- Shell controller = target controller (01102 Unfortunate): equipped character *gains* the destroy Action. Owner stays Risk controller so `removeRiskAttachment` returns Blackmail to that discard.
- Destroy Action resolves on `EventActionTriggered` (no chooser) — Engage host then `removeRiskAttachment`. Contrast 01102 which needs a 2-card discard state.
- No end-of-HD Forced printed → do not copy Burden/Silence HD destroy.
- Renamed stub `05Shakedown.php` → `_05Shakedown.php` (wiring.md: getCardClassName requires underscore file).
- Shell class id shortened to `05Shakedown_Att` — `card.card_type` is varchar(16); `05Shakedown_Blackmail` (21) blew INSERT. Same discipline as `05TimebombB`. Printed Name stays "Blackmail".

## Shipped
- `_05Shakedown` + `Action_05Shakedown` + `_05Shakedown_Att` + `Action_05Shakedownb`
- State `405028` / `"05Shakedown"` + cad JS character-chooser trio
- Skill references row
- Tests: `Action_05Shakedown_Test` (5) + `Action_05Shakedownb_Test` (5) — all pass

## CharacterTargeted (follow-up)
User: both `_05Shakedown` and `_04008` need `EventCharacterTargeted` so cancel reactions can fire.

WHY gate equip in `handleEvent` on `!canceled` (not queue beside the hook): UL/Terraria/Maryam cancel during CharacterTargeted; equipping first would stick the shell (04040/Teens trap). Sorcerer start/played for Silence also move into the survive path.

Tests updated for both Actions — all pass. Skill B.2 / checklist 51 / references updated.

## Unfinished / watch
- Did not add Cesca Reaction_01008 allow-list (CAD prototypes skip until asked).
- Stub Name remains "Blackmail"; Image `05Shakedown.v5.jpg`.
- Shell renamed `_05Shakedown_Blackmail` → `_05Shakedown_Att` for varchar(16).
