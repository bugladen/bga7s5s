# Create-character skill update after Yevgeni _03052

Updated `.claude/skills/create-character/SKILL.md` with the reusable lessons from implementing Yevgeni.

## WHY these patterns matter

- **Sink is not discard.** The City Deck effect must use `createCardAddedToCityDeckEvent(..., false)` to place the card on the bottom. `Action_02014` is visually similar but discards, so copying it mechanically changes the card's rules.
- **Look is not reveal.** Looked-at City Deck cards and the adversary's hand must travel through `argsForStatePrivate`. `Technique_03043` is the wrong model because its printed Reveal publicly identifies cards and needs multiplayer acknowledgement.
- **A button Reaction may launch richer states.** The Dusk choice starts as a Continuous Reaction, then uses dedicated sink/reorder states because chooseList interaction cannot live entirely in reaction buttons.
- **Snapshot once.** Persist the top-card set when Look is chosen, then validate sink/reorder against that snapshot. Re-querying between steps risks acting on a changed deck.
- **Skip forced UI.** After sinking, zero remaining cards means done and one means forced top placement; only two or more need sorting.
- **Hand-look owner remains active.** Unlike adversary-discard techniques, Yevgeni's owner sees the private hand and acknowledges Done.
- The Sorcerer trait alone does not make either ability an `ISorcererAbility`; only the printed ability keyword does.

Added canonical-reference, phrase-table, detailed Pattern D/E, reference-table, and completion-checklist entries.
