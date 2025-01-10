define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.setup', null, {

    setup: function( gamedatas )
    {
        debug( "Starting game setup" );
        debug( "gamedatas", gamedatas );

        // Remove city sections that are not used
        const playerCount = Object.keys(gamedatas.players).length;
        if (playerCount < 4) {
            dojo.destroy('city-governors-garden');
        }
        if (playerCount < 3) {
            dojo.destroy('city-oles-inn');
        }

        // City Discard Pile
        dojo.style($('city-discard'), 'cursor', 'zoom-in');
        dojo.connect($('city-discard'), 'onclick', this, 'onCityDiscardClicked');

        // Set up the city tooltips
        this.addTooltipHtml( 'oles-inn-image', `<div class='basic-tooltip'>${_("Ole's Inn")}</div>` );
        this.addTooltipHtml( 'dock-image', `<div class='basic-tooltip'>${_('The City Docks')}</div>` );
        this.addTooltipHtml( 'forum-image', `<div class='basic-tooltip'>${_('The City Forum')}</div>` );
        this.addTooltipHtml( 'bazaar-image', `<div class='basic-tooltip'>${_('The Grand Bazaar')}</div>` );
        this.addTooltipHtml( 'garden-image', `<div class='basic-tooltip'>${_("Governor's Garden")}</div>` );

        this.addTooltipHtml( 'city-discard', `<div class='basic-tooltip'>${_('City Discard Pile')}</div>` );
        this.addTooltipHtml( 'day-indicator', `<div class='basic-tooltip'>${_('The Current Day')}</div>` );
        this.addTooltipHtml( 'city-day-phase', `<div class='basic-tooltip'>${_('The Current Phase of the Day')}</div>` );
        this.addTooltipHtmlToClass('city-reknown-chip', `<div class='basic-tooltip'>${_('Current Reknown on this City Location')}</div>` );

        //Update the day
        if (gamedatas.day > 0) {
            $('day-indicator').innerHTML = gamedatas.day;
            dojo.style('day-indicator', 'display', 'block');
        }

        //Update the game phase indicator            
        if (gamedatas.turnPhase > 0) {
            switch (gamedatas.turnPhase) {
                case 1: $('city-day-phase').innerHTML = _('Dawn'); break;
                case 2: $('city-day-phase').innerHTML = _('Planning'); break;
                case 3: $('city-day-phase').innerHTML = _('High Drama'); break;
                case 4: $('city-day-phase').innerHTML = _('Plunder'); break;
                case 5: $('city-day-phase').innerHTML = _('Dusk'); break;
            }
            
            dojo.style('city-day-phase', 'display', 'block');
        }

        // Setting up player home boards
        for( const playerId in gamedatas.players )
        {
            const player = gamedatas.players[playerId];

            dojo.style( `player_score_${playerId}`, 'display', 'none' );
            //dojo.style( `icon_point_${playerId}`, 'display', 'none' );

            // Override the score with the reknown
            this.getPlayerPanelElement(playerId).innerHTML = this.format_block( 'jstpl_player_board', {
                id: playerId,
                reknown: player.score,
                crewcap: player.leader?.modifiedCrewCap ?? '',
                panache: player.leader?.modifiedPanache ?? '',
                faction: player.leader?.faction.toLowerCase() ?? '',
            });
            this.addTooltipHtml( `${playerId}-score-reknown`, `<div class='basic-tooltip'>${_('Current Reknown')}</div>` );
            this.addTooltipHtml( `${playerId}-score-crewcap`, `<div class='basic-tooltip'>${_('Current Crew Cap')}</div>` );
            this.addTooltipHtml( `${playerId}-score-panache`, `<div class='basic-tooltip'>${_('Current Panache')}</div>` );

            //Display only if we are out of pre-game setup
            if (gamedatas.turnPhase > 0) {
                // Home
                this.createHome(playerId, player.color, player.leader);
                dojo.addClass( `overall_player_board_${playerId}`, `home-${player.leader.faction.toLowerCase()}` );
                dojo.addClass( `${playerId}-score-seal`, `seal-score seal-${player.leader.faction.toLowerCase()}-score` );

                // Discard Pile
                dojo.style(`${playerId}-discard`, 'cursor', 'zoom-in');
                dojo.connect($(`${playerId}-discard`), 'onclick', this, 'onPlayerDiscardClicked');

                // Locker Pile
                dojo.style(`${playerId}-locker`, 'cursor', 'zoom-in');
                dojo.connect($(`${playerId}-locker`), 'onclick', this, 'onPlayerLockerClicked');

                $(`${playerId}-home-anchor`).setAttribute('data-location', this.LOCATION_PLAYER_HOME);                
            }

            const playerInfo = this.gamedatas.players[playerId];

            //Pull the home cards out of the gamedatas that are for this player.  homecards are an array that is indexed
            let homeCards = gamedatas.homeCards.filter((card) => card.controllerId === parseInt(playerId));

            //Display the scheme first
            const scheme = homeCards.find((card) => card.type === 'Scheme');
            if (scheme)
            {
                homeCards = homeCards.filter((card) => card.type !== 'Scheme');
                const divId = `${playerId}-${scheme.id}`;
                this.createSchemeCard(divId, scheme, playerId + '-scheme-anchor');
            }

            //Display the leader next
            const leader = homeCards.find((card) => card.traits.includes('Leader'));
            if (leader)
            {
                homeCards = homeCards.filter((card) => ! card.traits.includes('Leader'));
                const divId = `${playerId}-${leader.id}`;
                this.createCharacterCard(divId, playerInfo.color, leader, playerId + '-home-anchor');
            }
        
            //Display the rest of the cards
            for( const index in homeCards )
            {
                const card = homeCards[index];
                const divId = `${playerId}-${card.id}`;
                this.createCharacterCard(divId, playerInfo.color, card, playerId + '-home-anchor');
            }
    
        }

        // Display the first player marker if there is one
        if (gamedatas.firstPlayer) {
            dojo.addClass(`${gamedatas.firstPlayer}-first-player`, 'first-player-home');

            dojo.removeClass(`${gamedatas.firstPlayer}-score-seal-first-player`, 'first-player-hidden');
            dojo.addClass(`${gamedatas.firstPlayer}-score-seal-first-player`, 'first-player-score');

            this.addTooltipHtmlToClass('first-player-home', `<div class='basic-tooltip'>${_('First Player')}</div>` );
            this.addTooltipHtmlToClass('first-player-score', `<div class='basic-tooltip'>${_('First Player')}</div>` );
        }

        // Set up Ole's inn
        for( const index in gamedatas.oleCards )
        {
            const card = gamedatas.oleCards[index];
            const cardId = `olesinn-${card.id}`;
            this.createCard(cardId, card, 'oles-inn-endcap');
        }
        if (gamedatas.locationReknown[this.LOCATION_CITY_OLES_INN] != null) {
            $('oles-inn-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_OLES_INN];
            $('oles-inn-image').setAttribute('data-location', this.LOCATION_CITY_OLES_INN);            
        }

        // Set up The Docks
        for( const index in gamedatas.dockCards )
        {
            const card = gamedatas.dockCards[index];
            const cardId = `docks-${card.id}`;
            this.createCard(cardId, card, 'dock-endcap');
        }
        $('dock-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_DOCKS];
        $('dock-image').setAttribute('data-location', this.LOCATION_CITY_DOCKS);

        // Set up The Forum
        for( const index in gamedatas.forumCards )
        {
            const card = gamedatas.forumCards[index];
            const cardId = `forums-${card.id}`;
            this.createCard(cardId, card, 'forum-endcap');
        }
        $('forum-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_FORUM];
        $('forum-image').setAttribute('data-location', this.LOCATION_CITY_FORUM);
            
        // Set up cards in the bazaar
        for( const index in gamedatas.bazaarCards )
        {
            const card = gamedatas.bazaarCards[index];
            const cardId = `bazaar-${card.id}`;
            this.createCard(cardId, card, 'bazaar-endcap');
        }
        $('bazaar-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_BAZAAR];
        $('bazaar-image').setAttribute('data-location', this.LOCATION_CITY_BAZAAR);

        // Set up cards in the governors garden
        for( const index in gamedatas.gardenCards )
        {
            const card = gamedatas.gardenCards[index];
            const cardId = `garden-${card.id}`;
            this.createCard(cardId, card, 'garden-endcap');
        }
        if (gamedatas.locationReknown[this.LOCATION_CITY_GOVERNORS_GARDEN] != null) {
            $('garden-reknown').innerHTML = gamedatas.locationReknown[this.LOCATION_CITY_GOVERNORS_GARDEN];
            $('garden-image').setAttribute('data-location', this.LOCATION_CITY_GOVERNORS_GARDEN);
        }

        // Create Approach deck
        this.approachDeck = new ebg.stock();
        this.approachDeck.create( this, $('approachDeck'), this.wholeCardWidth, this.wholeCardHeight ); 
        this.approachDeck.image_items_per_row = 0;
        this.approachDeck.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
        this.approachDeck.onItemCreate = dojo.hitch( this, 'setupNewStockCard' ); 
        this.approachDeck.setSelectionAppearance( 'class' )
        dojo.connect( this.approachDeck, 'onChangeSelection', this, 'onApproachCardSelected' );
        // For each card in the approach deck, create a stock item
        gamedatas.approachDeck.forEach((card) => {
            this.addCardToDeck(this.approachDeck, card);
        });
        this.approachDeck.setSelectionMode(0);

        // Create the faction hand
        this.factionHand = new ebg.stock();
        this.factionHand.create( this, $('factionHand'), this.wholeCardWidth, this.wholeCardHeight ); 
        this.factionHand.image_items_per_row = 0;
        this.factionHand.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
        this.factionHand.onItemCreate = dojo.hitch( this, 'setupNewStockCard' ); 
        this.factionHand.setSelectionAppearance( 'class' )
        dojo.connect( this.factionHand, 'onChangeSelection', this, 'onFactionCardSelected' );
        // For each card in the approach deck, create a stock item
        gamedatas.factionHand.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });
        this.factionHand.setSelectionMode(0);

        this.chooseList = new ebg.stock();
        this.chooseList.create( this, $('chooseList'), this.wholeCardWidth, this.wholeCardHeight ); 
        this.chooseList.image_items_per_row = 0;
        this.chooseList.resizeItems(this.wholeCardWidth, this.wholeCardHeight, this.wholeCardWidth, this.wholeCardHeight);
        this.chooseList.onItemCreate = dojo.hitch( this, 'setupNewStockCard' ); 
        this.chooseList.setSelectionAppearance( 'class' )
        dojo.connect( this.chooseList, 'onChangeSelection', this, 'onChooseCardSelected' );

        // Setup game notifications to handle (see "setupNotifications" method below)
        this.setupNotifications();

        debug( "Ending game setup" );
    },
})
});