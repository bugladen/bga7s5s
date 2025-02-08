define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.notifications', null, {

    setupNotifications: function()
    {
        debug( 'notifications subscriptions setup' );
        
        // TODO: here, associate your game notifications with local methods
        const notifs = [
            ['playLeader', 1500],
            ['approachCardsReceived', 1000],
            ['approachCharacterPlayed', 2000],
            ['approachSchemePlayed', 2000],
            ['attachmentEquipped', 1000],
            ['newDay', 1000],
            ['cityCardAddedToLocation', 1000],
            ['cardAddedToCityDiscardPile', 500],
            ['cardAddedToHand', 2000],
            ['cardDiscardedFromHand', 500],
            ['cardEngaged', 1000],
            ['cardMoved', 1000],
            ['characterRecruited', 1000],
            ['characterWounded', 1000],
            ['drawCard', 2000],
            ['firstPlayer', 1500],
            ['locationClaimed', 500],
            ['panacheModified', 1000],
            ['playerReknownUpdated', 500],
            ['reknownUpdatedOnCard', 500],
            ['reknownAddedToLocation', 500],
            ['reknownRemovedFromLocation', 500],
            ['factionResolveCardDraw', 1000],
            ['cardRemovedFromCityDiscardPile', 500],
            ['cardRemovedFromPlayerDiscardPile', 500],
            ['yevgeniAdversaryChosen', 500],
            ['01126_2_scheme_moved', 500],
            ['challengeIssued', 500],
            ['duelStarted', 500],
            ['duelRound', 500],
        ];

        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            this.notifqueue.setSynchronous(notif[0], notif[1]);
        });

        this.notifqueue.setIgnoreNotificationCheck( 'drawCardMessage', (notif) => (notif.args.playerId == this.player_id) );
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

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`);
    },

    notif_approachSchemePlayed: function( notif )
    {
        debug( 'notif_approachSchemePlayed' );
        debug( notif );

        const args = notif.args;

        this.createCard(`${args.player_id}-${args.scheme.id}`, args.scheme, `${args.player_id}-scheme-anchor`);

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.scheme.name}</span> as their Scheme today`);
    },

    notif_approachCharacterPlayed: function (notif) {
        debug( 'notif_approachCharacterPlayed' );
        debug( notif );


        const args = notif.args;

        this.createCard(`${args.player_id}-${args.character.id}`, args.character, `${args.player_id}-home-anchor`);

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.character.name}</span> as their Approach Character today`);
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
        if (this.factionHand.getItemById(attachment.id) !== undefined)
        {
            this.factionHand.removeFromStockById(attachment.id);
        }
        else if (this.cardProperties[attachment.id] !== undefined)
        {
            const oldCard = this.cardProperties[attachment.id];

            //Destroy the old card element
            dojo.destroy(oldCard.divId);
        }

        this.attachCard(performer, attachment);

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

    notif_factionResolveCardDraw: function( notif )
    {
        debug( 'notif_factionResolveCardDraw' );
        debug( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });            
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

    notif_cardAddedToCityDiscardPile: function( notif )
    {
        debug( 'notif_cardAddedToCityDiscardPile' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        card.location = this.LOCATION_CITY_DISCARD;

        dojo.destroy(card.divId);
        card.divId = null;

        this.gamedatas.cityDiscard.push(card);
    },

    notif_cardDiscardedFromHand: function( notif )
    {
        debug( 'notif_cardDiscardedFromHand' );
        debug( notif );

        const args = notif.args;
        let card = null;

        if (notif.args.playerId == this.player_id)
        {
            card = this.cardProperties[args.card.id];
            this.factionHand.removeFromStockById(card.id);
            $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.count();
        }
        else
        {
            card = args.card;
            this.cardProperties[card.id] = card;
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
        noWounds = args.wounds == 0;
        if (noWounds == 0)
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
        $(`${args.player_id}-score-reknown`).innerHTML = args.amount;
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

        dojo.place( this.format_block( 'jstpl_reknown_chip', {
            id: divId,
            amount: args.amount,
        }),  card.divId, 'last');
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

        $('pagemaintitletext').innerHTML = _(`${args.player_name} is now the First Player`);
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

    notif_duelStarted: function( notif )
    {
        debug( 'notif_duelStarted' );
        debug( notif );

        const args = notif.args;

        this.inDuel = true;
        this.displayDuelTable();
        
        if (this.player_id == args.challengingPlayerId || this.player_id == args.defendingPlayerId)
        {
            dojo.place('factionHand-container', 'duel', 'before');
        }
    },

    notif_duelRound: function( notif )
    {
        debug( 'notif_duelRound' );
        debug( notif );

        const args = notif.args;
        this.displayDuelRow(args.round, args.challengerId, args.defenderId, args.actorId, args.challengerName, args.challengerThreat, args.defenderName, args.defenderThreat);

    }
})
});