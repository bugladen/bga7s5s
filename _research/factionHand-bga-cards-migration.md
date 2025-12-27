# Converting factionHand to bga-cards HandStock with Floating Hand

## Overview

This document provides a complete implementation guide for converting `this.factionHand` from `ebg.stock()` to the bga-cards `HandStock` component **with floating hand support**.

**Reference Documentation:**
- Demo: https://x.boardgamearena.net/data/game-libs/bga-cards/1.0.10/demo/hand-stocks.html
- API Docs: https://x.boardgamearena.net/data/game-libs/bga-cards/1.0.10/docs/index.html

---

## Implementation Checklist

- [ ] Step 1: Update `seventhseacityoffivesails.js` - Import bga-cards library
- [ ] Step 2: Update `seventhseacityoffivesails_seventhseacityoffivesails.tpl` - Add floating hand HTML structure
- [ ] Step 3: Update `seventhseacityoffivesails.css` - Add floating hand styles
- [ ] Step 4: Update `modules/js/Setup.js` - Create CardManager and HandStock
- [ ] Step 5: Update `modules/js/Utilities.js` - Modify addCardToDeck helper
- [ ] Step 6: Update all files using factionHand API methods

---

## STEP 1: Import bga-cards Library

**File: `seventhseacityoffivesails.js`**

```javascript
// BEFORE (lines 16-36)
define([
   "dojo",
   "dojo/_base/declare", 
   "dojo/dom-class",
   "ebg/core/gamegui",
   "ebg/counter",
   "ebg/stock",
   getLibUrl('bga-animations', '1.x'),
   g_gamethemeurl + 'modules/js/OnEnteringState.js',
   // ... rest of modules
],
function (dojo, declare, domClass, gamegui, counter, stock, BgaAnimations)

// AFTER
define([
   "dojo",
   "dojo/_base/declare", 
   "dojo/dom-class",
   "ebg/core/gamegui",
   "ebg/counter",
   "ebg/stock",
   getLibUrl('bga-animations', '1.x'),
   getLibUrl('bga-cards', '1.x'),  // ADD THIS LINE
   g_gamethemeurl + 'modules/js/OnEnteringState.js',
   // ... rest of modules
],
function (dojo, declare, domClass, gamegui, counter, stock, BgaAnimations, BgaCards) // ADD BgaCards
{
    // Store BgaCards globally for use in mixed-in classes
    window.BgaCards = BgaCards;  // ADD THIS LINE (similar to BgaAnimations pattern)
```

---

## STEP 2: Update Template for Floating Hand

**File: `seventhseacityoffivesails_seventhseacityoffivesails.tpl`**

### Current Structure (lines 295-299)
```html
<div id="factionHand-container" class="whiteblock _7sfs-hand">
    <div class="_7sfs-hand-label"><span><b>Your Faction Hand</b></span> <span id="faction_hand_info"></span></div>
    <div id="factionHand">
    </div>
</div>
```

### New Structure with Floating Hand
```html
<!-- Placeholder shown when hand is floating (appears in normal document flow) -->
<div id="factionHand-placeholder" class="whiteblock _7sfs-hand _7sfs-hand-placeholder">
    <div class="_7sfs-hand-label">
        <span><b>Your Faction Hand</b></span>
        <span id="faction_hand_info"></span>
        <span class="_7sfs-floating-indicator">(floating above)</span>
    </div>
</div>

<!-- The actual floating hand container -->
<div id="factionHand-wrapper" class="_7sfs-floating-hand-wrapper">
    <div id="factionHand-container" class="_7sfs-floating-hand-container">
        <div class="_7sfs-floating-hand-header">
            <span class="_7sfs-hand-label"><b>Your Faction Hand</b></span>
            <button id="factionHand-collapse-btn" class="_7sfs-collapse-btn">▼</button>
        </div>
        <div id="factionHand" class="_7sfs-floating-hand-cards">
        </div>
    </div>
</div>
```

---

## STEP 3: Add CSS for Floating Hand

**File: `seventhseacityoffivesails.css`**

Add these styles (can be placed after the existing `._7sfs-hand` styles around line 1015):

