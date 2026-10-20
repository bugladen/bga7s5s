# setupTable_05Gary chooseList leftover cards

User: attachments shown during Rena setup can include cards still sitting in `chooseList` from a previous state. Clear the stock before painting the attachments.

## WHY clear on enter, not only on leave

`OnLeavingState.cad.js` already calls `chooseList.removeAll()` when leaving `setupTable_05Gary` and `_2`. That does not help the *incoming* state. `chooseList` is one shared stock used by many states. If the previous state's leave handler never ran (reconnect, or a state that filled the list and did not wipe it), enter just `addCardToDeck`s on top of whatever is already there.

Fix is in `OnEnteringState.cad.js` `setupTable_05Gary`: `removeAll()` immediately before the attachment `forEach`. Only the active player path; spectators never fill this list. Did not touch `_2` — that state shows the single chosen card after Gary's own leave already emptied the stock, and the user asked specifically about the attachment list.

JS only. No PHP regression run.
