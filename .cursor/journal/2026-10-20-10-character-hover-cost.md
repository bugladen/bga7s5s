# Character text hover: Cost row

Eddie: cards with a wealth cost should show a Cost line in the text hover.

Attachment and Risk builders already had `row(_('Cost'), card.wealthCost ?? '')`. Character was the only other type with a wealth cost (FactionCharacter / CityCharacter implement IWealthCost; Leader does not; Scheme/Event never have one). Added Cost after Type in `createTextTooltipForCharacter`.

WHY gated on `wealthCost != null && !== ''` rather than always showing like Attachment/Risk: `wealthCost` is only serialized via `addWealthCostProperties` for IWealthCost cards, so Leaders would otherwise get a blank "Cost" row. Cost 0 still displays (a real value). Left Attachment/Risk alone — they always implement IWealthCost.

Didn't touch PHP, so no regression run needed. Not verified in browser (BGA deploy is SFTP only).
