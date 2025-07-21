define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.notifications', null, {

    setupNotifications: function()
    {
        debug( 'notifications subscriptions setup' );
        
        const notifs = [
            ['01126_2_scheme_moved', 500],
            ['actionUsed', 1],
            ['approachCardsReceived', 1000],
            ['approachCharacterPlayed', 2000],
            ['approachSchemePlayed', 2000],
            ['attachmentEquipped', 1000],
            ['attachmentUnequipped', 1000],
            ['cardAddedToCityDeck', 500],
            ['cardAddedToCityDiscardPile', 500],
            ['cardAddedToHand', 2000],
            ['cardDiscardedFromHand', 500],
            ['cardDiscardedFromPlay', 500],
            ['cardEngaged', 1000],
            ['cardEngarded', 1000],
            ['cardMoved', 1000],
            ['cardRemovedFromCityDiscardPile', 500],
            ['cardRemovedFromPlayerDiscardPile', 500],
            ['challengeIssued', 500],
            ['challengerSwapped', 500],
            ['characterDestroyed', 1000],
            ['characterHealed', 1000],
            ['characterInfluenceModified', 1000],
            ['characterIntervened', 500],
            ['characterMustered', 1000],
            ['characterRecruited', 1000],
            ['characterWounded', 1000],
            ['cityCardAddedToLocation', 1000],
            ['cityDiscardShuffled', 500],
            ['crystalEyeTargetChosen', 500],
            ['defenderSwapped', 500],
            ['drawCard', 2000],
            ['duelActorSwapped', 500],
            ['duelEnd', 500],
            ['duelStarted', 500],
            ['factionResolveCardDraw', 1000],
            ['factionResolveCardDrawPublic', 500],
            ['firstPlayer', 2000],
            ['locationClaimed', 500],
            ['maneuverUsed', 1],
            ['newDay', 1000],
            ['newDuelRound', 500],
            ['panacheModified', 1000],
            ['playerDiscardShuffled', 500],
            ['playerReknownUpdated', 500],
            ['playLeader', 1500],
            ['reactionUsed', 1],
            ['reknownAddedToLocation', 500],
            ['reknownRemovedFromLocation', 500],
            ['reknownUpdatedOnCard', 500],
            ['schemeSentToLocker', 1000],
            ['techniqueAdded', 1],
            ['techniqueRemoved', 1],
            ['techniqueUsed', 1],
            ['traitAdded', 1],
            ['traitRemoved', 1],
            ['updateRoundThreats', 500],
            ['updateRoundWithCombatStats', 500],
            ['yevgeniAdversaryChosen', 500],
        ];

        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            this.notifqueue.setSynchronous(notif[0], notif[1]);
        });

        this.notifqueue.setIgnoreNotificationCheck( 'drawCardMessage', (notif) => (notif.args.playerId == this.player_id) );
        this.notifqueue.setIgnoreNotificationCheck( 'crystalEyeTargetMessage', (notif) => (this.player_id == notif.args.targetplayerId || this.player_id == notif.args.choosingPlayerId) );
    },  

    notif_playLeader: function( notif )
    {
        debug( 'notif_playLeader' );
        debug( notif );

        const args = notif.args;

        this.createHome(
            args.player_id, 
            args.player_color, 
            args.leader
        );

        const target = this.getTargetElementForLocation(this.LOCATION_PLAYER_HOME, args.player_id);
        const cardId = this.createCardId(args.leader, this.LOCATION_PLAYER_HOME);
        this.createCard(cardId, args.leader, target);

        // Update the player panel
        dojo.addClass( `overall_player_board_${args.player_id}`, `home-${args.leader.faction.toLowerCase()}` );
        dojo.addClass( `${args.player_id}-score-seal`, `seal-score seal-${args.leader.faction.toLowerCase()}-score` );
        $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
        $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

        // Discard Pile
        dojo.style(`${args.player_id}-discard`, 'cursor', 'zoom-in');
        dojo.connect($(`${args.player_id}-discard`), 'onclick', this, 'onPlayerDiscardClicked');

        // Locker Pile
        dojo.style(`${args.player_id}-locker`, 'cursor', 'zoom-in');
        dojo.connect($(`${args.player_id}-locker`), 'onclick', this, 'onPlayerLockerClicked');

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${leader_name}</strong> as their leader"),
            {
                player_name: args.player_name,
                leader_name: args.leader.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_actionUsed: function( notif )
    {
        debug( 'notif_actionUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the action from the card
            const action = card.actions.find(action => action.id === args.actionId);
            if (action)
            {
                action.available = ! args.used;
                this.createTooltipForCard(card);
            }       
        }
    },

    notif_maneuverUsed: function( notif )
    {
        debug( 'notif_maneuverUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the maneuver from the card
            const maneuver = card.maneuvers.find(maneuver => maneuver.id === args.maneuverId);
            if (maneuver)
            {
                maneuver.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_reactionUsed: function( notif )
    {
        debug( 'notif_reactionUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the reaction from the card
            const reaction = card.reactions.find(reaction => reaction.id === args.reactionId);
            if (reaction)
            {
                reaction.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_techniqueAdded: function( notif )
    {
        debug( 'notif_techniqueAdded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the technique to the card
            card.techniques.push(args.technique);
            this.createTooltipForCard(card);
        }
    },

    notif_techniqueRemoved: function( notif )
    {
        debug( 'notif_techniqueRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the technique from the card
            card.techniques = card.techniques.filter(technique => technique.id !== args.techniqueId);
            this.createTooltipForCard(card);
        }
    },
    
    notif_techniqueUsed: function( notif )
    {
        debug( 'notif_techniqueUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the technique from the card
            const technique = card.techniques.find(technique => technique.id === args.techniqueId);
            if (technique)
            {
                technique.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_traitAdded: function( notif )
    {
        debug( 'notif_traitAdded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the trait to the card
            card.traits.push(args.trait);
        }
    },

    notif_traitRemoved: function( notif )
    {
        debug( 'notif_traitRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the trait from the card
            card.traits = card.traits.filter(trait => trait !== args.trait);
        }
    },

    notif_approachSchemePlayed: function( notif )
    {
        debug( 'notif_approachSchemePlayed' );
        debug( notif );

        const args = notif.args;

        this.createCard(`${args.player_id}-${args.scheme.id}`, args.scheme, `${args.player_id}-scheme-anchor`);

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${scheme_name}</strong> as their Scheme today"),
            {
                player_name: args.player_name,
                scheme_name: args.scheme.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_approachCharacterPlayed: function (notif) 
    {
        debug( 'notif_approachCharacterPlayed' );
        debug( notif );

        const args = notif.args;

        this.createCard(`${args.player_id}-${args.character.id}`, args.character, `${args.player_id}-home-anchor`);

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${character_name}</strong> as their Approach Character today"),
            {
                player_name: args.player_name,
                character_name: args.character.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_panacheModified: function( notif )
    {
        debug( 'notif_panacheModified' );
        debug( notif );

        const args = notif.args;
        $(`${args.playerId}-score-panache`).innerHTML = args.panache;
        $(`${args.playerId}-panache`).innerHTML = args.panache;
    },

    notif_approachCardsReceived: function( notif )
    {
        debug( 'notif_approachCardsReceived' );
        debug( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.approachDeck, card);
        });            
    },

    notif_attachmentEquipped: function( notif )
    {
        debug( 'notif_attachmentEquipped' );
        debug( notif );

        const args = notif.args;
        const attachment = args.attachment;
        const performer = this.cardProperties[args.performerId];

        //See if the card came from the hand
        if (this.factionHand.getItemById(attachment.id) != undefined)
        {
            this.factionHand.removeFromStockById(attachment.id);
        }
        else if (this.cardProperties[attachment.id] != undefined)
        {
            const oldCard = this.cardProperties[attachment.id];

            //Destroy the old card element
            dojo.destroy(oldCard.divId);
        }

        this.attachCard(performer, attachment);
        this.cardProperties[attachment.id] = attachment;

        performer.modifiedResolve = args.modifiedResolve;
        performer.modifiedCombat = args.modifiedCombat;
        performer.modifiedFinesse = args.modifiedFinesse;
        performer.modifiedInfluence = args.modifiedInfluence;

        //Create a placeholder html element in front of the performer
        const placeholderId = "equip-placeholder";
        dojo.place(`<div id="${placeholderId}"></div>`, performer.divId, 'before');

        //Destroy old character element
        dojo.destroy(performer.divId);

        //Create the new character element    
        this.createCard(performer.divId, performer, placeholderId);

        //Destroy the placeholder
        dojo.destroy(placeholderId);
    },

    notif_attachmentUnequipped: function( notif )
    {
        debug( 'notif_attachmentUnequipped' );
        debug( notif );

        const args = notif.args;
        const attachment = this.cardProperties[args.attachmentId];
        const character = this.cardProperties[args.characterId];

        attachment.attachmentIndex = null;
        attachment.attachedToId = null;
        attachment.controllerId = 0;
        
        character.attachedCards = character.attachedCards.filter(card => card.id !== attachment.id);
        character.modifiedResolve = args.modifiedResolve;
        character.modifiedCombat = args.modifiedCombat;
        character.modifiedFinesse = args.modifiedFinesse;
        character.modifiedInfluence = args.modifiedInfluence;

        //Create a placeholder html element in front of the performer
        const placeholderId = "unequip-placeholder";
        dojo.place(`<div id="${placeholderId}"></div>`, character.divId, 'before');

        //Destroy attachment element
        dojo.destroy(attachment.divId);

        //Destroy old character element
        dojo.destroy(character.divId);

        //Create the new attachment element    
        this.createCard(attachment.divId, attachment, placeholderId);

        //Create the new character element    
        this.createCard(character.divId, character, placeholderId);

        //Destroy the placeholder
        dojo.destroy(placeholderId);

    },

    notif_factionResolveCardDraw: function( notif )
    {
        debug( 'notif_factionResolveCardDraw' );
        debug( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });

        $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();
    },

    notif_factionResolveCardDrawPublic: function( notif )
    {
        debug( 'notif_factionResolveCardDrawPublic' );
        debug( notif );

        $(`${notif.args.playerId}-score-hand-count`).innerHTML = notif.args.count;
    },

    notif_cardAddedToHand: function( notif )
    {
        if (notif.args.player_id !== this.player_id) return;

        debug( 'notif_cardAddedToHand' );
        debug( notif );

        this.addCardToDeck(this.factionHand, notif.args.card);
        $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();
    },

    notif_drawCard: function( notif )
    {
        debug( 'notif_drawCard' );
        debug( notif );

        const args = notif.args;

        const card = args.card;
        this.cardProperties[card.id] = card;
        this.addCardToDeck(this.factionHand, card);
        $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();

    },

    notif_cardAddedToCityDeck: function( notif )
    {
        debug( 'notif_cardAddedToCityDeck' );
        debug( notif );

        const args = notif.args;

        let card = this.cardProperties[args.cardId];
        if (card)
        {
            card.location = this.LOCATION_CITY_DECK;

            dojo.destroy(card.divId);
            card.divId = null;
            delete this.cardProperties[args.cardId];
        }
    },

    notif_cardAddedToCityDiscardPile: function( notif )
    {
        debug( 'notif_cardAddedToCityDiscardPile' );
        debug( notif );

        const args = notif.args;

        let card = this.cardProperties[args.cardId];
        if (card)
        {
            card.location = this.LOCATION_CITY_DISCARD;

            dojo.destroy(card.divId);
            card.divId = null;
        }
        else
        {
            card = args.card;
        }

        this.gamedatas.cityDiscard.push(card);

    },

    notif_cardDiscardedFromPlay: function( notif )
    {
        debug( 'notif_cardDiscardedFromPlay' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.location = this.LOCATION_PLAYER_DISCARD;

        dojo.destroy(card.divId);
        card.divId = null;

        const player = this.gamedatas.players[args.playerId];
        player.discard.push(card);
    },

    notif_cardDiscardedFromHand: function( notif )
    {
        debug( 'notif_cardDiscardedFromHand' );
        debug( notif );

        const args = notif.args;
        let card = args.card;
        this.cardProperties[card.id] = card;

        if (notif.args.playerId == this.player_id)
        {
            this.factionHand.removeFromStockById(card.id);
            $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();
        }

        card.location = this.LOCATION_PLAYER_DISCARD;
        const player = this.gamedatas.players[args.playerId];
        player.discard.push(card);
    },

    notif_cardMoved: function( notif )
    {
        debug( 'notif_cardMoved' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        card.engaged = args.engage;
        card.location = args.toLocation;

        //Destroy the old card element
        dojo.destroy(card.divId);

        //Create the new card element
        const cardId = this.createCardId(card, args.toLocation);
        const target = this.getTargetElementForLocation(args.toLocation, card.controllerId);
        this.createCard(cardId, card, target);
    },

    notif_cardEngaged: function( notif )
    {
        debug( 'notif_cardEngaged' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        dojo.addClass(`${card.divId}_image`, 'engaged');
    },

    notif_cardEngarded: function( notif )
    {
        debug( 'notif_cardEngarded' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        dojo.removeClass(`${card.divId}_image`, 'engaged');
    },

    notif_characterMustered: function (notif) 
    {
        debug( 'notif_characterMustered' );
        debug( notif );

        const args = notif.args;
        const cardId = this.createCardId(args.character, args.location);
        const target = this.getTargetElementForLocation(args.location, args.playerId);
        this.createCard(cardId, args.character, target);
    },


    notif_characterRecruited: function( notif )
    {
        debug( 'notif_characterRecruited' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.controllerId = args.player_id;

        //Remove from this.cardProperties
        delete this.cardProperties[args.characterId];
        dojo.destroy(card.divId);

        const cardId = this.createCardId(card, card.location);
        const target = this.getTargetElementForLocation(card.location, card.controllerId);
        this.createCard(cardId, card, target);
    },

    notif_characterWounded: function( notif )
    {
        debug( 'notif_characterWounded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card.wounds == 0)
        {
            const characterImage = $(`${card.divId}_image`);
            const woundChip = `${card.divId}_wounds`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: woundChip,
                class: 'wound-chip',
            }),  characterImage, 'last');
            this.addTooltipHtml( woundChip, `<div class='basic-tooltip'>${_("Wounds")}</div>` );
        }
        
        card.wounds += args.wounds;
        card.modifiedResolve -= args.wounds;

        const woundChip = $(`${card.divId}_wounds`);
        woundChip.innerHTML = card.wounds;

        const element = $(`${card.divId}_resolve_value`);
        element.innerHTML = card.modifiedResolve;
        if (card.modifiedResolve != card.resolve)
            dojo.addClass(element, 'modified-stat-value');
    },

    notif_characterHealed: function( notif )
    {
        debug( 'notif_characterHealed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];

        card.wounds -= args.wounds;
        if (card.wounds < 0)
            card.wounds = 0;
        
        if (card.wounds == 0)
        {
            const woundChip = $(`${card.divId}_wounds`);
            dojo.destroy(woundChip);
        }

        card.modifiedResolve += args.wounds;
        if (card.modifiedResolve > card.resolve)
            card.modifiedResolve = card.resolve;

        const element = $(`${card.divId}_resolve_value`);
        element.innerHTML = card.modifiedResolve;
        if (card.modifiedResolve == card.resolve)
            dojo.removeClass(element, 'modified-stat-value');
    },

    notif_characterDestroyed: function( notif )
    {
        debug( 'notif_characterDestroyed' );
        debug( notif );

        const args = notif.args;        
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            card.location = this.LOCATION_PLAYER_LOCKER;
            card.engaged = false;

            dojo.destroy(card.divId);
            card.divId = null;
        }

        const player = this.gamedatas.players[args.playerId];
        player.locker.push(card);
    },

    notif_characterInfluenceModified: function( notif )
    {
        debug( 'notif_characterInfluenceModified' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.modifiedInfluence = args.newInfluence;

        const element = $(`${card.divId}_influence_value`);
        element.innerHTML = card.modifiedInfluence;
        if (card.modifiedInfluence != card.influence)
            dojo.addClass(element, 'modified-stat-value');
        else
            dojo.removeClass(element, 'modified-stat-value');
    },

    notif_schemeSentToLocker: function( notif )
    {
        debug( 'notif_schemeSentToLocker' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.scheme.id];
        card.location = this.LOCATION_PLAYER_LOCKER;

        dojo.destroy(card.divId);
        card.divId = null;

        const player = this.gamedatas.players[args.playerId];
        player.locker.push(card);
    },

    notif_newDay: function( notif )
    {
        debug( 'notif_newDay' );
        debug( notif );

        const args = notif.args;

        $('day-indicator').innerHTML = args.day;
        dojo.style('day-indicator', 'display', 'block');
    },

    notif_cityCardAddedToLocation: function( notif )
    {
        debug( 'notif_cityCardAddedToLocation' );
        debug( notif );

        const args = notif.args;

        const card = args.card;
        const target = this.getTargetElementForLocation(args.location, card.controllerId);
        const cardId = this.createCardId(card, args.location);
        this.createCard(cardId, card, target);
    },

    notif_playerReknownUpdated: function( notif )
    {
        debug( 'notif_playerReknownUpdated' );
        debug( notif );

        const args = notif.args;
        $(`${args.player_id}-score-reknown`).innerHTML = args.total;
    },

    notif_reknownUpdatedOnCard: function( notif )
    {
        debug( 'notif_reknownUpdatedOnCard' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        const divId = `${card.divId}-reknown`;
        ////Delete the old element if exists
        if ($(divId)) {                
            dojo.destroy(divId);
        } 

        if (args.total > 0)
        {
            dojo.place( this.format_block( 'jstpl_reknown_chip', {
                id: divId,
                amount: args.total,
            }),  card.divId, 'last');
        }
    },

    notif_reknownAddedToLocation: function( notif )
    {
        debug( 'notif_reknownAddedToLocation' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
        //Find the element with the class city-reknown-chip that is a child of the element's parent
        const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
        const reknown = parseInt(reknownElement.innerHTML) + args.amount;
        reknownElement.innerHTML = reknown;
    },

    notif_reknownRemovedFromLocation: function( notif )
    {
        debug( 'notif_reknownRemovedFromLocation' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
        //Find the element with the class city-reknown-chip that is a child of the element's parent
        const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
        const reknown = parseInt(reknownElement.innerHTML) - args.amount;
        reknownElement.innerHTML = reknown;
    },

    notif_firstPlayer: function( notif )
    {
        debug( 'notif_firstPlayer' );
        debug( notif );

        //Remove any existing first player classes
        dojo.query('.first-player-home').removeClass('first-player-home');
        dojo.query('.first-player-score').removeClass('first-player-score');

        //Add the new classes
        const args = notif.args;
        dojo.addClass(`${args.playerId}-first-player`, 'first-player-home');
        dojo.removeClass(`${args.playerId}-score-seal-first-player`, 'first-player-hidden');
        dojo.addClass(`${args.playerId}-score-seal-first-player`, 'first-player-score');

        var translated = dojo.string.substitute(
            _("${player_name} is now the First Player"),
            {
                player_name: args.player_name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_cardRemovedFromCityDiscardPile: function ( notif )
    {
        debug( 'notif_cardRemovedFromCityDiscardPile' );
        debug( notif );

        const args = notif.args;
        this.gamedatas.cityDiscard = this.gamedatas.cityDiscard.filter((c) => c.id !== args.card.id);
    },

    notif_cardRemovedFromPlayerDiscardPile: function ( notif )
    {
        debug( 'notif_cardRemovedFromPlayerDiscardPile' );
        debug( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.player_id];
        player.discard = player.discard.filter((c) => c.id !== args.card.id);
    },

    notif_yevgeniAdversaryChosen: function( notif )
    {
        debug( 'notif_yevgeniAdversaryChosen' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.ADVERSARY_OF_YEVGENI);

        const imageElement = dojo.query('.card', card.divId)[0];
        const id = `${card.divId}_yevgeni_adversary`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: 'yevgeni-adversary-chip',
        }),  imageElement, 'last');

        this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
    },

    notif_crystalEyeTargetChosen: function( notif )
    {
        debug( 'notif_crystalEyeTargetChosen' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.CRYSTAL_EYE_TARGET);

        const div = this.approachDeck.getItemDivId(args.cardId);
        const id = `${args.cardId}_crystal_eye_target`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: 'crystal-eye-target-chip',
        }),  div, 'last');

        this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Chosen Target for Crystal Eye")}</div>` );
    },

    notif_01126_2_scheme_moved: function( notif )
    {
        debug( 'notif_01126_2_scheme_moved');
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        card.location = args.location;
        this.gamedatas.homeCards = this.gamedatas.homeCards.filter((scheme) => scheme.id !== card.id);
        dojo.destroy(card.divId);

        args.card = card;
        this.notif_cityCardAddedToLocation(notif);
    },

    notif_locationClaimed: function( notif )
    {
        debug( 'notif_locationClaimed' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];

        const player = this.gamedatas.players[args.playerId];

        dojo.place( this.format_block( 'jstpl_location_control_chip', {
            id: imageElement.id,
            player_color: player.color,
        }),  imageElement, 'before');
    },
    
    notif_challengeIssued: function( notif )
    {
        debug( 'notif_challengeIssued' );
        debug( notif );

        const args = notif.args;
        const challenger = this.cardProperties[args.challengerId];
        challenger.conditions.push(this.CHALLENGER);
        const challengerImage = $(`${challenger.divId}_image`);
        const challengerChipId = `${challenger.divId}_challenger`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: challengerChipId,
            class: 'challenger-chip',
        }),  challengerImage, 'last');
        this.addTooltipHtml( challengerChipId, `<div class='basic-tooltip'>${_("Duel Challenger")}</div>` );

        const defender = this.cardProperties[args.defenderId];
        defender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${defender.divId}_image`);
        const defenderChipId = `${defender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: 'defender-chip',
        }),  defenderImage, 'last');
        this.addTooltipHtml( defenderChipId, `<div class='basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_challengerSwapped: function( notif )
    {
        debug( 'notif_challengerSwapped' );
        debug( notif );

        const args = notif.args;

        const oldChallenger = this.cardProperties[args.oldChallengerId];
        oldChallenger.conditions = oldChallenger.conditions.filter(condition => condition !== this.CHALLENGER);
        const oldChallengerChipId = `${oldChallenger.divId}_challenger`;
        dojo.destroy(oldChallengerChipId);

        const newChallenger = this.cardProperties[args.newChallengerId];
        newChallenger.conditions.push(this.CHALLENGER);
        const challengerImage = $(`${newChallenger.divId}_image`);
        const challengerChipId = `${newChallenger.divId}_challenger`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: challengerChipId,
            class: 'challenger-chip',
        }),  challengerImage, 'last');
        this.addTooltipHtml( challengerChipId, `<div class='basic-tooltip'>${_("Duel Challenger")}</div>` );
    },

    notif_defenderSwapped: function( notif )
    {
        debug( 'notif_defenderSwapped' );
        debug( notif );

        const args = notif.args;

        const oldDefender = this.cardProperties[args.oldDefenderId];
        oldDefender.conditions = oldDefender.conditions.filter(condition => condition !== this.DEFENDER);
        const oldDefenderChipId = `${oldDefender.divId}_defender`;
        dojo.destroy(oldDefenderChipId);

        const newDefender = this.cardProperties[args.newDefenderId];
        newDefender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newDefender.divId}_image`);
        const defenderChipId = `${newDefender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: 'defender-chip',
        }),  defenderImage, 'last');
        this.addTooltipHtml( defenderChipId, `<div class='basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_characterIntervened: function( notif )
    {
        debug( 'notif_characterIntervened' );
        debug( notif );

        const args = notif.args;

        const oldTarget = this.cardProperties[args.oldTargetId];
        oldTarget.conditions = oldTarget.conditions.filter(condition => condition !== this.DEFENDER);
        const oldDefenderChipId = `${oldTarget.divId}_defender`;
        dojo.destroy(oldDefenderChipId);

        const newTarget = this.cardProperties[args.newTargetId];
        newTarget.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newTarget.divId}_image`);
        const defenderChipId = `${newTarget.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: 'defender-chip',
        }),  defenderImage, 'last');
        this.addTooltipHtml( defenderChipId, `<div class='basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_duelStarted: function( notif )
    {
        debug( 'notif_duelStarted' );
        debug( notif );

        const args = notif.args;

        this.inDuel = true;
        this.duelRound = 0;
        this.displayDuelTable();
        
        if (this.player_id == args.challengingPlayerId || this.player_id == args.defendingPlayerId)
        {
            dojo.place('factionHand-container', 'duel', 'before');
        }
    },

    notif_newDuelRound: function( notif )
    {
        debug( 'notif_newDuelRound' );
        debug( notif );

        const args = notif.args;
        this.duelRound = args.round;
        this.displayDuelRow(args);

    },

    notif_duelActorSwapped: function( notif )
    {
        debug( 'notif_duelActorSwapped' );
        debug( notif );

        const args = notif.args;
        let divId = `duel_round_${args.round}_actor`
        dojo.empty(divId);
        this.createCard(`duel_${args.round}_${args.actor.id}`, args.actor, divId, true);

        if (dojo.hasClass(`duel_round_${args.round}_starting_challenger_threat_row`, 'duel-acting-character'))
        {
            divId = `duel_round_${args.round}_challenger_name`;
            $(divId).innerHTML = args.actor.name;
        }
        else
        {
            divId = `duel_round_${args.round}_defender_name`;
            $(divId).innerHTML = args.actor.name;
        }
    },

    notif_updateRoundWithCombatStats: function( notif )
    {
        debug( 'notif_updateRoundWithCombatStats' );
        debug( notif );

        const args = notif.args;

        if (args.mode == 'combat')
        {
            const combatCard = args.combatCard;
            const divId = `duel_round_${args.round}_combat`;
            if ($(divId).innerHTML == 'Not Chosen')
            {
                $(divId).innerHTML = '';
            }

            dojo.place( this.format_block('jstpl_row_combat_card', { 
                round: args.round,
                id: combatCard.id,
                image: g_gamethemeurl + combatCard.image 
            }),  divId, 'last');

            const cardDivId = `duel_round_${args.round}_combat_card_${combatCard.id}`;
            this.addTooltipHtml(cardDivId, `<img src="${g_gamethemeurl + combatCard.image}" />`, this.CARD_TOOLTIP_DELAY);
    
            if (args.gambled)
            {
                dojo.addClass(cardDivId, 'engaged');
                dojo.addClass(cardDivId, 'duel-row-combat-card-gambled');
            }
            else if (this.player_id == combatCard.controllerId)
            {
                this.factionHand.removeFromStockById(combatCard.id);
                $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();
            }

        }
        else
        {
            var element = $(`duel_round_${args.round}_${args.mode}`);
            if (element.innerHTML == 'Not Chosen')
            {
                element.innerHTML = '';
            }
            element.innerHTML += args.card_name + ': ' + args.effect_name;
        }

        let riposte = parseInt($(`duel_round_${args.round}_${args.mode}_riposte`).innerHTML) + args.riposte;
        let parry = parseInt($(`duel_round_${args.round}_${args.mode}_parry`).innerHTML) + args.parry;
        let thrust = parseInt($(`duel_round_${args.round}_${args.mode}_thrust`).innerHTML) + args.thrust;
        $(`duel_round_${args.round}_${args.mode}_riposte`).innerHTML = riposte;
        $(`duel_round_${args.round}_${args.mode}_parry`).innerHTML = parry;
        $(`duel_round_${args.round}_${args.mode}_thrust`).innerHTML = thrust;
        $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML = args.endingChallengerThreatAfter;
        $(`duel_round_${args.round}_ending_defender_threat`).innerHTML = args.endingDefenderThreatAfter;
        $(`duel_round_${args.round}_wounds`).innerHTML = args.wounds;

        if (args.endingChallengerThreatAfter > 0)
            dojo.addClass(`duel_round_${args.round}_ending_challenger_threat`, 'threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_challenger_threat`, 'threat-chip-threatened');

        if (args.endingDefenderThreatAfter > 0)
            dojo.addClass(`duel_round_${args.round}_ending_defender_threat`, 'threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_defender_threat`, 'threat-chip-threatened');

        dojo.removeClass(`duel_round_${args.round}_${args.mode}`, 'ability-not-chosen');
        dojo.removeClass(`duel_round_${args.round}_${args.mode}_stats`, 'ability-not-chosen');
    },

    notif_updateRoundThreats: function( notif )
    {
        debug( 'notif_updateRoundThreats' );
        debug( notif );

        const args = notif.args;

        $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML = args.challenger_threat;
        if (args.challenger_threat > 0)
            dojo.addClass(`duel_round_${args.round}_ending_challenger_threat`, 'threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_challenger_threat`, 'threat-chip-threatened');

        $(`duel_round_${args.round}_ending_defender_threat`).innerHTML = args.defender_threat;
        if (args.defender_threat > 0)
            dojo.addClass(`duel_round_${args.round}_ending_defender_threat`, 'threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_defender_threat`, 'threat-chip-threatened');

        $(`duel_round_${args.round}_wounds`).innerHTML = args.wounds;
    },

    notif_duelEnd: function( notif )
    {
        debug( 'notif_duelEnd' );
        debug( notif );

        const args = notif.args;

        this.inDuel = false;
        dojo.destroy('duel');

        const challenger = this.cardProperties[args.challengerId];
        if (challenger)
        {
            challenger.conditions = challenger.conditions.filter(condition => condition !== this.CHALLENGER);
            const challengerChipId = `${challenger.divId}_challenger`;
            dojo.destroy(challengerChipId);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
        }

        if (this.player_id == args.challengingPlayerId || this.player_id == args.defendingPlayerId)
        {
            this.showHandAtBottom();
        }
    },

    notif_cityDiscardShuffled: function( notif )
    {
        debug( 'notif_cityDiscardShuffled' );
        debug( notif );

        //Clear the city discard pile
        this.gamedatas.cityDiscard = [];
    },

    notif_playerDiscardShuffled: function( notif )
    {
        debug( 'notif_playerDiscardShuffled' );
        debug( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.playerId];
        player.discard = [];
    },

})
});