```css
/* ============================================
   FLOATING HAND STYLES
   ============================================ */

/* Placeholder - shown when hand is floating */
._7sfs-hand-placeholder {
    min-height: 40px;
    background: rgba(0, 0, 0, 0.1);
    border: 2px dashed rgba(0, 0, 0, 0.3);
}

._7sfs-hand-placeholder._7sfs-hand-not-floating {
    display: none;
}

._7sfs-floating-indicator {
    font-style: italic;
    color: #666;
    font-size: 12px;
    margin-left: 10px;
}

/* Floating hand wrapper - fixed at bottom of viewport */
._7sfs-floating-hand-wrapper {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    pointer-events: none;
    display: flex;
    justify-content: center;
    transition: transform 0.3s ease;
}

._7sfs-floating-hand-wrapper._7sfs-collapsed {
    transform: translateY(calc(100% - 40px));
}

._7sfs-floating-hand-wrapper._7sfs-hand-not-floating {
    position: relative;
    z-index: auto;
}

/* Floating hand container */
._7sfs-floating-hand-container {
    pointer-events: auto;
    background: linear-gradient(180deg, rgba(40, 30, 20, 0.95) 0%, rgba(30, 25, 15, 0.98) 100%);
    border: 3px solid #8B7355;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 8px 15px 15px 15px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
    max-width: 90vw;
    min-width: 300px;
}

._7sfs-floating-hand-wrapper._7sfs-hand-not-floating ._7sfs-floating-hand-container {
    background: transparent;
    border: none;
    border-radius: 0;
    box-shadow: none;
    padding: 0;
    max-width: none;
}

/* Floating hand header */
._7sfs-floating-hand-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    color: #D4C4A8;
}

._7sfs-floating-hand-wrapper._7sfs-hand-not-floating ._7sfs-floating-hand-header {
    display: none;
}

._7sfs-collapse-btn {
    background: rgba(139, 115, 85, 0.5);
    border: 1px solid #8B7355;
    border-radius: 4px;
    color: #D4C4A8;
    cursor: pointer;
    padding: 4px 10px;
    font-size: 12px;
    transition: background 0.2s;
}

._7sfs-collapse-btn:hover {
    background: rgba(139, 115, 85, 0.8);
}

._7sfs-collapsed ._7sfs-collapse-btn {
    transform: rotate(180deg);
}

/* Cards container */
._7sfs-floating-hand-cards {
    display: flex;
    justify-content: center;
    flex-wrap: nowrap;
    gap: 5px;
    min-height: 100px;
    overflow-x: auto;
    padding: 5px 0;
}

/* Card styling in floating hand */
._7sfs-floating-hand-cards .bga-cards_card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
}

._7sfs-floating-hand-cards .bga-cards_card:hover {
    transform: translateY(-15px) scale(1.05);
    z-index: 10;
}

._7sfs-floating-hand-cards .bga-cards_selectable-card {
    box-shadow: 0 0 8px 2px rgba(255, 255, 0, 0.6);
}

._7sfs-floating-hand-cards .bga-cards_selected-card {
    transform: translateY(-20px);
    box-shadow: 0 0 12px 3px rgba(0, 255, 0, 0.8);
}

/* Hide floating hand for spectators */
.spectatorMode ._7sfs-floating-hand-wrapper,
.spectatorMode ._7sfs-hand-placeholder {
    display: none;
}

/* Mobile adjustments */
@media (max-width: 768px) {
    ._7sfs-floating-hand-container {
        max-width: 100vw;
        border-radius: 0;
        padding: 5px 10px 10px 10px;
    }
    
    ._7sfs-floating-hand-cards {
        min-height: 80px;
    }
}
```

---

## STEP 4: Update Setup.js - CardManager and HandStock

**File: `modules/js/Setup.js`**

### Replace factionHand initialization (lines 262-285)

