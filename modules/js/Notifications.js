define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.notifications', null, {

    setupNotifications: function()
    {
        console.log( 'notifications subscriptions setup' );
        
        // TODO: here, associate your game notifications with local methods
        const notifs = [
            ['playLeader', 1500],
            ['approachCardsReceived', 1000],
            ['newDay', 1000],
            ['cityCardAddedToLocation', 500],
            ['playApproachScheme', 2000],
            ['playApproachCharacter', 2000],
            ['firstPlayer', 1500],
            ['panacheModified', 1000],
            ['playerReknownUpdated', 500],
            ['reknownUpdatedOnCard', 500],
            ['reknownAddedToLocation', 500],
            ['reknownRemovedFromLocation', 500],
            ['factionResolveCardDraw', 1000],
            ['cardAddedToHand', 1000],
            ['cardRemovedFromCityDiscardPile', 500],
            ['cardRemovedFromPlayerDiscardPile', 500],
            ['yevgeni_adversary_chosen', 500],
        ];

        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            this.notifqueue.setSynchronous(notif[0], notif[1]);
        });
    },  

    notif_playLeader: function( notif )
    {
        console.log( 'notif_playLeader' );
        console.log( notif );

        const args = notif.args;

        this.createHome(
            args.player_id, 
            args.player_color, 
            args.leader
        );

        this.createCard(`${args.player_id}-${args.leader.id}`, args.leader, `${args.player_id}-home-anchor`);

        // Update the player panel
        dojo.addClass( `overall_player_board_${args.player_id}`, `home-${args.leader.faction.toLowerCase()}` );
        dojo.addClass( `${args.player_id}-score-seal`, `seal-score seal-${args.leader.faction.toLowerCase()}-score` );
        $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
        $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.leader.name}</span> as their leader`);
    },

    notif_playApproachScheme: function( notif )
    {
        console.log( 'notif_playApproachScheme' );
        console.log( notif );

        const args = notif.args;

        this.createCard(`${args.player_id}-${args.scheme.id}`, args.scheme, `${args.player_id}-scheme-anchor`);

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.scheme.name}</span> as their Scheme today`);
    },

    notif_playApproachCharacter: function (notif) {
        console.log( 'notif_playApproachCharacter' );
        console.log( notif );


        const args = notif.args;

        this.createCard(`${args.player_id}-${args.character.id}`, args.character, `${args.player_id}-home-anchor`);

        $('pagemaintitletext').innerHTML = _(`${args.player_name} has selected <span style="font-weight:bold">${args.character.name}</span> as their Approach Character today`);
    },

    notif_panacheModified: function( notif )
    {
        console.log( 'notif_panacheModified' );
        console.log( notif );

        const args = notif.args;
        $(`${args.playerId}-score-panache`).innerHTML = args.panache;
        $(`${args.playerId}-panache`).innerHTML = args.panache;
    },

    notif_approachCardsReceived: function( notif )
    {
        console.log( 'notif_approachCardsReceived' );
        console.log( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.approachDeck, card);
        });            
    },

    notif_factionResolveCardDraw: function( notif )
    {
        console.log( 'notif_factionResolveCardDraw' );
        console.log( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });            
    },

    notif_cardAddedToHand: function( notif )
    {
        if (notif.args.player_id !== this.player_id) return;

        console.log( 'notif_cardAddedToHand' );
        console.log( notif );
        this.addCardToDeck(this.factionHand, notif.args.card);
    },

    notif_newDay: function( notif )
    {
        console.log( 'notif_newDay' );
        console.log( notif );

        const args = notif.args;

        $('day-indicator').innerHTML = args.day;
        dojo.style('day-indicator', 'display', 'block');
    },

    notif_cityCardAddedToLocation: function( notif )
    {
        console.log( 'notif_cityCardAddedToLocation' );
        console.log( notif );

        const args = notif.args;

        const card = args.card;
        let cardId = null;
        let location = '';
        switch (args.location) {
            case this.LOCATION_CITY_OLES_INN:
                cardId = `oles-inn-${card.id}`;
                location = 'oles-inn-endcap';
                break;
            case this.LOCATION_CITY_DOCKS:
                cardId = `docks-${card.id}`;
                location = 'dock-endcap';
                break;
            case this.LOCATION_CITY_FORUM:
                cardId = `forum-${card.id}`;
                location = 'forum-endcap';
                break;
            case this.LOCATION_CITY_BAZAAR:
                cardId = `bazaar-${card.id}`;
                location = 'bazaar-endcap';
                break;
            case this.LOCATION_CITY_GOVERNORS_GARDEN:
                cardId = `garden-${card.id}`;
                location = 'garden-endcap';
                break;
        }

        this.createCard(cardId, card, location);

    },

    notif_playerReknownUpdated: function( notif )
    {
        console.log( 'notif_playerReknownUpdated' );
        console.log( notif );

        const args = notif.args;
        $(`${args.player_id}-score-reknown`).innerHTML = args.amount;
    },

    notif_reknownUpdatedOnCard: function( notif )
    {
        console.log( 'notif_reknownUpdatedOnCard' );
        console.log( notif );

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
        console.log( 'notif_reknownAddedToLocation' );
        console.log( notif );

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
        console.log( 'notif_reknownRemovedFromLocation' );
        console.log( notif );

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
        console.log( 'notif_firstPlayer' );
        console.log( notif );

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
        console.log( 'notif_cardRemovedFromCityDiscardPile' );
        console.log( notif );

        const args = notif.args;
        this.gamedatas.cityDiscard = this.gamedatas.cityDiscard.filter((c) => c.id !== args.card.id);
    },

    notif_cardRemovedFromPlayerDiscardPile: function ( notif )
    {
        console.log( 'notif_cardRemovedFromPlayerDiscardPile' );
        console.log( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.player_id];
        player.discard = player.discard.filter((c) => c.id !== args.card.id);
    },

    notif_yevgeni_adversary_chosen: function( notif )
    {
        console.log( 'notif_yevgeni_adversary_chosen' );
        console.log( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        const imageElement = dojo.query('.card', card.divId)[0];
        const id = `${card.divId}-yevgeni-adversary`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: 'yevgeni-adversary-chip',
        }),  imageElement, 'last');

        this.addTooltipHtml( id, `<div class='basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
    }
})
});