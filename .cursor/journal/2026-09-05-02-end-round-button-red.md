# End Round button red in duelChooseAction

Eddie wanted End Round colored red like Pass in highDramaPlayerTurn.

## Change
`OnUpdateActionButtons.js` `duelChooseAction`: switched End Round from `this.addActionButton(...)` to `this.statusBar.addActionButton(..., { id: 'btnDone', color: 'alert' })`.

WHY: Pass uses `color: 'alert'` on `statusBar.addActionButton` — that's the BGA red/destructive style. Old `addActionButton` without a color param stays blue/default. Same API as Pass keeps visual language consistent for "I'm done / no more actions" controls.

Did not touch End Duel — only End Round was requested.
