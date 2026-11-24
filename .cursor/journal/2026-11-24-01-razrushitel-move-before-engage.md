# Razrushitel (01138) move-before-engage order

## Bug
Card text: move performer to target location, THEN "you may engage" (home) else wound.
BGA offered engage chooser first; move + home/wound all ran after the choice in one batch.
Symptom: Tomoe→Rosa location + Rosa Home same resolution step — Rosa Reaction_02033
("After a character moves to Rosa's location") never meaningfully fired / felt like
Rosa went Home before the arrival.

## WHY the current shape existed
2026-10-27 Come Hither / Cross work: act only queues CharacterTargeted; effects emit on
survive so cancel hooks see the target first. Engaged path already did move+wound on
survive; en garde path only transitioned to 01138_2 and deferred the move into the
chooser act — wrong relative to printed order.

## Fix plan
On CharacterTargeted survive (en garde): queue performer move, THEN transition 01138_2.
Chooser act: engage+Home OR wound only — no second performer move.
Mirror Action_01011 (move then follow-up state). Keep Come Hither gate intact
(effects still only after targeted survives).

## Done
Action_01138 + State comment + Action_01138_Test updated. 15/15 pass.

Engaged path unchanged (move+wound on survive). En garde: move then 01138_2;
chooser only engage+Home or wound.