```javascript
// BEFORE
this.factionHand = new ebg.stock();
this.factionHand.create( this, $('factionHand'), this.wholeCardWidth, this.wholeCardHeight ); 
this.factionHand.image_items_per_row = 0;
this.factionHand.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
this.factionHand.onItemCreate = dojo.hitch( this, 'setupNewStockCard' ); 
this.factionHand.setSelectionAppearance( 'class' )
dojo.connect( this.factionHand, 'onChangeSelection', this, 'onFactionCardClicked' );
// For each card in the approach deck, create a stock item
gamedatas.factionHand.forEach((card) => {
    this.addCardToDeck(this.factionHand, card);

    //Check for any special conditions where a token has to be displayed
    if (card.conditions.includes(this.CATS_EMBARGO_TARGET)) {
        const div = this.factionHand.getItemDivId(card.id);
        const id = `${card.id}_cats_embargo_target`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: '_7sfs-cats-embargo-target-chip',
        }),  div, 'last');
        this.addTooltipHtml( id, `<div class='_7sfs-basic-tooltip'>${_("Target for Cat's Embargo")}</div>` );
    }
});
this.factionHand.setSelectionMode(0);

// AFTER
// Create CardManager for faction hand cards
this.factionHandManager = new CardManager(this, {
    getId: (card) => `factionhand-card-${card.id}`,
    setupDiv: (card, div) => {
        // Store reference
        if (!this.cardProperties[card.id]) {
            this.cardProperties[card.id] = card;
        }
        this.cardProperties[card.id].divId = div.id;
        
        // Apply card styling
        div.classList.add('_7sfs-card', '_7sfs-faction-hand-card');
        div.style.width = `${this.wholeCardWidth}px`;
        div.style.height = `${this.wholeCardHeight}px`;
        div.style.backgroundImage = `url('${g_gamethemeurl}${card.image}')`;
        div.style.backgroundSize = 'cover';
        div.style.borderRadius = '4px';
        
        // Add tooltip
        this.addTooltipHtml(div.id, `<img src="${g_gamethemeurl + card.image}" />`, this.STOCK_CARD_TOOLTIP_DELAY);
    },
    cardWidth: this.wholeCardWidth,
    cardHeight: this.wholeCardHeight,
});

// Create floating HandStock
this.factionHand = new HandStock(this.factionHandManager, $('factionHand'), {
    cardOverlap: '40px',
    sort: (a, b) => {
        // Schemes and Attachments first, then by id
        const weightA = (a.type === "Scheme" || a.type === 'Attachment') ? 1 : 2;
        const weightB = (b.type === "Scheme" || b.type === 'Attachment') ? 1 : 2;
        if (weightA !== weightB) return weightA - weightB;
        return a.id - b.id;
    },
});

// Selection change handler
this.factionHand.onSelectionChange = (selection, lastChange) => {
    this.onFactionCardClicked(lastChange);
};

// Add initial cards
gamedatas.factionHand.forEach((card) => {
    this.cardProperties[card.id] = card;
    this.factionHand.addCard(card);
    
    // Check for special conditions
    if (card.conditions.includes(this.CATS_EMBARGO_TARGET)) {
        const cardElement = this.factionHand.getCardElement(card);
        if (cardElement) {
            const id = `${card.id}_cats_embargo_target`;
            dojo.place(this.format_block('jstpl_generic_chip', {
                id: id,
                class: '_7sfs-cats-embargo-target-chip',
            }), cardElement, 'last');
            this.addTooltipHtml(id, `<div class='_7sfs-basic-tooltip'>${_("Target for Cat's Embargo")}</div>`);
        }
    }
});

this.factionHand.setSelectionMode('none');

// Setup floating hand behavior
this.setupFloatingHand();
```

### Add floating hand setup method (add to Setup.js or Utilities.js)

