# _05Timebomb (Klim Grabiezca) — finish Character

## Prior context
Seadog done earlier today. Stub `_05Timebomb` open with Text that was literally Nevermore's scheme paste (Add Renown / Villain Reaction muster Klim / Leader City Action draw Artifacts). Art CAD-US7A / PTv0.5 is different.

## WHY fix Text from art, not implement Nevermore paste
User said "abilities described in its Text property" + Create Character skill + test card. Implementing scheme text on a Character named Klim who is himself the muster target is nonsense. Art is:
1. Cannot be in an Approach deck
2. Forced: end of HD if at Home • Wound him and your Leader twice
3. Forced: When would be destroyed • Flip him

Flipped side is Porcelain Heart (FactionAttachment Artifact) — owner takes control, equips to Leader; cannot move/destroy/locker; Forced −1 Parry on adversary combat announce.

## Classification → implementation
1. Approach ban → `canEnterApproachDeck()` false (Orel; not Silence-liftable)
2. HD Home Forced → `EventHighDramaPhaseEnd` + Location==HOME; wound Klim 2 then Leader 2; abilityId=card Id
3. Destroy→flip → cancel `EventCharacterDestroyed` in handleEvent (hub-after-cards); Gary same-Id swap but Character→Attachment; ControllerId=OwnerId; equip Leader via getRequiredAttachTargetId + createAttachmentEquippedEvent; client `cardRemovedFromPlay` not `characterDestroyed` (locker fly)

WHY Theah blanking skip is enough for Silence: blanked Characters get `handleCoreCharacterEvent` only → wound still destroys via hub, no flip. No extra abilitiesAreBlanked gate needed inside destroy handler.

WHY FakeGame instantiateCard now routes 01–05 prefixes: was hardcoded `_7s5s`; CAD flip `05TimebombFlipped` would throw. Mirror getCardClassName.

## B-side partial
Implemented on Flipped: canBeMoved false, Leader-only attach (01202), equip eventCheck. NOT done: Forced −1 Parry on adversary combat announce; full cannot-destroy/locker (Syrneth Hand dying-host exception TBD — blocking unequip on Leader death softlocks).

## Tests
`tests/regression/cad/Card_05Timebomb_Test.php` — 5/5 PASS (stats/approach, HD home wounds, HD city no-op, destroy flip+equip, opponent-control returns to owner).

## Unfinished
- Finish `_05TimebombFlipped` Forced Parry + permanence (destroy/locker)
- Nevermore scheme still unimplemented (same pasted text lives there for real)
