# Does Sanjay (03037) emit CharacterTargeted?

User asked. Was **no** — now fixed.

## Before
`actFromActionWithId` set challenge globals + queued `03037_2` with no CharacterTargeted. Framework emit lives only in `actHighDramaChallengeActionTargetChosen`; hub-exit challenges (Sanjay, base Valeri 01123, Aja 03002, …) bypassed it. Vittoria/UL/"when targeted" missed until ChallengeIssued.

## Fix (Dabney Action_05DabneyUS01 shape)
- **act**: set CHOSEN_TARGET, queue batched `EventCharacterTargeted` only, nextState.
- **handleEvent** on surviving CharacterTargeted: sync performer/target/Influence/SANJAY_CHALLENGE_TYPE, `deleteQueuedTransitionsNamed("03037_2")`, queue hub-exit.
- Canceled → no technique path.

WHY gate on survival (not queue CT + transition together): UL cancel must not leave a dangling `03037_2`; redirect must re-sync CHOSEN_TARGET from event.targetId before technique.

WHY not also fix Valeri/Aja/etc.: user asked only for Sanjay. Same gap remains on other hub-exit challenges — base Valeri called out in 2026-10-15 Dabney journal.

## Tests
`Action_03037_Test`: act asserts CharacterTargeted only; surviving stamps type + `03037_2`; canceled queues nothing.