```javascript
setupFloatingHand: function() {
    const wrapper = $('factionHand-wrapper');
    const placeholder = $('factionHand-placeholder');
    const collapseBtn = $('factionHand-collapse-btn');
    
    if (!wrapper || !placeholder) return;
    
    // Collapse/expand toggle
    if (collapseBtn) {
        dojo.connect(collapseBtn, 'onclick', this, () => {
            dojo.toggleClass(wrapper, '_7sfs-collapsed');
        });
    }
    
    // Scroll handler for floating behavior
    const checkFloating = () => {
        const placeholderRect = placeholder.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        // Float when placeholder is above the viewport bottom
        const shouldFloat = placeholderRect.bottom < viewportHeight - 50;
        
        if (shouldFloat) {
            dojo.removeClass(wrapper, '_7sfs-hand-not-floating');
            dojo.removeClass(placeholder, '_7sfs-hand-not-floating');
        } else {
            dojo.addClass(wrapper, '_7sfs-hand-not-floating');
            dojo.addClass(placeholder, '_7sfs-hand-not-floating');
        }
    };
    
    // Check on scroll and resize
    window.addEventListener('scroll', checkFloating, { passive: true });
    window.addEventListener('resize', checkFloating, { passive: true });
    
    // Initial check
    checkFloating();
},
```

---

## STEP 5: Update Utilities.js - addCardToDeck Helper

**File: `modules/js/Utilities.js`**

```javascript
// BEFORE (lines 42-55)
addCardToDeck: function( deck, card )
{
    if (!this.cardProperties[card.id])
        this.cardProperties[card.id] = card;

    //Different weight depending on the type. Scheme and attachment cards go first
    const weight = card.type === "Scheme" || card.type === 'Attachment' ? 1 : 2;

    //Each card is a different image, so would be considered a different type for the stock object
    deck.addItemType(card.id, weight, g_gamethemeurl + card.image, 0);

    // Type and id are the same for the approach deck stock object
    deck.addToStockWithId(card.id, card.id);
},

// AFTER - Compatible with both ebg.stock and bga-cards
addCardToDeck: function( deck, card )
{
    if (!this.cardProperties[card.id])
        this.cardProperties[card.id] = card;

    // Check if using bga-cards (HandStock/CardStock) or legacy ebg.stock
    if (typeof deck.addCard === 'function') {
        // bga-cards HandStock/CardStock
        deck.addCard(card);
    } else {
        // Legacy ebg.stock
        const weight = card.type === "Scheme" || card.type === 'Attachment' ? 1 : 2;
        deck.addItemType(card.id, weight, g_gamethemeurl + card.image, 0);
        deck.addToStockWithId(card.id, card.id);
    }
},

// Add helper to remove card (for compatibility)
removeCardFromDeck: function( deck, cardOrId )
{
    const cardId = typeof cardOrId === 'object' ? cardOrId.id : cardOrId;
    
    if (typeof deck.removeCard === 'function') {
        // bga-cards
        const card = this.cardProperties[cardId];
        if (card) deck.removeCard(card);
    } else {
        // Legacy ebg.stock
        deck.removeFromStockById(cardId);
    }
},
```

---

## STEP 6: API Method Replacements

### Pattern 1: Selection Mode
```javascript
// BEFORE
this.factionHand.setSelectionMode(0);  // none
this.factionHand.setSelectionMode(1);  // single
this.factionHand.setSelectionMode(2);  // multiple

// AFTER
this.factionHand.setSelectionMode('none');
this.factionHand.setSelectionMode('single');
this.factionHand.setSelectionMode('multiple');
```

**Files affected:** OnEnteringState.js, OnEnteringState.7s5s.js, OnLeavingState.7s5s.js, OnUpdateActionButtons.js

### Pattern 2: Get Selected Items
```javascript
// BEFORE
var items = this.factionHand.getSelectedItems();
items.forEach((item) => { /* use item.id */ });

// AFTER
var items = this.factionHand.getSelection();
items.forEach((card) => { /* use card.id */ });
```

**Files affected:** PlayerActions.js, EventHandlers.js

### Pattern 3: Remove Card
```javascript
// BEFORE
this.factionHand.removeFromStockById(cardId);
// or
items.forEach((item) => this.factionHand.removeFromStockById(item));

// AFTER
const card = this.cardProperties[cardId];
this.factionHand.removeCard(card);
// or
items.forEach((card) => this.factionHand.removeCard(card));
```

**Files affected:** PlayerActions.js, Notifications.js

