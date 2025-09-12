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
                var deckPickerButtons = document.querySelectorAll('.deck-picker-button');
                deckPickerButtons.forEach((button) => {
                    button.disabled = true;
                });
                
                const id = tab.getAttribute('id');
                this.onStarterDeckSelected(id);
            }
        });
    },

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
        playerId == this.player_id ? 'city' : 'home_anchor', 
        playerId == this.player_id ? 'after' : 'before' );

        this.addTooltipHtml( `${playerId}-crewcap`, `<div class='_7sfs-basic-tooltip'>${_('Current Crew Capacity')}</div>` );
        this.addTooltipHtml( `${playerId}-discard`, `<div class='_7sfs-basic-tooltip'>${_('Faction Deck Discard Pile')}</div>` );
        this.addTooltipHtml( `${playerId}-locker`, `<div class='_7sfs-basic-tooltip'>${_('Player Locker')}</div>` );
        this.addTooltipHtml( `${playerId}-panache`, `<div class='_7sfs-basic-tooltip'>${_('Current Panache')}</div>` );
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
        if (card.type === 'Character')
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
        if (!card.controllerId) {
            this.addTooltipHtml(`${card.divId}_image`, `<img src="${g_gamethemeurl + card.image}" />`, this.CARD_TOOLTIP_DELAY);
            return;
        }

        const traits = card.traits?.join(', ') ?? '';
        const html = `
        <div style="position:relative;">
            <img src="${g_gamethemeurl + card.image}" />
            <div class="_7sfs-card-info">
                <div style="background-color:white; color:black">Traits: ${traits}</div>
                ${card.actions?.map((action) => `<div style="background-color:${action.available ? 'green' : 'red'};">${_('Action:')} ${_(action.shortName)}</div>`).join('') ?? ''}
                ${card.reactions?.map((reaction) => `<div style="background-color:${reaction.available ? 'green' : 'red'};">${_('Reaction:')} ${_(reaction.shortName)}</div>`).join('') ?? ''}
                ${card.maneuvers?.map((maneuver) => `<div style="background-color:${maneuver.available ? 'green' : 'red'};">${_('Maneuver:')} ${_(maneuver.shortName)}</div>`).join('') ?? ''}
                ${card.techniques?.map((technique) => `<div style="background-color:${technique.available ? 'green' : 'red'};">${_('Technique:')} ${_(technique.shortName)}</div>`).join('') ?? ''}
            </div>
        </div>
        `;
        this.addTooltipHtml(`${card.divId}_image`, html, this.CARD_TOOLTIP_DELAY);
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
            image: character.image,
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
            this.addTooltipHtml( id, `<div class='_7sfs-basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
        }
        if (character.conditions.includes(this.CHALLENGER)) {
            id = `${divId}_challenger`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-challenger-chip',
            }),  `${divId}_image`, 'last');
            this.addTooltipHtml( id, `<div class='_7sfs-basic-tooltip'>${_("Duel Challenger")}</div>` );
        }
        if (character.conditions.includes(this.DEFENDER)) {
            id = `${divId}_defender`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-defender-chip',
            }),  `${divId}_image`, 'last');
            this.addTooltipHtml( id, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
        }
        if (character.wounds > 0)
        {
            const woundChip = `${divId}_wounds`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: woundChip,
                class: '_7sfs-wound-chip',
            }),  `${divId}_image`, 'last');
            $(woundChip).innerHTML = character.wounds;
            this.addTooltipHtml( woundChip, `<div class='_7sfs-basic-tooltip'>${_("Wounds")}</div>` );
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

    },  

    createEventCard: function( divId, event, targetDiv )
    {
        //Set the divId of the card
        event.divId = divId;

        //Add to the card properties cache
        this.cardProperties[event.id] = event;

        dojo.place( this.format_block( 'jstpl_card_event', {
            id: divId,
            image: event.image,
        }), targetDiv, "before" );

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + event.image}" />`, this.CARD_TOOLTIP_DELAY);

        if (event.reknown > 0) {
            divId = `${divId}-reknown`;
            dojo.place( this.format_block( 'jstpl_reknown_chip', {
                id: divId,
                amount: event.reknown,
            }),  event.divId, 'last');
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
            image: scheme.image,
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
            image: attachment.image,
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
        //Add tooltip to card
        this.addTooltipHtml( cardDiv.id, `<img src="${g_gamethemeurl + card.image}" />`, this.STOCK_CARD_TOOLTIP_DELAY);
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
            dojo.style(location, 'cursor', 'default');
        });

        const playerHome = this.getCityLocationElement(this.LOCATION_PLAYER_HOME);
        dojo.removeClass(playerHome, '_7sfs-selectable');
        dojo.removeClass(playerHome, '_7sfs-selected');
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
            this.attachCard(equipped, attachment);
        });
        return list;
    },

    orderCardsInLocation: function(list) {
        //Move all the leaders to the beginning of the list
        const leaders = list.filter((card) => card.traits.includes('Leader'));
        list = leaders.concat(list.filter((card) => !card.traits.includes('Leader')));

        //Move all characters that are not controlled to the beginning of the list
        const characters = list.filter((card) => card.type === 'Character' && card.controllerId == 0);
        list = characters.concat(list.filter((card) => card.type !== 'Character' || card.controllerId != 0));

        //Move all the attachments to the beginning of the list
        const attachments = list.filter((card) => card.type === 'Attachment' && card.attachedToId == 0);
        list = attachments.concat(list.filter((card) => card.type !== 'Attachment'));

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
            const divId = `duel_round_${row.round}_combat`;
            $(divId).innerHTML = '';
            combatCards.forEach((combatCard) => {                
                dojo.place( this.format_block('jstpl_row_combat_card', { 
                    round: row.round,
                    id: combatCard.id,
                    image: g_gamethemeurl + combatCard.image 
                }),  divId, 'last');

                const cardDivId = `duel_round_${row.round}_combat_card_${combatCard.id}`;
                this.addTooltipHtml(cardDivId, `<img src="${g_gamethemeurl + combatCard.image}" />`, this.CARD_TOOLTIP_DELAY);
                if (row.gambled)
                {
                    dojo.addClass(divId, '_7sfs-engaged');
                    dojo.addClass(divId, '_7sfs-duel-row-combat-card-gambled');
                }
            });
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

        this.addTooltipHtml(`duel_round_${row.round}_wounds`, `<div class='_7sfs-basic-tooltip'>${_("The amount of wounds the Actor took, or will take, for this round")}</div>` );        
    },

    showApproachDeckAtTop: () => {
        dojo.place('approachDeck-container', 'factionHand-container', 'before');
    },

    showApproachDeckAtBottom: () => {
        dojo.place('approachDeck-container', 'hand_anchor', 'before');
    },

    getCityLocationElement: function(location) {
        return dojo.query(`[data-location="${location}"]`)[0];
    },
})
});