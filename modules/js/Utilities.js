define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.utilities', null, {

    addCardToDeck: function( deck, card )
    {
        this.cardProperties[card.id] = card;

        //Different weight depending on the type. Scheme cards go first
        const weight = card.type === "Scheme" || card.type === 'Attachment'? 1 : 2;

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
    
    createCard: function( divId, card, targetDiv )
    {
        if (card.type === 'Character')
        {
            if (card.controllerId !== 0) {
                const playerInfo = this.gamedatas.players[card.controllerId];
                this.createCharacterCard(divId, playerInfo.color, card, targetDiv);
                dojo.style( `${divId}-wealth-cost`, 'display', 'none' );
            }
            else {
                this.createCharacterCard(divId, '', card, targetDiv);
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
                this.createAttachmentCard(divId, playerInfo.color, card, targetDiv);
            }
            else {
                this.createAttachmentCard(divId, '', card, targetDiv);
            }
        }
        else if (card.type === 'Scheme') {
            this.createSchemeCard(divId, card, targetDiv);
        }
    },

    createCharacterCard: function( divId, color, character, location )
    {
        //Set the divId of the card
        character.divId = divId;

        //Add to the card properties cache
        this.cardProperties[character.id] = character;

        const wealthCost = character.wealthCost ? character.wealthCost : '';
        const influence = character.influence >= 0 ? character.influence  : '-';

        dojo.place( this.format_block( 'jstpl_character', {
            id: divId,
            faction: character.faction.toLowerCase(),
            image: character.image,
            player_color: color,
            resolve: character.resolve,
            combat: character.combat,
            finesse: character.finesse,
            influence: influence,
            cost: wealthCost,
        }), location, "before" );

        if (!character.wealthCost) {
            dojo.style( `${divId}-wealth-cost`, 'display', 'none' );
        }

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + character.image}" />`, 100);

        //Check for any special conditions where a token has to be displayed
        if (character.conditions.includes('Adversary of Yevgeni')) {
            //Get the first child of element divId
            const child = $(divId).firstElementChild;
            const id = `${divId}-yevgeni-adversary`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: 'yevgeni-adversary-chip',
            }),  child, 'last');

            this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
        }
    },  

    createEventCard: function( divId, event, location )
    {
        //Set the divId of the card
        event.divId = divId;

        //Add to the card properties cache
        this.cardProperties[event.id] = event;

        dojo.place( this.format_block( 'jstpl_card_event', {
            id: divId,
            image: event.image,
        }), location, "before" );

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + event.image}" />`, 100);

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

        dojo.place( this.format_block( 'jstpl_card_scheme', {
            id: divId,
            image: scheme.image,
            initiative: scheme.initiative,
            panache: this.formatModifer(scheme.panacheModifier),
        }), location, "after" );

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + scheme.image}" />`, 100);
    },  

    createAttachmentCard: function( divId, color, attachment, location )
    {
        //Set the divId of the card
        attachment.divId = divId;

        //Add to the card properties cache
        this.cardProperties[attachment.id] = attachment;

        dojo.place( this.format_block( 'jstpl_card_attachment', {
            id: divId,
            faction: attachment.faction.toLowerCase(),
            image: attachment.image,
            player_color: color,
            resolve: this.attachmentFormatModifer(attachment.resolveModifier),
            combat: this.attachmentFormatModifer(attachment.combatModifier),
            finesse: this.attachmentFormatModifer(attachment.finesseModifier),
            influence: this.attachmentFormatModifer(attachment.influenceModifier),
            cost: attachment.wealthCost,
        }), location, "before" );

        this.addTooltipHtml( divId, `<img src="${g_gamethemeurl + attachment.image}" />`, 100);
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
        this.addTooltipHtml( cardDiv.id, `<img src="${g_gamethemeurl + card.image}" />`, 100);
    },

})
});