### Pattern 4: Get Card Element/Div
```javascript
// BEFORE
const div = this.factionHand.getItemDivId(cardId);
// Returns: string like "factionHand_item_123"

// AFTER
const card = this.cardProperties[cardId];
const element = this.factionHand.getCardElement(card);
const divId = element ? element.id : null;
// Returns: DOM element, use .id to get string
```

**Files affected:** OnEnteringState.7s5s.js, OnLeavingState.7s5s.js, Notifications.js

### Pattern 5: Count Cards
```javascript
// BEFORE
const count = this.factionHand.count();

// AFTER
const count = this.factionHand.getCards().length;
```

**Files affected:** Notifications.js, OnUpdateActionButtons.js

### Pattern 6: Get All Items
```javascript
// BEFORE
this.factionHand.getAllItems().forEach((card, index) => {
    let div = this.factionHand.getItemDivId(card.id);
});

// AFTER
this.factionHand.getCards().forEach((card, index) => {
    let element = this.factionHand.getCardElement(card);
});
```

**Files affected:** OnLeavingState.7s5s.js

### Pattern 7: Select/Unselect Card
```javascript
// BEFORE
this.factionHand.selectItem(item_id);
this.factionHand.unselectItem(item_id);

// AFTER
const card = this.cardProperties[item_id];
this.factionHand.selectCard(card);
this.factionHand.unselectCard(card);
```

**Files affected:** EventHandlers.js

### Pattern 8: Check if Card Exists in Stock
```javascript
// BEFORE
if (this.factionHand.getItemById(attachment.id) != undefined)

// AFTER
const cardExists = this.factionHand.getCards().some(c => c.id === attachment.id);
if (cardExists)
```

**Files affected:** Notifications.js

---

## Files Requiring Changes - Detailed List

| File | Changes | Patterns Used |
|------|---------|---------------|
| `seventhseacityoffivesails.js` | 3 | Import, global storage |
| `seventhseacityoffivesails.tpl` | 1 | Template restructure |
| `seventhseacityoffivesails.css` | 1 | New styles (~100 lines) |
| `Setup.js` | 8 | Initialization, addCard |
| `Utilities.js` | 2 | addCardToDeck, new helper |
| `Notifications.js` | 12 | Patterns 3, 4, 5, 8 |
| `PlayerActions.js` | 20 | Patterns 2, 3 |
| `EventHandlers.js` | 25 | Patterns 2, 7 |
| `OnEnteringState.js` | 8 | Patterns 1, 4 |
| `OnEnteringState.7s5s.js` | 18 | Patterns 1, 4 |
| `OnLeavingState.7s5s.js` | 22 | Patterns 1, 4, 6 |
| `OnUpdateActionButtons.js` | 3 | Patterns 1, 5 |

**Total: ~123 code changes across 12 files**

---

## Testing Checklist

After implementation, verify:

- [ ] Hand displays correctly on page load
- [ ] Hand floats when scrolling down
- [ ] Hand returns to placeholder when scrolling up
- [ ] Collapse/expand button works
- [ ] Single selection mode works
- [ ] Multiple selection mode works
- [ ] Cards can be added during gameplay
- [ ] Cards can be removed during gameplay
- [ ] Tooltips appear on hover
- [ ] Special condition chips (Cat's Embargo) display correctly
- [ ] Works correctly in duel mode (when hand moves to duel area)
- [ ] Hidden correctly in spectator mode
- [ ] Responsive on mobile devices
- [ ] Selection callback (onFactionCardClicked) fires correctly

---

## Rollback Plan

If issues arise, the migration can be rolled back by:
1. Removing the bga-cards import
2. Reverting template to original structure
3. Reverting CSS changes
4. Reverting Setup.js to use ebg.stock()
5. Reverting Utilities.js
6. Search/replace all pattern changes back to original

Keep the original code commented out during initial testing for easy comparison.

---

## Notes

- The `approachDeck` and `chooseList` stocks still use `ebg.stock()` - the compatibility helper in `addCardToDeck` handles both
- The floating hand logic uses vanilla scroll events for performance
- Card animations are handled automatically by bga-cards
- The CardManager is stored separately (`this.factionHandManager`) in case you want to share it or create additional stocks later
