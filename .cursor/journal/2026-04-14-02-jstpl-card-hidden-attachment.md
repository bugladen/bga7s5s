# jstpl_card_hidden_attachment vs Templates.js

User asked if `jstpl_card_hidden_attachment` was inadvertently removed from `Templates.js`.

**Finding:** Git pickaxe on `modules/js/Templates.js` for that string returns **no commits** — it was never present in that file in repo history.

**What did happen:** Commit `acca7990` ("Fate's Kiss", 2026-02-02) added `dojo.place(this.format_block('jstpl_card_hidden_attachment', ...))` in `Utilities.js` but did not add `window.jstpl_card_hidden_attachment` anywhere (Templates.js, tpl, or otherwise). Only reference in the whole tree is that one call site.

**Conclusion:** Not a removal — an **omission** when the Utilities path was added. Likely needs a new template alongside `jstpl_card_hidden` / `jstpl_card_attachment` (probably hidden back + `--attachment-index` like visible attachments).

No notes under `C:\repos\magnus\journal\7s5s` (path empty/missing).

---

## Implemented (same session)

Added `window.jstpl_card_hidden_attachment` in `Templates.js` right after `jstpl_card_hidden`.

**WHY this shape:** `createHiddenAttachmentCard` passes `id`, `attachmentIndex`, `image`, `player_color` only — no resolve/stats/faction. Outer wrapper matches `jstpl_card_attachment` (`--attachment-index` on `#id`) so existing attachment stacking / splay CSS applies; inner matches `jstpl_card_hidden` (`_7sfs-card-back`, player color chip, `\${id}_image`) so tooltips and any `_image`-scoped rules stay consistent with other hidden cards.
