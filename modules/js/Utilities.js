/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.utilities', null, {

    // Tippy.js wrapper to replace BGA's addTooltipHtml
    // Stores all tippy instances for cleanup
    _tippyInstances: [],
    
    // Queue for tooltips requested before tippy.js is loaded
    _pendingTooltips: [],
    _pendingTooltipClasses: [],
    _tippyReady: false,

    /**
     * Initialize Tippy.js - call this once tippy is confirmed loaded
     * Processes any queued tooltip requests
     */
    initTippy: function() {
        if (this._tippyReady) return;
        
        // Check that Tippy is loaded (Popper is guaranteed by tippy-loader.js)
        if (typeof window.tippy !== 'function') {
            // Library not ready yet, retry after a short delay
            setTimeout(() => this.initTippy(), 50);
            return;
        }
        
        this._tippyReady = true;
        
        // Process pending individual tooltips
        this._pendingTooltips.forEach(({elementId, html, delay}) => {
            this._createTippyTooltip(elementId, html, delay);
        });
        this._pendingTooltips = [];
        
        // Process pending class tooltips
        this._pendingTooltipClasses.forEach(({cssClass, html, delay}) => {
            this._createTippyTooltipForClass(cssClass, html, delay);
        });
        this._pendingTooltipClasses = [];
    },

    /**
     * Internal method to actually create a tippy tooltip
     */
    _createTippyTooltip: function(elementId, html, delay) {
        const element = document.getElementById(elementId);
        if (!element) {
            // Element may have been removed from DOM, silently skip
            return null;
        }

        // Destroy existing tippy on this element if any
        if (element._tippy) {
            element._tippy.destroy();
        }

        const instance = window.tippy(element, {
            content: html,
            allowHTML: true,
            delay: [delay, 0],
            interactive: true,
            appendTo: document.body,
            maxWidth: 'none',
            theme: '7sfs',
            placement: 'auto',
            zIndex: 10000,
        });

        this._tippyInstances.push(instance);
        return instance;
    },

    /**
     * Internal method to actually create tippy tooltips for a class
     */
    _createTippyTooltipForClass: function(cssClass, html, delay) {
        const elements = document.querySelectorAll('.' + cssClass);
        elements.forEach((element) => {
            // Destroy existing tippy on this element if any
            if (element._tippy) {
                element._tippy.destroy();
            }

            const instance = window.tippy(element, {
                content: html,
                allowHTML: true,
                delay: [delay, 0],
                interactive: true,
                appendTo: document.body,
                maxWidth: 'none',
                theme: '7sfs',
                placement: 'auto',
                zIndex: 10000,
            });

            this._tippyInstances.push(instance);
        });
    },

    /**
     * Add a tooltip using Tippy.js (replaces this.addTooltipHtml)
     * @param {string} elementId - The ID of the element to attach the tooltip to
     * @param {string} html - The HTML content for the tooltip
     * @param {number} delay - Optional delay in ms before showing (default: 200)
     */
    addTippyTooltip: function(elementId, html, delay = 200) {
        // If tippy not ready, queue it
        if (!this._tippyReady) {
            this._pendingTooltips.push({elementId, html, delay});
            return null;
        }

        return this._createTippyTooltip(elementId, html, delay);
    },

    /**
     * Add a tooltip to all elements with a given class using Tippy.js (replaces this.addTooltipHtmlToClass)
     * @param {string} cssClass - The CSS class to target
     * @param {string} html - The HTML content for the tooltip
     * @param {number} delay - Optional delay in ms before showing (default: 200)
     */
    addTippyTooltipToClass: function(cssClass, html, delay = 200) {
        // If tippy not ready, queue it
        if (!this._tippyReady) {
            this._pendingTooltipClasses.push({cssClass, html, delay});
            return;
        }

        this._createTippyTooltipForClass(cssClass, html, delay);
    },

    /**
     * Destroy all tippy instances (useful for cleanup)
     */
    destroyAllTippyTooltips: function() {
        this._tippyInstances.forEach((instance) => {
            if (instance && !instance.state.isDestroyed) {
                instance.destroy();
            }
        });
        this._tippyInstances = [];
    },

    deckPickerShowTab: function(tabIndex) {
        const tabs = document.querySelectorAll('._7sfs-deck-picker-tab-content');
        tabs.forEach((tab, i) => {
          tab.classList.toggle('_7sfs-deck-picker-active', i === tabIndex);

          //If tableIndex matches get the deck name from the data-deck-name attribute
          if (tabIndex === i) {
            const deckName = tab.getAttribute('data-deck-name');
            this.selectedDeck = deckName;
            var btnDeckSelect = document.getElementById('btnDeckSelect');
            btnDeckSelect.disabled = false;
        }
        });


        this.selectedDeck = tabIndex;
    },

    deckPickerDeckSelected: function() {
        const tabs = document.querySelectorAll('._7sfs-deck-picker-tab-content');
        tabs.forEach((tab, i) => {
            if (this.selectedDeck === i) {
                const id = tab.getAttribute('id');
                this.onStarterDeckSelected(id);
            }
        });
    },

    addCardToDeck: function( deck, card )
    {
        if (!this.cardProperties[card.id])
            this.cardProperties[card.id] = card;

        // Check if using bga-cards (HandStock/CardStock) or legacy ebg.stock
        if (typeof deck.addCard === 'function') {
            // bga-cards HandStock/CardStock
            deck.addCard(card);
            
            // Apply styling if this is the factionHand
            if (deck === this.factionHand) {
                this.applyFactionHandCardStyle(card);
            }
        } else {
            // Legacy ebg.stock
            const weight = card.type === "Scheme" || card.type === 'Attachment' ? 1 : 2;
            deck.addItemType(card.id, weight, this.getCardImageUrlRoot(card.image) + card.image, 0);
            deck.addToStockWithId(card.id, card.id);
        }
    },

    // Helper to remove card (for compatibility)
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

    // Apply styling to a faction hand card (since bga-cards setupDiv callback doesn't work)
    applyFactionHandCardStyle: function(card)
    {
        const cardElement = this.factionHand.getCardElement(card);
        if (!cardElement) {
            console.warn('Could not find card element for', card.id);
            return;
        }
        
        // Store reference
        this.cardProperties[card.id].divId = cardElement.id;
        
        // Apply dimensions (1.5x scale for floating hand)
        const scaledWidth = Math.round(this.wholeCardWidth * 1.5);
        const scaledHeight = Math.round(this.wholeCardHeight * 1.5);
        
        cardElement.style.width = `${scaledWidth}px`;
        cardElement.style.height = `${scaledHeight}px`;
        
        // Set CSS variables
        cardElement.style.setProperty('--bga-cards_card-width', `${scaledWidth}px`);
        cardElement.style.setProperty('--bga-cards_card-height', `${scaledHeight}px`);
        
        // Find and style the front face
        const frontDiv = cardElement.querySelector('.bga-cards_card-side.front');
        if (frontDiv) {
            frontDiv.style.backgroundImage = `url('${this.getCardImageUrlRoot(card.image) + card.image}')`;
            frontDiv.style.backgroundSize = 'cover';
            frontDiv.style.borderRadius = '4px';
            
            // Assign an ID to the front div if it doesn't have one (bga-cards doesn't set this)
            if (!frontDiv.id) {
                frontDiv.id = `${cardElement.id}_front`;
            }
            
            // Add tooltip (with class for mobile scaling)
            if (this.getGameUserPreference(this.USER_PREFERENCES_CARD_HOVER_TYPE) == 2) {
                const cardData = this.cardProperties[card.id];
                if (cardData.type === 'Character') {
                    this.createTextTooltipForCharacter(cardData, frontDiv.id);
                } else if (cardData.type === 'Scheme') {
                    this.createTextTooltipForScheme(cardData, frontDiv.id);
                } else if (cardData.type === 'Attachment') {
                    this.createTextTooltipForAttachment(cardData, frontDiv.id);
                } else if (cardData.type === 'Risk') {
                    this.createTextTooltipForRisk(cardData, frontDiv.id);
                } else {
                    this.addTippyTooltip(frontDiv.id, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />`, this.STOCK_CARD_TOOLTIP_DELAY);
                }
            } else {
                this.addTippyTooltip(frontDiv.id, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />`, this.STOCK_CARD_TOOLTIP_DELAY);
            }
        }
    },

    // Setup floating hand behavior based on placeholder visibility
    setupFloatingHand: function() {
        const wrapper = $('factionHand-wrapper');
        const placeholder = $('factionHand-placeholder');
        
        if (!wrapper || !placeholder) return;
        
        // Check if mobile - skip floating logic entirely on mobile
        // Mobile includes portrait (width <= 768) and landscape (height <= 500 with landscape orientation)
        const isMobile = () => window.innerWidth <= 768 || (window.innerHeight <= 500 && window.innerWidth > window.innerHeight);
        
        // On mobile, don't set up floating behavior at all
        if (isMobile()) {
            this.checkFloatingHand = () => {}; // No-op
            return;
        }
        
        // Track current floating state to avoid unnecessary DOM updates
        let isCurrentlyFloating = null;
        let rafPending = false;
        let cooldownUntil = 0;
        
        // Hysteresis threshold to prevent jitter at boundary (pixels)
        const HYSTERESIS = 50;
        // How far past the placeholder (in px) scrolling continues before the hand anchors
        const FLOAT_OFFSET = 1000;
        // Cooldown period after state change (ms)
        const COOLDOWN_MS = 300;
        
        // Core check function
        const doCheck = (forcedCheck) => {
            rafPending = false;
            
            // Skip checks during cooldown period (unless forced)
            if (!forcedCheck && Date.now() < cooldownUntil) {
                return;
            }
            
            const placeholderRect = placeholder.getBoundingClientRect();
            
            // Float when placeholder is visible or within FLOAT_OFFSET of the viewport top
            // Park in placeholder when scrolled well past it
            let shouldFloat;
            
            if (isCurrentlyFloating === null) {
                // Initial state - no hysteresis
                shouldFloat = placeholderRect.bottom > -FLOAT_OFFSET;
            } else if (isCurrentlyFloating) {
                // Currently floating - need to scroll further down to park (add hysteresis)
                shouldFloat = placeholderRect.bottom > -(FLOAT_OFFSET + HYSTERESIS);
            } else {
                // Currently parked - need to scroll further up to float (add hysteresis)
                shouldFloat = placeholderRect.bottom > -(FLOAT_OFFSET - HYSTERESIS);
            }
            
            // Only update DOM if state actually changed
            if (shouldFloat !== isCurrentlyFloating) {
                isCurrentlyFloating = shouldFloat;
                cooldownUntil = Date.now() + COOLDOWN_MS;
                
                if (shouldFloat) {
                    // Placeholder visible -> float at bottom
                    dojo.removeClass(wrapper, '_7sfs-hand-not-floating');
                    dojo.addClass(placeholder, '_7sfs-hand-floating');
                } else {
                    // Placeholder not visible -> park in placeholder
                    dojo.addClass(wrapper, '_7sfs-hand-not-floating');
                    dojo.removeClass(placeholder, '_7sfs-hand-floating');
                }
            }
        };
        
        // Throttled scroll handler using requestAnimationFrame
        const checkFloating = () => {
            if (!rafPending) {
                rafPending = true;
                requestAnimationFrame(() => doCheck(false));
            }
        };
        
        // Store the check function so it can be called externally
        // External calls bypass throttling and cooldown for immediate response
        this.checkFloatingHand = () => {
            isCurrentlyFloating = null; // Reset state for immediate recalculation
            cooldownUntil = 0; // Clear cooldown
            doCheck(true);
        };
        
        // Check on scroll and resize
        window.addEventListener('scroll', checkFloating, { passive: true });
        window.addEventListener('resize', checkFloating, { passive: true });
        
        // Initial check
        doCheck(true);
    },

    createHome: function( playerId, playerColor, leader )
    {
        // Place the viewing player's home board closest to the city
        dojo.place( this.format_block( 'jstpl_home', {
            id: playerId,
            faction: leader.faction.toLowerCase(),
            crewcap: leader.modifiedCrewCap,
            panache: leader.modifiedPanache,
            player_color: playerColor,
        }),
        playerId == this.player_id ? 'home_wrapper' : 'home_anchor',
        playerId == this.player_id ? 'first' : 'before' );

        this.addTippyTooltip( `${playerId}-crewcap`, `<div class='_7sfs-basic-tooltip'>${_('Current Crew Capacity')}</div>` );
        this.addTippyTooltip( `${playerId}-discard`, `<div class='_7sfs-basic-tooltip'>${_('Faction Deck Discard Pile')}</div>` );
        this.addTippyTooltip( `${playerId}-locker`, `<div class='_7sfs-basic-tooltip'>${_('Player Locker')}</div>` );
        this.addTippyTooltip( `${playerId}-panache`, `<div class='_7sfs-basic-tooltip'>${_('Current Panache')}</div>` );
    },

    getCardPropertiesByDivId: function( divId )
    {
        for ( const cardId in this.cardProperties )
        {
            if (this.cardProperties[cardId]?.divId === divId) {
                return this.cardProperties[cardId];
            }
        }

        return null;
    },

    getCardPropertiesByName: function( name )
    {
        for ( const cardId in this.cardProperties )
        {
            if (this.cardProperties[cardId]?.name === name) {
                return this.cardProperties[cardId];
            }
        }

        return null;
    },
    
    createCard: function( divId, card, targetDiv, inDuel = false )
    {
        if (card.faceDown)
        {
            const playerInfo = this.gamedatas.players[card.controllerId];
            this.createHiddenCard(divId, card, targetDiv, playerInfo.color);
        }
        else if (card.type === 'Character')
        {
            if (card.controllerId !== 0) {
                const playerInfo = this.gamedatas.players[card.controllerId];
                this.createCharacterCard(divId, playerInfo.color, card, targetDiv, inDuel);
                dojo.style( `${divId}_wealth_cost`, 'display', 'none' );
            }
            else {
                this.createCharacterCard(divId, '', card, targetDiv, inDuel);
                dojo.removeClass(`${divId}-player-color`, '_7sfs-character-player-color');
            }
        }
        else if (card.type === 'Event')
        {
            this.createEventCard(divId, card, targetDiv);
        }
        else if (card.type === 'Attachment') 
        {
            this.createAttachmentCard(divId, card, targetDiv, inDuel);
        }
        else if (card.type === 'Scheme') {
            this.createSchemeCard(divId, card, targetDiv);
        }
    },

    createTooltipForCard: function(card)
    {
        if (this.getGameUserPreference(this.USER_PREFERENCES_CARD_HOVER_TYPE) == 2) {
            if (card.type === 'Character') {
                this.createTextTooltipForCharacter(card);
                return;
            }
            if (card.type === 'Scheme') {
                this.createTextTooltipForScheme(card);
                return;
            }
            if (card.type === 'Attachment') {
                this.createTextTooltipForAttachment(card);
                return;
            }
            if (card.type === 'Risk') {
                this.createTextTooltipForRisk(card);
                return;
            }
        }

        if (!card.controllerId) {
            this.addTippyTooltip(`${card.divId}_image`, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />`, this.CARD_TOOLTIP_DELAY);
            return;
        }

        const traits = card.traits?.join(', ') ?? '';
        const abilityStyle = (available) => `background-color: gold; color: black;${available ? '' : ' text-decoration: line-through;'}`;
        const html = `
        <div style="position:relative;">
            <img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />
            <div class="_7sfs-card-info">
                <div class="_7sfs-card-info-text" style="background-color:white; color:black">Traits: ${traits}</div>
                ${card.actions?.map((action) => `<div class="_7sfs-card-info-text" style="${abilityStyle(action.available)}">${_('Action:')} ${_(action.shortName)}</div>`).join('') ?? ''}
                ${card.reactions?.map((reaction) => `<div class="_7sfs-card-info-text" style="${abilityStyle(reaction.available)}">${_('Reaction:')} ${_(reaction.shortName)}</div>`).join('') ?? ''}
                ${card.maneuvers?.map((maneuver) => `<div class="_7sfs-card-info-text" style="${abilityStyle(maneuver.available)}">${_('Maneuver:')} ${_(maneuver.shortName)}</div>`).join('') ?? ''}
                ${card.techniques?.map((technique) => `<div class="_7sfs-card-info-text" style="${abilityStyle(technique.available)}">${_('Technique:')} ${_(technique.shortName)}</div>`).join('') ?? ''}
            </div>
        </div>
        `;
        this.addTippyTooltip(`${card.divId}_image`, html, this.CARD_TOOLTIP_DELAY);
    },

    getSetDisplayName: function(expansionName) {
        const setNames = { '_7s5s': _('Core') };
        return setNames[expansionName] ?? expansionName ?? '';
    },

    createTextTooltipForCharacter: function(card, nodeId)
    {
        nodeId = nodeId ?? `${card.divId}_image`;
        const strikeIf = (used, text) => used ? `<s>${text}</s>` : text;
        const row = (label, value, vtop) => `<tr><td style="padding-right:10px;${vtop ? 'vertical-align:top;' : ''}">${label}</td><td>${value}</td></tr>`;
        const traits = card.traits?.join(', ') ?? '';
        const combat = card.dashedCombat ? '-' : card.modifiedCombat;
        const finesse = card.dashedFinesse ? '-' : card.modifiedFinesse;
        const influence = card.dashedInfluence ? '-' : card.modifiedInfluence;

        let rows = [
            row(_('Name'), _(card.name)),
            row(_('Type'), _(card.type)),
            row(_('Set'), this.getSetDisplayName(card.expansionName)),
            row(_('Card #'), card.cardNumber ?? ''),
            row(_('Title'), _(card.title)),
            row(_('Resolve'), card.modifiedResolve),
            row(_('Combat'), combat),
            row(_('Finesse'), finesse),
            row(_('Influence'), influence),
        ];

        if (card.traits?.includes('Leader')) {
            rows.push(row(_('Crew Cap'), card.modifiedCrewCap));
            rows.push(row(_('Panache'), card.modifiedPanache));
        }

        rows.push(
            row(_('Traits'), traits),
            row(_('Text'), _(card.text), true)
        );

        if (card.controllerId && card.location !== 'Approach' && card.location !== 'hand') {
            if (card.actions?.length) {
                rows.push(row(_('Available&nbsp;Actions'), card.actions.map(a => strikeIf(!a.available, _(a.shortName))).join('<br>'), true));
            }
            if (card.reactions?.length) {
                rows.push(row(_('Available&nbsp;Reactions'), card.reactions.map(r => strikeIf(!r.available, _(r.shortName))).join('<br>'), true));
            }
            if (card.techniques?.length) {
                rows.push(row(_('Available&nbsp;Techniques'), card.techniques.map(t => strikeIf(!t.available, _(t.shortName))).join('<br>'), true));
            }
        }

        const html = `<div class='_7sfs-basic-tooltip'><table style="border:none;border-collapse:collapse;">${rows.join('')}</table></div>`;
        this.addTippyTooltip(nodeId, html, this.CARD_TOOLTIP_DELAY);
    },

    createTextTooltipForScheme: function(card, nodeId)
    {
        nodeId = nodeId ?? `${card.divId}_image`;
        const strikeIf = (used, text) => used ? `<s>${text}</s>` : text;
        const traits = card.traits?.join(', ') ?? '';

        let lines = [
            `${_('Name')}: ${_(card.name)}`,
            `${_('Type')}: ${_(card.type)}`,
            `${_('Set')}: ${this.getSetDisplayName(card.expansionName)}`,
            `${_('Card #')}: ${card.cardNumber ?? ''}`,
            `${_('Traits')}: ${traits}`,
            `${_('Initiative')}: ${card.initiative}`,
            `${_('Panache&nbsp;Modifier')}: ${card.panacheModifier}`,
            `${_('Text')}: ${_(card.text)}`
        ];

        if (card.controllerId && card.location !== 'Approach') {
            if (card.actions?.length) {
                lines.push(`${_('Available&nbsp;Actions')}:<br>` + card.actions.map(a => strikeIf(!a.available, _(a.shortName))).join('<br>'));
            }
            if (card.reactions?.length) {
                lines.push(`${_('Available&nbsp;Reactions')}:<br>` + card.reactions.map(r => strikeIf(!r.available, _(r.shortName))).join('<br>'));
            }
            if (card.techniques?.length) {
                lines.push(`${_('Available&nbsp;Techniques')}:<br>` + card.techniques.map(t => strikeIf(!t.available, _(t.shortName))).join('<br>'));
            }
        }

        const html = `<div class='_7sfs-basic-tooltip'>${lines.join('<br>')}</div>`;
        this.addTippyTooltip(nodeId, html, this.CARD_TOOLTIP_DELAY);
    },

    createTextTooltipForAttachment: function(card, nodeId)
    {
        nodeId = nodeId ?? `${card.divId}_image`;
        const strikeIf = (used, text) => used ? `<s>${text}</s>` : text;
        const row = (label, value, vtop) => `<tr><td style="padding-right:10px;${vtop ? 'vertical-align:top;' : ''}">${label}</td><td>${value}</td></tr>`;
        const fmtMod = (v) => v > 0 ? `+${v}` : (v || '-');
        const traits = card.traits?.join(', ') ?? '';
        const riposte = card.dashedRiposte ? '-' : (card.riposte ?? '-');
        const parry = card.dashedParry ? '-' : (card.parry ?? '-');
        const thrust = card.dashedThrust ? '-' : (card.thrust ?? '-');

        let rows = [
            row(_('Name'), _(card.name)),
            row(_('Type'), _(card.type)),
            row(_('Set'), this.getSetDisplayName(card.expansionName)),
            row(_('Card #'), card.cardNumber ?? ''),
            row(_('Cost'), card.wealthCost ?? ''),
            row(_('Resolve&nbsp;Modifier'), fmtMod(card.resolveModifier)),
            row(_('Combat&nbsp;Modifier'), fmtMod(card.combatModifier)),
            row(_('Finesse&nbsp;Modifier'), fmtMod(card.finesseModifier)),
            row(_('Influence&nbsp;Modifier'), fmtMod(card.influenceModifier)),
            row(_('Riposte'), riposte),
            row(_('Parry'), parry),
            row(_('Thrust'), thrust),
            row(_('Traits'), traits),
            row(_('Text'), _(card.text), true),
        ];

        if (card.controllerId && card.location !== 'hand') {
            if (card.actions?.length) {
                rows.push(row(_('Available&nbsp;Actions'), card.actions.map(a => strikeIf(!a.available, _(a.shortName))).join('<br>'), true));
            }
            if (card.reactions?.length) {
                rows.push(row(_('Available&nbsp;Reactions'), card.reactions.map(r => strikeIf(!r.available, _(r.shortName))).join('<br>'), true));
            }
            if (card.maneuvers?.length) {
                rows.push(row(_('Available&nbsp;Maneuvers'), card.maneuvers.map(m => strikeIf(!m.available, _(m.shortName))).join('<br>'), true));
            }
            if (card.techniques?.length) {
                rows.push(row(_('Available&nbsp;Techniques'), card.techniques.map(t => strikeIf(!t.available, _(t.shortName))).join('<br>'), true));
            }
        }

        const html = `<div class='_7sfs-basic-tooltip'><table style="border:none;border-collapse:collapse;">${rows.join('')}</table></div>`;
        this.addTippyTooltip(nodeId, html, this.CARD_TOOLTIP_DELAY);
    },

    createTextTooltipForRisk: function(card, nodeId)
    {
        nodeId = nodeId ?? `${card.divId}_image`;
        const row = (label, value, vtop) => `<tr><td style="padding-right:10px;${vtop ? 'vertical-align:top;' : ''}">${label}</td><td>${value}</td></tr>`;
        const traits = card.traits?.join(', ') ?? '';
        const riposte = card.dashedRiposte ? '-' : card.riposte;
        const parry = card.dashedParry ? '-' : card.parry;
        const thrust = card.dashedThrust ? '-' : card.thrust;

        let rows = [
            row(_('Name'), _(card.name)),
            row(_('Type'), _(card.type)),
            row(_('Set'), this.getSetDisplayName(card.expansionName)),
            row(_('Card #'), card.cardNumber ?? ''),
            row(_('Cost'), card.wealthCost ?? ''),
            row(_('Riposte'), riposte),
            row(_('Parry'), parry),
            row(_('Thrust'), thrust),
            row(_('Traits'), traits),
            row(_('Text'), _(card.text), true),
        ];

        const html = `<div class='_7sfs-basic-tooltip'><table style="border:none;border-collapse:collapse;">${rows.join('')}</table></div>`;
        this.addTippyTooltip(nodeId, html, this.CARD_TOOLTIP_DELAY);
    },

    createCharacterCard: function( divId, color, character, targetDiv, inDuel = false )
    {
        //Set the divId of the card
        character.divId = divId;

        //Add to the card properties cache if not in duel - these are temporary copies of the character cards
        if (!inDuel)
            this.cardProperties[character.id] = character;

        const wealthCost = character.wealthCost ? character.wealthCost : '';
        const combat = character.dashedCombat ? '-' : character.modifiedCombat;
        const finesse = character.dashedFinesse ? '-' : character.modifiedFinesse;
        const influence = character.dashedInfluence ? '-' : character.modifiedInfluence;

        const placement = inDuel ? 'first' : 'before';

        dojo.place( this.format_block( 'jstpl_character', {
            id: divId,
            attachmentCount: character.attachedCards?.length ?? 0,
            faction: character.faction.toLowerCase(),
            image: this.getCardImageUrlRoot(character.image) + character.image,
            player_color: color,
            resolve: character.modifiedResolve,
            combat: combat,
            finesse: finesse,
            influence: influence,
            cost: wealthCost,
        }), targetDiv, placement );

        if (character.combat != character.modifiedCombat) 
            dojo.addClass(`${divId}_combat_value`, '_7sfs-modified-stat-value');

        if (character.finesse != character.modifiedFinesse) 
            dojo.addClass(`${divId}_finesse_value`, '_7sfs-modified-stat-value');

        if (character.resolve != character.modifiedResolve || character.wounds > 0)
            dojo.addClass(`${divId}_resolve_value`, '_7sfs-modified-stat-value');

        if (character.influence != character.modifiedInfluence)
            dojo.addClass(`${divId}_influence_value`, '_7sfs-modified-stat-value');

        if (!character.wealthCost || character.controllerId) {
            dojo.style( `${divId}_wealth_cost`, 'display', 'none' );
        }

        this.createTooltipForCard(character);

        //Check for any special conditions where a token has to be displayed
        if (character.conditions.includes(this.ADVERSARY_OF_YEVGENI)) {
            //Get the first child of element divId
            const id = `${divId}_yevgeni_adversary`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-yevgeni-adversary-chip',
            }),  `${divId}_image`, 'last');
            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
        }
        if (character.conditions.includes(this.MARYAM_BENU_PLEROMA_ABILITY_USED)) {
            //Get the first child of element divId
            const id = `${divId}_maryam_benu_pleroma_ability_used`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-maryam-benu-pleroma-ability-used-chip',
            }),  `${divId}_image`, 'last');
            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Maryam Benu Pleroma Ability Used")}</div>` );
        }
        if (character.conditions.includes(this.INDOMITABLE_WILL_CONDITION)) {
            //Get the first child of element divId
            const id = `${divId}_indomitable_will_condition`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-indomitable-will-condition-chip',
            }),  `${divId}_image`, 'last');
            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("This character has Indomitable Will")}</div>` );
        }
        if (character.conditions.includes(this.CHALLENGER)) {
            id = `${divId}_challenger`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-challenger-chip',
            }),  `${divId}_image`, 'last');
            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Duel Challenger")}</div>` );
        }
        if (character.conditions.includes(this.DEFENDER)) {
            id = `${divId}_defender`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-defender-chip',
            }),  `${divId}_image`, 'last');
            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
        }
        if (character.wounds > 0)
        {
            const woundChip = `${divId}_wounds`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: woundChip,
                class: '_7sfs-wound-chip',
            }),  `${divId}_image`, 'last');
            $(woundChip).innerHTML = character.wounds;
            this.addTippyTooltip( woundChip, `<div class='_7sfs-basic-tooltip'>${_("Wounds")}</div>` );
        }

        if (character.engaged) 
            dojo.addClass(`${divId}_image`, '_7sfs-engaged');

        //Display the attachments in front of the character, offset
        character.attachedCards?.forEach((attachment) => {
            let divId = this.createCardId(attachment, attachment.location);
            //If this is a duel character then the attachment will have a different divId 
            // so that it doesn't interfere with the actual attachment in play
            if (character.duelPrefix)
                divId = `${character.duelPrefix}_${attachment.id}`;
            this.createAttachmentCard(divId, attachment, character.divId, inDuel);
        });

        dojo.connect($(divId), 'onclick', this, 'splayAttachments');
    },  

    splayAttachments: function( event )
    {
        const parent = event.target.parentElement;
        if (!dojo.hasClass(event.target, '_7sfs-selectable') && dojo.hasClass(parent, '_7sfs-attachment-container'))
        {
            dojo.toggleClass(parent, '_7sfs-attachment-container-splayed');

            //Query all sub elements of the parent with the class _7sfs-attached-card
            const attachedCards = parent.querySelectorAll('._7sfs-attached-card');
            attachedCards.forEach((card) => {
                dojo.toggleClass(card, '_7sfs-attached-card-splayed');
            });
        }
    },

    createHiddenCard: function( divId, card, targetDiv, playerColor )
    {
        //Set the divId of the card
        card.divId = divId;

        //Add to the card properties cache
        this.cardProperties[card.id] = card;

        dojo.place( this.format_block( 'jstpl_card_hidden', {
            id: divId,
            image: card.cardBackImage,
            player_color: playerColor,
        }), targetDiv, "before" );

        if (card.controllerId === this.player_id)
            this.addTippyTooltip( divId, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />`, this.CARD_TOOLTIP_DELAY);
    },

    createEventCard: function( divId, event, targetDiv )
    {
        //Set the divId of the card
        event.divId = divId;

        //Add to the card properties cache
        this.cardProperties[event.id] = event;

        dojo.place( this.format_block( 'jstpl_card_event', {
            id: divId,
            image: this.getCardImageUrlRoot(event.image) + event.image,
        }), targetDiv, "before" );

        this.addTippyTooltip( divId, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(event.image) + event.image}" />`, this.CARD_TOOLTIP_DELAY);

        if (event.reknown > 0) {
            divId = `${divId}-reknown`;
            dojo.place( this.format_block( 'jstpl_reknown_chip', {
                id: divId,
                amount: event.reknown,
            }),  `${event.divId}_image`, 'last');
            }
    },  

    createSchemeCard: function( divId, scheme, location )
    {
        //Set the divId of the card
        scheme.divId = divId;

        //Add to the card properties cache
        this.cardProperties[scheme.id] = scheme;

        const playerId = scheme.controllerId;
        const playerInfo = this.gamedatas.players[playerId];

        let position = 'after';
        
        //Is the card in the city due to some effect?
        const schemeInCity = this.isCardInCity(scheme.id);
        if (schemeInCity) {
            position = 'before';
        }

        dojo.place( this.format_block( 'jstpl_card_scheme', {
            id: divId,
            image: this.getCardImageUrlRoot(scheme.image) + scheme.image,
            player_color: playerInfo.color,
            initiative: scheme.initiative,
            panache: this.formatModifer(scheme.panacheModifier),
        }), location, position );

        if (schemeInCity) {
            dojo.addClass(divId, '_7sfs-scheme-container-in-city');
            const img = $(`${divId}_image`);
            dojo.addClass(img, '_7sfs-scheme-in-city');
        }
        else {
            dojo.removeClass(`${divId}-player-color`, '_7sfs-scheme-player-color');
        }
        
        this.createTooltipForCard(scheme);
    },  

    createAttachmentCard: function( divId, attachment, targetDiv, inDuel = false )
    {
        //Set the divId of the card
        attachment.divId = divId;

        //Add to the card properties cache
        if (!inDuel)
            this.cardProperties[attachment.id] = attachment;

        //Get the attached character and set up as a container
        if (attachment.attachedToId) {
            const character = this.cardProperties[attachment.attachedToId];
            if (character)
                dojo.addClass(character.divId, '_7sfs-attachment-container');
            else
                attachment.attachedToId = undefined;
        }

        let placement = inDuel ? 'last' : attachment.attachedToId ? 'last' : 'before';
        let attachmentIndex = attachment.attachmentIndex ?? 0;

        dojo.place( this.format_block( 'jstpl_card_attachment', {
            id: divId,
            attachmentIndex: attachmentIndex,
            faction: attachment.faction?.toLowerCase(),
            image: this.getCardImageUrlRoot(attachment.image) + attachment.image,
            resolve: this.attachmentFormatModifer(attachment.resolveModifier),
            combat: this.attachmentFormatModifer(attachment.combatModifier),
            finesse: this.attachmentFormatModifer(attachment.finesseModifier),
            influence: this.attachmentFormatModifer(attachment.influenceModifier),
            cost: attachment.wealthCost,
        }), targetDiv, placement );

        if (attachment.controllerId)
        {
            dojo.addClass(divId, '_7sfs-attached-card');
            dojo.addClass(`${divId}_wealth_cost`, 'hidden');
        } 

        if (!attachment.showStatModifiers)
        {
            //Remove the class from child elements of the divId
            dojo.removeClass($(`${divId}_resolve`), '_7sfs-card-resolve');
            dojo.removeClass($(`${divId}_wealth_cost`), '_7sfs-card-wealth-cost _7sfs-city-attachment-wealth-cost');
            dojo.removeClass($(`${divId}_combat_box`), '_7sfs-card-stat-box _7sfs-card-combat-box');
            dojo.removeClass($(`${divId}_finesse_box`), '_7sfs-card-stat-box _7sfs-card-finesse-box');
            dojo.removeClass($(`${divId}_influence_box`), '_7sfs-card-stat-box _7sfs-card-influence');

            dojo.addClass(divId, '_7sfs-attached-card-no-modifiers');
        }
        
        if (attachment.engaged) 
            dojo.addClass(`${divId}_image`, '_7sfs-engaged');

        this.createTooltipForCard(attachment);
    },

    formatModifer: function( modifier )
    {
        if (modifier > 0) {
            return `+${modifier}`;
        } else {
            return modifier;
        }
    },

    attachmentFormatModifer: function( modifier )
    {
        if (modifier > 0) {
            return `+${modifier}`;
        } else if (modifier === 0) {
            return '-';
        } else {
            return modifier;
        }
    },

    getListofAvailableCityLocationImages: function()
    {
        const playerCount = Object.keys(this.gamedatas.players).length;
        let locations = ['dock-image', 'forum-image', 'bazaar-image'];
        if (playerCount > 2) {
            locations.push('oles-inn-image');
        }
        if (playerCount > 3) {
            locations.push('garden-image');
        }

        return locations;
    },

    getListofOutermostCityLocations: function()
    {
        const playerCount = Object.keys(this.gamedatas.players).length;
        switch (playerCount) {
            case 1:
            case 2:
                return ['dock-image', 'bazaar-image'];
            case 3:
                return ['oles-inn-image', 'bazaar-image'];
            case 4:
                return ['oles-inn-image', 'garden-image'];
        }
    },

    getListOfLocationsAdjacentToLocation: function( location )
    {
        switch (location) {
            case 'dock-image':
                locations = ['forum-image', 'oles-inn-image'];
                break;
            case 'forum-image':
                locations = ['dock-image', 'bazaar-image'];
                break;
            case 'bazaar-image':
                locations = ['forum-image', 'garden-image'];
                break;
            case 'oles-inn-image':
                locations = ['dock-image'];
                break;
            case 'garden-image':
                locations = ['bazaar-image'];
                break;
        }

        const playerCount = Object.keys(this.gamedatas.players).length;

        //Remove The Gardens if there are only 3 players
        if (Object.keys(this.gamedatas.players).length < 4) {
            locations = locations.filter((location) => location !== 'garden-image');
        }

        //Remove Ole's Inn if there are only 2 players
        if (Object.keys(this.gamedatas.players).length < 3) {
            locations = locations.filter((location) => location !== 'oles-inn-image');
        }

        return locations;
    },

    setupNewStockCard: function( cardDiv, cardTypeId, cardId )
    {
        const card = this.cardProperties[cardTypeId];
        if (this.getGameUserPreference(this.USER_PREFERENCES_CARD_HOVER_TYPE) == 2) {
            if (card.type === 'Character') {
                this.createTextTooltipForCharacter(card, cardDiv.id);
                return;
            }
            if (card.type === 'Scheme') {
                this.createTextTooltipForScheme(card, cardDiv.id);
                return;
            }
            if (card.type === 'Attachment') {
                this.createTextTooltipForAttachment(card, cardDiv.id);
                return;
            }
            if (card.type === 'Risk') {
                this.createTextTooltipForRisk(card, cardDiv.id);
                return;
            }
        }
        this.addTippyTooltip( cardDiv.id, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(card.image) + card.image}" />`, this.STOCK_CARD_TOOLTIP_DELAY);
    },

    isCardInCity: function( cardId )
    {
        const card = this.cardProperties[cardId];
        return (card.location === this.LOCATION_CITY_DOCKS 
            || card.location === this.LOCATION_CITY_FORUM 
            || card.location === this.LOCATION_CITY_BAZAAR 
            || card.location === this.LOCATION_CITY_OLES_INN 
            || card.location === this.LOCATION_CITY_GOVERNORS_GARDEN);
    },

    isCardInPlay: function( cardId )
    {
        const card = this.cardProperties[cardId];
        return (this.isCardInCity(cardId) || card.location === this.LOCATION_PLAYER_HOME);
    },

    createCardId: function( card, location )
    {
        switch (location) {
            case this.LOCATION_CITY_OLES_INN:
                return card.controllerId ? `${card.controllerId}-${card.id}` : `oles-inn-${card.id}`;
            case this.LOCATION_CITY_DOCKS:
                return card.controllerId ? `${card.controllerId}-${card.id}` : `docks-${card.id}`;
            case this.LOCATION_CITY_FORUM:
                return card.controllerId ? `${card.controllerId}-${card.id}` : `forum-${card.id}`;
            case this.LOCATION_CITY_BAZAAR:
                return card.controllerId ? `${card.controllerId}-${card.id}` : `bazaar-${card.id}`;
            case this.LOCATION_CITY_GOVERNORS_GARDEN:
                return card.controllerId ? `${card.controllerId}-${card.id}` : `garden-${card.id}`;
            case this.LOCATION_PLAYER_HOME:
                return `${card.controllerId}-${card.id}`;
            default:
                return `${card.location}-${card.id}`;
        }
    },

    getTargetElementForLocation: function ( location, playerId = null )
    {
        switch (location) {
            case this.LOCATION_CITY_OLES_INN:
                return 'oles-inn-endcap';
            case this.LOCATION_CITY_DOCKS:
                return 'dock-endcap';
            case this.LOCATION_CITY_FORUM:
                return 'forum-endcap';
            case this.LOCATION_CITY_BAZAAR:
                return 'bazaar-endcap';
            case this.LOCATION_CITY_GOVERNORS_GARDEN:
                return 'garden-endcap';
            case this.LOCATION_PLAYER_HOME:
                return `${playerId}-home-anchor`
        }
    },

    makeCityLocationSelectable: function(location) {
        dojo.addClass(location, '_7sfs-selectable');
        dojo.style(location, 'cursor', 'pointer');
        const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
        this.connects.push(handle);
    },

    markCityLocationAsChosen: function(location) {
        dojo.addClass(location, '_7sfs-chosen');
    },
    
    makeCardSelectable: function(image) {
        dojo.addClass(image, '_7sfs-selectable');
        dojo.style(image, 'cursor', 'pointer');
        const handle = dojo.connect(image, 'onclick', this, 'onCardInPlayClicked');
        this.connects.push(handle);                        
    },   
    
    resetCityLocations: function() {
        const locations = this.getListofAvailableCityLocationImages();
        locations.forEach((location) => {
            dojo.removeClass(location, '_7sfs-selectable');
            dojo.removeClass(location, '_7sfs-selected');
            dojo.removeClass(location, '_7sfs-chosen');
            dojo.style(location, 'cursor', 'default');
        });

        const playerHome = this.getCityLocationElement(this.LOCATION_PLAYER_HOME);
        dojo.removeClass(playerHome, '_7sfs-selectable');
        dojo.removeClass(playerHome, '_7sfs-selected');
        dojo.removeClass(playerHome, '_7sfs-home-endcap-marker');
        dojo.style(playerHome, 'cursor', 'default');
    },
    
    clearCardAsSelectable: function(image) {
        dojo.removeClass(image, '_7sfs-selectable');
        dojo.removeClass(image, '_7sfs-selected');
        dojo.removeClass(image, '_7sfs-chosen');
        dojo.style(image, 'cursor', 'default');
    },

    attachCard: function( equipped, attachment) {
        if (!equipped.attachedCards) {
            equipped.attachedCards = [];
        }
        equipped.attachedCards.push(attachment);
        attachment.attachmentIndex = equipped.attachedCards.length;
    },

    unattachCard: function( equipped, attachment) {
        equipped.attachedCards = equipped.attachedCards.filter((card) => card.id !== attachment.id);
        //Reorder the attachmentIndex on the attached cards
        let index = 1;
        equipped.attachedCards.forEach((card) => {
            card.attachmentIndex = index++;
        });
    },

    moveAttachmentsToCharacters: function(list) {
        const attachments = list.filter((card) => card.type === 'Attachment');
        attachments.forEach((attachment, index) => {
            //If the attachment is not attached to a character, then ignore
            if (!attachment.attachedToId) return;
            
            //Remove the attachment from the list
            list = list.filter((card) => card.id !== attachment.id);
            let equipped = list.find((card) => card.id == attachment.attachedToId);
            if (equipped)            
                this.attachCard(equipped, attachment);
        });
        return list;
    },

    orderCardsInLocation: function(list) {
        //Move all the leaders to the beginning of the list
        const leaders = list.filter((card) => card.traits.includes('Leader'));
        list = leaders.concat(list.filter((card) => !card.traits.includes('Leader')));

        //Move all the schemes to the beginning of the list
        const schemes = list.filter((card) => card.type == 'Scheme');
        list = schemes.concat(list.filter((card) => card.type != 'Scheme'));

        //Move all characters that are not controlled to the beginning of the list
        const characters = list.filter((card) => card.type === 'Character' && card.controllerId == 0);
        list = characters.concat(list.filter((card) => card.type !== 'Character' || card.controllerId != 0));

        //Move all the attachments to the beginning of the list
        const attachments = list.filter((card) => card.type === 'Attachment' && card.attachedToId == 0);
        list = attachments.concat(list.filter((card) => card.type !== 'Attachment'));

        //Move all the events to the beginning of the list
        const events = list.filter((card) => card.type === 'Event');
        list = events.concat(list.filter((card) => card.type !== 'Event'));

        return list;
    },

    displayLocationControlChip: function( location ) {
        const controllerId = this.gamedatas.locationControllers[location];
        if (controllerId != 0) 
        {
            const player = this.gamedatas.players[controllerId];
            const imageElement = this.getCityLocationElement(location);
            dojo.place( this.format_block( 'jstpl_location_control_chip', {
                id: imageElement.id,
                player_color: player.color,
            }),  imageElement, 'before');
        }
    },

    displayDuelTable: function() {
        const city = $('city');
        dojo.place( this.format_block( 'jstpl_duel_table', {
        }),  city, 'before');
    },

    displayDuelRow: function(row)
    {
        const headerRow = $('duel_header_row');

        var maneuvers = row.maneuverNames;
        if (!maneuvers)
            maneuvers = [];
        else
            //Surround the maneuver names with div tags
            maneuvers = maneuvers.map((maneuver) => `<div>${maneuver}</div>`);

        var techniques = row.techniqueNames;
        if (!techniques)
            techniques = [];
        else
            //Surround the technique names with div tags
            techniques = techniques.map((technique) => `<div>${technique}</div>`);

        let techniqueMarkup = '';
        techniques.forEach((technique) => {
            techniqueMarkup += technique;
        });

        let maneuverMarkup = '';
        maneuvers.forEach((maneuver) => {
            maneuverMarkup += maneuver;
        });

        dojo.place( this.format_block( 'jstpl_duel_round', {
            round: row.round,
            challengerName: row.challengerName,
            startingChallengerThreat: row.startingChallengerThreat,
            defenderName: row.defenderName,
            startingDefenderThreat: row.startingDefenderThreat,
            combatRiposte: row.combatRiposte ?? 0,
            combatParry: row.combatParry ?? 0,
            combatThrust: row.combatThrust ?? 0,
            technique: techniques.length > 0 ? techniqueMarkup : 'Not Chosen',
            techniqueRiposte: row.techniqueRiposte ?? 0,
            techniqueParry: row.techniqueParry ?? 0,
            techniqueThrust: row.techniqueThrust ?? 0,
            maneuver: maneuvers.length > 0 ? maneuverMarkup : 'Not Chosen',
            maneuverRiposte: row.maneuverRiposte ?? 0,
            maneuverParry: row.maneuverParry ?? 0,
            maneuverThrust: row.maneuverThrust ?? 0,
            endingChallengerThreat: row.endingChallengerThreat,
            endingDefenderThreat: row.endingDefenderThreat,
            wounds: row.wounds,
        }),  headerRow, 'after');

        if (row.attachments)
            row.attachments.forEach((attachment) => {
                if (!this.cardProperties[attachment.id])
                    this.cardProperties[attachment.id] = attachment;
            });

        let containerDivId = `duel_round_${row.round}_actor`;
        row.actor.duelPrefix = `duel_${row.round}`;
        let actorDivId = `${row.actor.duelPrefix}_${row.actor.id}`;

        row.actor.attachments.forEach((attachmentId) => {
            const attachment = { ...this.cardProperties[attachmentId] };
            if (!attachment.attachedToId)
                attachment.attachedToId = row.actor.id;
            this.attachCard(row.actor, attachment);
        });
        this.createCard(actorDivId, row.actor, containerDivId, true);
        dojo.addClass(actorDivId, '_7sfs-attachment-container');

        const combatCards = row.combatCards;
        if (combatCards)
        {
            var dashedRiposte = true;
            var dashedParry = true;
            var dashedThrust = true;

            const divId = `duel_round_${row.round}_combat`;
            $(divId).innerHTML = '';
            combatCards.forEach((combatCard) => 
            {                
                dojo.place( this.format_block('jstpl_row_combat_card', { 
                    round: row.round,
                    id: combatCard.id,
                    image: this.getCardImageUrlRoot(combatCard.image) + combatCard.image 
                }),  divId, 'last');

                const cardDivId = `duel_round_${row.round}_combat_card_${combatCard.id}`;
                this.addTippyTooltip(cardDivId, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(combatCard.image) + combatCard.image}" />`, this.CARD_TOOLTIP_DELAY);
                if (row.gambled)
                {
                    dojo.addClass(divId, '_7sfs-engaged');
                    dojo.addClass(divId, '_7sfs-duel-row-combat-card-gambled');
                }

                if (! combatCard.dashedRiposte)
                    dashedRiposte = false;
                if (! combatCard.dashedParry)
                    dashedParry = false;
                if (! combatCard.dashedThrust)
                    dashedThrust = false;
            });

            if (dashedRiposte)
            {
                const riposteSpan = $(`duel_round_${row.round}_combat_riposte`);
                riposteSpan.innerHTML = '&mdash;';
                riposteSpan.style.color = 'red';
            }
            if (dashedParry)
            {
                const parrySpan = $(`duel_round_${row.round}_combat_parry`);
                parrySpan.innerHTML = '&mdash;';
                parrySpan.style.color = 'red';
            }
            if (dashedThrust)
            {
                const thrustSpan = $(`duel_round_${row.round}_combat_thrust`);
                thrustSpan.innerHTML = '&mdash;';
                thrustSpan.style.color = 'red';
            }
        }

        if (!row.combatCards)
        {
            dojo.addClass(`duel_round_${row.round}_combat`, '_7sfs-ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_combat_stats`, '_7sfs-ability-not-chosen');
        }
        if (!row.techniqueNames)
        {
            dojo.addClass(`duel_round_${row.round}_technique`, '_7sfs-ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_technique_stats`, '_7sfs-ability-not-chosen');
        }
        if (!row.maneuverNames)
        {
            dojo.addClass(`duel_round_${row.round}_maneuver`, '_7sfs-ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_maneuver_stats`, '_7sfs-ability-not-chosen');
        }

        if (row.startingChallengerThreat > 0)
            dojo.addClass(`duel_round_${row.round}_starting_challenger_threat`, '_7sfs-threat-chip-threatened');
        if (row.startingDefenderThreat > 0)
            dojo.addClass(`duel_round_${row.round}_starting_defender_threat`, '_7sfs-threat-chip-threatened');
        if (row.endingChallengerThreat > 0)
            dojo.addClass(`duel_round_${row.round}_ending_challenger_threat`, '_7sfs-threat-chip-threatened');
        if (row.endingDefenderThreat > 0)
            dojo.addClass(`duel_round_${row.round}_ending_defender_threat`, '_7sfs-threat-chip-threatened');

        if (row.challengerThreatIsLethal == 1)
            $(`duel_round_${row.round}_ending_challenger_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';
        if (row.defenderThreatIsLethal == 1)
            $(`duel_round_${row.round}_ending_defender_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';

        if (row.actorId === row.challengerId)
        {
            dojo.addClass(`duel_round_${row.round}_starting_challenger_threat_row`, '_7sfs-duel-acting-character');
            dojo.addClass(`duel_round_${row.round}_ending_challenger_threat_row`, '_7sfs-duel-acting-character');
            
        }
        if (row.actorId == row.defenderId)
        {
            dojo.addClass(`duel_round_${row.round}_starting_defender_threat_row`, '_7sfs-duel-acting-character');
            dojo.addClass(`duel_round_${row.round}_ending_defender_threat_row`, '_7sfs-duel-acting-character');
        }

        this.addTippyTooltip(`duel_round_${row.round}_wounds`, `<div class='_7sfs-basic-tooltip'>${_("The amount of wounds the Actor took, or will take, for this round")}</div>` );        
    },

    showApproachDeckAtTop: function() {
        const container = $('approachDeck-container');
        if (!container) return;

        const firstRect = container.getBoundingClientRect();
        dojo.place('approachDeck-container', 'city', 'before');
        dojo.removeClass('approachDeck-container', '_7sfs-dimmed');

        if (this.animationManager && this.animationManager.animationsActive()) {
            const lastRect = container.getBoundingClientRect();
            const deltaY = firstRect.top - lastRect.top;
            if (deltaY !== 0) {
                container.animate([
                    { transform: `translateY(${deltaY}px)`, opacity: 0.5 },
                    { transform: 'translateY(0)', opacity: 1 }
                ], { duration: 500, easing: 'ease-in-out' });
            }
        }
    },

    showApproachDeckAtBottom: function() {
        const container = $('approachDeck-container');
        if (!container) return;

        const firstRect = container.getBoundingClientRect();
        dojo.place('approachDeck-container', 'hand_anchor', 'before');
        dojo.addClass('approachDeck-container', '_7sfs-dimmed');

        if (this.animationManager && this.animationManager.animationsActive()) {
            const lastRect = container.getBoundingClientRect();
            const deltaY = firstRect.top - lastRect.top;
            if (deltaY !== 0) {
                container.animate([
                    { transform: `translateY(${deltaY}px)`, opacity: 1 },
                    { transform: 'translateY(0)', opacity: 0.5 }
                ], { duration: 500, easing: 'ease-in-out' });
            }
        }
    },

    getCityLocationElement: function(location) {
        return dojo.query(`[data-location="${location}"]`)[0];
    },

    highlightCharacterChosen: function(cardId) {
        const card = this.cardProperties[cardId];
        const image = $(`${card.divId}_image`);
        dojo.addClass(image, '_7sfs-chosen');
    },  

    highlightCardsAsChosen: function(ids) {
        ids.forEach((id) => {
            const card = this.cardProperties[id];
            const image = $(`${card.divId}_image`);
            dojo.addClass(image, '_7sfs-chosen');
        });
        this.clientStateArgs.ids = ids;
    },

    highlightCardsAsSelectable: function(ids) {
        ids.forEach((id) => {
            const card = this.cardProperties[id];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image);
            this.makeCardSelectable(image);
        });
        this.clientStateArgs.ids = ids;
    },

    unhighlightCharacterChosen: function(id) {
        const card = this.cardProperties[id];
        const image = $(`${card.divId}_image`);
        dojo.removeClass(image, '_7sfs-chosen');
    },

    unhighlightCards: function(ids) {
        ids.forEach((id) => {
            const card = this.cardProperties[id];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image);
        });
    },
    
    makeHomeEndcapMarkerSelectable: function() {
        var home = $(`${this.getActivePlayerId()}-home-anchor`);
        dojo.addClass(home, '_7sfs-home-endcap-marker');
        dojo.addClass(home, '_7sfs-selectable');
        dojo.style(home, 'cursor', 'pointer');
        const handle = dojo.connect($(home), 'onclick', this, 'onCityLocationClicked');
        this.connects.push(handle);                
    },

    getCardImageUrlRoot: function(cardImage) {
        return cardImage.startsWith('img/') ? g_gamethemeurl : 'https://dtdb.co/images/7s5s/en/';
    },

    crewCapCheck: function() {
        const player = this.gamedatas.players[this.player_id];
        const leader = player.leader;
        const crewCap = leader.modifiedCrewCap;

        const characters = Object.values(this.cardProperties).filter((card) => 
            (card.type === 'Character' ||card.type === 'Leader') && card.controllerId === this.player_id && this.isCardInPlay(card.id));
        return characters.length + 1 <= crewCap; // +1 for the character being recruited
    },

    basicRecruitActionCrewCapCheck: function() {
        if (!this.crewCapCheck()) 
            this.confirmationDialog(_("You will exceed your crew cap if you Recruit. Are you sure you want to continue?"),
                () => {this.bgaPerformAction('actHighDramaRecruitActionStart', {})}
            );
            else
                this.bgaPerformAction('actHighDramaRecruitActionStart', {}) 
    },

    displayForumInterveneList: function(interveneList) {
        this.removeForumInterveneList();

        if (!interveneList || interveneList.length === 0) {
            return;
        }

        const forumImage = $('forum-image');
        if (!forumImage) return;

        dojo.place(this.format_block('jstpl_forum_parley_gone_wrong_intervene_list', {}), forumImage, 'first');

        const container = $('forum-parley-intervene-list');
        interveneList.forEach((entry) => {
            const span = dojo.create('span', {
                innerHTML: entry.playerName,
                style: `color:#${entry.playerColor}`,
            }, container);
        });

        this.addTippyTooltip('forum-parley-intervene-list', `<div class='_7sfs-basic-tooltip'>${_('Parley Gone Wrong - These players are allowed to Intervene')}</div>`);
    },

    removeForumInterveneList: function() {
        const existing = $('forum-parley-intervene-list');
        if (existing) {
            dojo.destroy(existing);
        }
    },
})
});