define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.utilities', null, {

    addCardToDeck: function( deck, card )
    {
        this.cardProperties[card.id] = card;

        //Different weight depending on the type. Scheme cards go first
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

        this.addTooltipHtml( `${playerId}-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Capacity')}</div>` );
        this.addTooltipHtml( `${playerId}-discard`, `<div class='basic-tooltip'>${_('Faction Deck Discard Pile')}</div>` );
        this.addTooltipHtml( `${playerId}-locker`, `<div class='basic-tooltip'>${_('Player Locker')}</div>` );
        this.addTooltipHtml( `${playerId}-panache`, `<div class='basic-tooltip'>${_('Current Panache')}</div>` );
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
                dojo.removeClass(`${divId}-player-color`, 'character-player-color');
            }
        }
        else if (card.type === 'Event')
        {
            this.createEventCard(divId, card, targetDiv);
        }
        else if (card.type === 'Attachment') {
            if (card.controllerId !== 0) {
                const playerInfo = this.gamedatas.players[card.controllerId];
                this.createAttachmentCard(divId, card, targetDiv);
            }
            else {
                this.createAttachmentCard(divId, card, targetDiv);
            }
        }
        else if (card.type === 'Scheme') {
            this.createSchemeCard(divId, card, targetDiv);
        }
    },

    createCharacterCard: function( divId, color, character, targetDiv, inDuel = false )
    {
        //Set the divId of the card
        character.divId = divId;

        //Add to the card properties cache
        this.cardProperties[character.id] = character;

        const wealthCost = character.wealthCost ? character.wealthCost : '';
        const influence = character.modifiedInfluence >= 0 ? character.modifiedInfluence  : '-';

        const placement = inDuel ? 'first' : 'before';

        dojo.place( this.format_block( 'jstpl_character', {
            id: divId,
            attachmentCount: character.attachedCards?.length ?? 0,
            faction: character.faction.toLowerCase(),
            image: character.image,
            player_color: color,
            resolve: character.modifiedResolve,
            combat: character.modifiedCombat,
            finesse: character.modifiedFinesse,
            influence: influence,
            cost: wealthCost,
        }), targetDiv, placement );

        if (character.combat != character.modifiedCombat) 
            dojo.addClass(`${divId}_combat_value`, 'modified-stat-value');

        if (character.finesse != character.modifiedFinesse) 
            dojo.addClass(`${divId}_finesse_value`, 'modified-stat-value');

        if (character.resolve != character.modifiedResolve || character.wounds > 0)
            dojo.addClass(`${divId}_resolve_value`, 'modified-stat-value');

        if (character.influence != character.modifiedInfluence)
            dojo.addClass(`${divId}_influence_value`, 'modified-stat-value');

        if (!character.wealthCost || character.controllerId) {
            dojo.style( `${divId}_wealth_cost`, 'display', 'none' );
        }

        this.addTooltipHtml(`${divId}_image`, `<img src="${g_gamethemeurl + character.image}" />`, this.CARD_TOOLTIP_DELAY);

        //Check for any special conditions where a token has to be displayed
        if (character.conditions.includes(this.ADVERSARY_OF_YEVGENI)) {
            //Get the first child of element divId
            const id = `${divId}_yevgeni_adversary`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: 'yevgeni-adversary-chip',
            }),  `${divId}_image`, 'last');
            this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
        }
        if (character.conditions.includes(this.CHALLENGER)) {
            id = `${divId}_challenger`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: 'challenger-chip',
            }),  `${divId}_image`, 'last');
            this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Duel Challenger")}</div>` );
        }
        if (character.conditions.includes(this.DEFENDER)) {
            id = `${divId}_defender`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: 'defender-chip',
            }),  `${divId}_image`, 'last');
            this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Duel Defender")}</div>` );
        }
        if (character.wounds > 0)
        {
            const woundChip = `${divId}_wounds`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: woundChip,
                class: 'wound-chip',
            }),  `${divId}_image`, 'last');
            $(woundChip).innerHTML = character.wounds;
            this.addTooltipHtml( woundChip, `<div class='basic-tooltip'>${_("Wounds")}</div>` );
        }

        if (character.engaged && !inDuel) 
            dojo.addClass(`${divId}_image`, 'engaged');

        if (inDuel)
            dojo.addClass(`${divId}_image`, 'duel-character');

        //Display the attachments in front of the character, offset
        character.attachedCards?.forEach((attachment) => {
            const divId = this.createCardId(attachment, attachment.location);
            this.createAttachmentCard(divId, attachment, character.divId);
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
            dojo.addClass(divId, 'scheme-container-in-city');
            const img = $(`${divId}-image`);
            dojo.addClass(img, 'scheme-in-city');
        }
        else {
            dojo.removeClass(`${divId}-player-color`, 'scheme-player-color');
        }
        
        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + scheme.image}" />`, this.CARD_TOOLTIP_DELAY);
    },  

    createAttachmentCard: function( divId, attachment, targetDiv )
    {
        //Set the divId of the card
        attachment.divId = divId;

        //Add to the card properties cache
        this.cardProperties[attachment.id] = attachment;

        //Get the attached character and set up as a container
        if (attachment.attachedToId) {
            const character = this.cardProperties[attachment.attachedToId];
            dojo.addClass(character.divId, 'attachment-container');
        }

        let placement = attachment.attachedToId ? 'last' : 'before';
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
            dojo.addClass(divId, 'attached-card');
            dojo.addClass(`${divId}_wealth_cost`, 'hidden');
        } 

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + attachment.image}" />`, this.CARD_TOOLTIP_DELAY);
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
            case this.IN_DUEL:
                return `duel-${card.id}`;
            case this.LOCATION_PLAYER_HOME:
                return `${card.controllerId}-${card.id}`;
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
        dojo.addClass(location, 'selectable');
        dojo.style(location, 'cursor', 'pointer');
        const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
        this.connects.push(handle);
    },
    
    makeCardSelectable: function(image) {
        dojo.addClass(image, 'selectable');
        dojo.style(image, 'cursor', 'pointer');
        const handle = dojo.connect(image, 'onclick', this, 'onCardInPlayClicked');
        this.connects.push(handle);                        
    },   
    
    resetCityLocations: function() {
        const locations = this.getListofAvailableCityLocationImages();
        locations.forEach((location) => {
            dojo.removeClass(location, 'selectable');
            dojo.removeClass(location, 'selected');
            dojo.style(location, 'cursor', 'default');
        });
    },
    
    clearCardAsSelectable: function(image) {
        dojo.removeClass(image, 'selectable');
        dojo.removeClass(image, 'selected');
        dojo.removeClass(image, 'chosen');
        dojo.style(image, 'cursor', 'default');
    },

    attachCard: function( equipped, attachment) {
        if (!equipped.attachedCards) {
            equipped.attachedCards = [];
        }
        equipped.attachedCards.push(attachment);
        attachment.attachmentIndex = equipped.attachedCards.length;
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

    displayLocationControlChip: function( location ) {
        const controllerId = this.gamedatas.locationControllers[location];
        if (controllerId != 0) 
        {
            const player = this.gamedatas.players[controllerId];
            const imageElement = dojo.query(`[data-location="${location}"]`)[0];
            dojo.place( this.format_block( 'jstpl_location_control_chip', {
                id: imageElement.id,
                player_color: player.color,
            }),  imageElement, 'before');
        }
    },

    displayDuelTable: function() {
        const city = $('city');
        const id = 'duelTable';
        dojo.place( this.format_block( 'jstpl_duel_table', {
        }),  city, 'before');
    },

    displayDuelRow: function(row)
    {
        const headerRow = $('duel_header_row');
        
        
        dojo.place( this.format_block( 'jstpl_duel_round', {
            round: row.round,
            challengerName: row.challengerName,
            startingChallengerThreat: row.startingChallengerThreat,
            defenderName: row.defenderName,
            startingDefenderThreat: row.startingDefenderThreat,
            combatRiposte: row.combatRiposte ?? 0,
            combatParry: row.combatParry ?? 0,
            combatThrust: row.combatThrust ?? 0,
            technique: row.techniqueName ?? 'Not Chosen',
            techniqueRiposte: row.techniqueRiposte ?? 0,
            techniqueParry: row.techniqueParry ?? 0,
            techniqueThrust: row.techniqueThrust ?? 0,
            maneuver: row.maneuver ?? 'Not Chosen',
            maneuverRiposte: row.maneuverRiposte ?? 0,
            maneuverParry: row.maneuverParry ?? 0,
            maneuverThrust: row.maneuverThrust ?? 0,
            endingChallengerThreat: row.endingChallengerThreat,
            endingDefenderThreat: row.endingDefenderThreat,
        }),  headerRow, 'after');

        const combatCard = row.combatCardId ? this.gamedatas.players[row.playerId].discard.find((card) => card.id == row.combatCardId) : null;
        if (combatCard)
        {
            const divId = `duel_round_${row.round}_combat`;
            $(divId).innerHTML = this.format_block('jstpl_row_combat_card', { 
                round: row.round,
                id: combatCard.id,
                image: g_gamethemeurl + combatCard.image 
            });
            this.addTooltipHtml(divId, `<img src="${g_gamethemeurl + combatCard.image}" />`, this.CARD_TOOLTIP_DELAY);
        }


        if (!row.combatCardId)
        {
            dojo.addClass(`duel_round_${row.round}_combat`, 'ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_combat_stats`, 'ability-not-chosen');
        }
        if (!row.techniqueName)
        {
            dojo.addClass(`duel_round_${row.round}_technique`, 'ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_technique_stats`, 'ability-not-chosen');
        }
        if (!row.maneuver)
        {
            dojo.addClass(`duel_round_${row.round}_maneuver`, 'ability-not-chosen');
            dojo.addClass(`duel_round_${row.round}_maneuver_stats`, 'ability-not-chosen');
        }

        if (row.startingChallengerThreat > 0)
            dojo.addClass(`duel_round_${row.round}_starting_challenger_threat`, 'threat-chip-threatened');
        if (row.startingDefenderThreat > 0)
            dojo.addClass(`duel_round_${row.round}_starting_defender_threat`, 'threat-chip-threatened');
        if (row.endingChallengerThreat > 0)
            dojo.addClass(`duel_round_${row.round}_ending_challenger_threat`, 'threat-chip-threatened');
        if (row.endingDefenderThreat > 0)
            dojo.addClass(`duel_round_${row.round}_ending_defender_threat`, 'threat-chip-threatened');

        if (row.actorId === row.challengerId)
        {
            dojo.addClass(`duel_round_${row.round}_starting_challenger_threat_row`, 'duel-acting-character');
            dojo.addClass(`duel_round_${row.round}_ending_challenger_threat_row`, 'duel-acting-character');
            
        }
        if (row.actorId == row.defenderId)
        {
            dojo.addClass(`duel_round_${row.round}_starting_defender_threat_row`, 'duel-acting-character');
            dojo.addClass(`duel_round_${row.round}_ending_defender_threat_row`, 'duel-acting-character');
        }

        const card = this.cardProperties[row.actorId];
        const divId = this.createCardId(card, this.LOCATION_DUEL);
        this.createCard(divId, card, `duel_round_${row.round}_actor`, true);
    },

})
});