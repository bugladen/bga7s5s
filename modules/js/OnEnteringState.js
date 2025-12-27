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
return declare('seventhseacityoffivesails.onenteringstate', null, {

onEnteringState: function( stateName, args )
{
    debug( 'Entering state: '+ stateName, args );

    const methods = {
        'dawnBeginning': (args) => {
            $('city-day-phase').innerHTML = _('Dawn');
            dojo.style('city-day-phase', 'display', 'block');            
        },

        'planningPhase': () => {
            $('city-day-phase').innerHTML = _('Planning');
            if (! this.isSpectator)
            {
                this.showApproachDeckAtTop();
            }
        },

        'highDramaBeginning': () => {
            $('city-day-phase').innerHTML = _('High Drama');
        },

        'highDramaPlayerTurn': () => {
            this.clientStateArgs = {};
        },

        'highDramaMoveActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaMoveActionChooseLocation': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCityLocationsSelectable = 1;
                args.args.locations.forEach((location) => {
                    if (location == this.LOCATION_PLAYER_HOME)
                    {
                        this.makeHomeEndcapMarkerSelectable();
                    }
                    else
                    {
                        const selectedLocationElement = this.getCityLocationElement(location);
                        this.makeCityLocationSelectable(selectedLocationElement.id);
                    }
                });
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
            }
        },

        'highDramaRecruitActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaRecruitActionParley': () => {
            if (this.isCurrentPlayerActive()) {
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            if (this.isCurrentPlayerActive()) {
                const performer = this.cardProperties[args.args.performerId];
                this.clientStateArgs.performerId = performer.id;
                const image = $(`${performer.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');

                this.numberOfCardsSelectable = 1;
                this.clientStateArgs.discount = args.args.discount;

                args.args.characterIds.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);

                    if (card.negotiable || args.args.recruitType == this.CIRILO_RECRUIT_TYPE)
                    {
                        const cost = $(`${card.divId}_wealth_cost`);
                        const originalCost = parseInt(cost.innerHTML);

                        let discountedCost = originalCost - this.clientStateArgs.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;

                        if (args.args.recruitType == this.CIRILO_RECRUIT_TYPE)
                            discountedCost = 1;                        

                        cost.innerHTML = parseInt(discountedCost);
                        if (originalCost != discountedCost)
                            dojo.addClass(cost, '_7sfs-discounted-wealth-cost');
                    }
                });
            }
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (this.isCurrentPlayerActive()) 
            {
                let card = this.cardProperties[args.args.performerId];
                let image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.performerId = card.id;
    
                card = this.cardProperties[args.args.recruitId];
                image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.recruitId = card.id;
    
                const cost = $(`${card.divId}_wealth_cost`);
                const originalCost = parseInt(cost.innerHTML);

                let discountedCost = originalCost - args.args.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                
                if (args.args.recruitType == this.CIRILO_RECRUIT_TYPE)
                    discountedCost = 1;

                this.clientStateArgs.discountedCost = discountedCost;
                cost.innerHTML = parseInt(discountedCost);
                if (originalCost != discountedCost)
                    dojo.addClass(cost, '_7sfs-discounted-wealth-cost');
    
                this.factionHand.setSelectionMode('multiple');
            }
        },    

        'highDramaInPlayActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                if (args.args.actionCardId) 
                {
                    const card = this.cardProperties[args.args.actionCardId];
                    if (card) {
                        const image = $(`${card.divId}_image`);
                        dojo.addClass(image, '_7sfs-chosen');
                    }
                }
                this.clientStateArgs.actionCardId = args.args.actionCardId;

                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((id) => { 
                    const card = this.cardProperties[id];
                    if (card) {
                    const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);
                    }
                });
                this.clientStateArgs.ids = args.args.ids;
            }
        },

        'highDramaEquipActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                args.args._private.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaEquipActionChooseAttachmentLocation': () => {
            if (this.isCurrentPlayerActive()) {
                card = this.cardProperties[args.args._private.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[args.args._private.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                args.args._private.attachmentsInHand.forEach((cardId) => {
                    const card = this.factionHand.getCards().find(c => c.id === cardId);
                    const cardElement = card ? this.factionHand.getCardElement(card) : null;
                    if (cardElement) dojo.addClass(cardElement, '_7sfs-selectable');
                });
                this.factionHand.setSelectionMode('multiple');
            }
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.numberOfCardsSelectable = 1;
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                args.args.attachmentsInPlay.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            if (this.isCurrentPlayerActive()) {
                performer = this.cardProperties[args.args._private.performerId];
                const image = $(`${performer.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                const chosenAttachmentId = args.args._private.chosenAttachmentId;
                const cardData = args.args._private.chosenAttachment;

                const handCard = this.factionHand.getCards().find(c => c.id === chosenAttachmentId);
                const cardElement = handCard ? this.factionHand.getCardElement(handCard) : null;
                if (cardElement) {
                    dojo.addClass(cardElement, '_7sfs-unselectable');

                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: cardElement.id,
                        cost: cardData.wealthCost,
                    }), cardElement, "first" );    
                }
    
                const costDiv = cardElement ? $(`${cardElement.id}_wealth_cost`) : null;
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    this.clientStateArgs.discountedCost = discountedCost;
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode('multiple');

                this.clientStateArgs.chosenCardId = chosenAttachmentId;
            }
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                const performer = this.cardProperties[args.args._private.performerId];
                let image = $(`${performer.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.performerId = performer.id;
    
                const card = this.cardProperties[args.args._private.chosenAttachmentId];
                image = $(`${card.divId}_image`);
                dojo.addClass(image, '_7sfs-chosen');
                this.clientStateArgs.chosenAttachmentId = card.id;
    
                const costDiv = $(`${card.divId}_wealth_cost`);
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                }
        
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode('multiple');
            }
        },

        'highDramaClaimActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaInHandActionChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                args.args._private.ids.forEach((id) => { 
                    const card = this.factionHand.getCards().find(c => c.id === id);
                    const cardElement = card ? this.factionHand.getCardElement(card) : null;
                    if (cardElement) dojo.addClass(cardElement, '_7sfs-selectable');
                });
            }
        },

        'highDramaInHandActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.clientStateArgs.actionCardId = args.args._private.actionCardId;
                const actionCard = this.factionHand.getCards().find(c => c.id === args.args._private.actionCardId);
                const cardElement = actionCard ? this.factionHand.getCardElement(actionCard) : null;
                if (cardElement) dojo.addClass(cardElement, '_7sfs-selected');

                this.numberOfCardsSelectable = 1;
                args.args._private.ids.forEach((cardId) => {
                    this.highlightCardsAsSelectable([cardId]);
                });
                this.clientStateArgs.ids = args.args._private.ids;
            }
        },

        'highDramaInHandActionPay': () => {
            if (this.isCurrentPlayerActive()) {
                if (args.args._private.performerId)
                {
                    performer = this.cardProperties[args.args._private.performerId];
                    const image = $(`${performer.divId}_image`);
                    this.clearCardAsSelectable(image);
                    dojo.addClass(image, '_7sfs-chosen');
                    this.clientStateArgs.performerId = performer.id;
                }

                const chosenActionCardId = args.args._private.choseActionCardId;
                this.clientStateArgs.chosenActionCardId = chosenActionCardId;
                const cardProps = this.cardProperties[chosenActionCardId];

                const handCard = this.factionHand.getCards().find(c => c.id === chosenActionCardId);
                const cardElement = handCard ? this.factionHand.getCardElement(handCard) : null;
                if (cardElement) {
                    dojo.addClass(cardElement, '_7sfs-unselectable');

                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: cardElement.id,
                        cost: cardProps.wealthCost,
                    }), cardElement, "first" );    
                }
    
                const costDiv = cardElement ? $(`${cardElement.id}_wealth_cost`) : null;
                const cost = costDiv ? parseInt(costDiv.innerHTML) : 0;
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    this.clientStateArgs.discountedCost = discountedCost;
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode('multiple');
            }
        },

        'highDramaBruteActionChooseBrute': () => {
            if (this.isCurrentPlayerActive()) 
            {
                args.args._private.ids.forEach((cardId) => {
                    const card = this.factionHand.getCards().find(c => c.id === cardId);
                    const cardElement = card ? this.factionHand.getCardElement(card) : null;
                    if (cardElement) dojo.addClass(cardElement, '_7sfs-selectable');
                });
                this.factionHand.setSelectionMode('single');
            }
        },

        'highDramaBruteActionPayForBrute': () => {
            if (this.isCurrentPlayerActive()) {
                const chosenBruteId = args.args._private.bruteId;
                const bruteData = args.args._private.brute;

                const handCard = this.factionHand.getCards().find(c => c.id === chosenBruteId);
                const cardElement = handCard ? this.factionHand.getCardElement(handCard) : null;
                if (cardElement) {
                    dojo.addClass(cardElement, '_7sfs-unselectable');

                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: cardElement.id,
                        cost: bruteData.wealthCost,
                    }), cardElement, "first" );    
        
                    const costDiv = $(`${cardElement.id}_wealth_cost`);
                    const cost = parseInt(costDiv.innerHTML);
                    let discountedCost = cost - args.args._private.discount;
                    discountedCost = discountedCost < 0 ? 0 : discountedCost;
                    if (discountedCost !== cost)
                    {
                        this.clientStateArgs.discountedCost = discountedCost;
                        costDiv.innerHTML = parseInt(discountedCost);
                        dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                    }
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode('multiple');

                this.clientStateArgs.chosenCardId = chosenBruteId;
            }
        },

        'highDramaChallengeActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },
        
        'highDramaChallengeActionChooseTarget': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;

                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaChallengeActionActivateTechnique' : () => {
            if (this.isCurrentPlayerActive()) {
                card = this.cardProperties[args.args.performerId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                card = this.cardProperties[args.args.targetId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');
            }
        },

        'highDramaChallengeActionAcceptChallenge' : () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                card = this.cardProperties[args.args.performerId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                card = this.cardProperties[args.args.targetId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, '_7sfs-chosen');

                args.args.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'playerReaction': () => {
            if (this.isCurrentPlayerActive()) {
                this.gamedatas.gamestate.descriptionmyturn = _(args.args._private.args.descriptionmyturn);
                this.updatePageTitle();
            }
        },

        'playerPayForReaction': () => {
            if (this.isCurrentPlayerActive()) {
                this.gamedatas.gamestate.descriptionmyturn = _(args.args._private.args.descriptionmyturn);
                this.updatePageTitle();

                const reactionId = args.args._private.args.reactionId;
                const cardProps = this.cardProperties[reactionId];
                this.clientStateArgs.reactionCardId = reactionId;

                const handCard = this.factionHand.getCards().find(c => c.id === reactionId);
                const cardElement = handCard ? this.factionHand.getCardElement(handCard) : null;

                if (cardElement)
                {
                    dojo.addClass(cardElement, '_7sfs-unselectable');

                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: cardElement.id,
                        cost: cardProps.wealthCost,
                    }), cardElement, "first" );    
        
                    const costDiv = $(`${cardElement.id}_wealth_cost`);
                    const cost = parseInt(costDiv.innerHTML);
                    let discountedCost = cost - args.args._private.args.discount;
                    discountedCost = discountedCost < 0 ? 0 : discountedCost;
                    if (discountedCost !== cost)
                    {
                        this.clientStateArgs.discountedCost = discountedCost;
                        costDiv.innerHTML = parseInt(discountedCost);
                        dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                    }
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode('multiple');
            }
        },

        'duelChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode('single');
            }
        },

        'duelUseManeuverFromCombatCard': () => {
            if (this.isCurrentPlayerActive()) 
            {
                setTimeout(async () => {
                    if (args.args._private.gambled)
                        {
                            dojo.removeClass('choose_container', 'hidden');
                            dojo.removeClass('chooseList', 'hidden');
                            $('choose_container_name').innerHTML = _('Chosen Gamble Card');    
                            this.addCardToDeck(this.chooseList, args.args._private.card);
                            this.chooseList.setSelectionMode(0);
                        }
                        else {
                            const card = this.factionHand.getCards().find(c => c.id === args.args._private.cardId);
                            if (card) this.factionHand.selectCard(card);
                        }
                }, 500);
            }
        },

        'duelPayForManeuverFromCombatCard' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                setTimeout(async () => {
                    let div = null;
                    if (args.args._private.gambled)
                    {
                        dojo.removeClass('choose_container', 'hidden');
                        dojo.removeClass('chooseList', 'hidden');
                        $('choose_container_name').innerHTML = _('Chosen Gamble Card');
    
                        this.addCardToDeck(this.chooseList, args.args._private.card);
                        const cardId = args.args._private.combatCardId;
                        div = this.chooseList.getItemDivId(cardId);
                        this.chooseList.setSelectionMode(0);
                    }
                    else
                    {
                        const cardId = args.args._private.combatCardId;
                        this.clientStateArgs.combatCardId = cardId;
                        const handCard = this.factionHand.getCards().find(c => c.id === cardId);
                        const cardElement = handCard ? this.factionHand.getCardElement(handCard) : null;
                        if (cardElement) {
                            div = cardElement.id;
                            dojo.addClass(cardElement, '_7sfs-unselectable');
                        }
                    }
        
                    dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                        id: div,
                        cost: args.args._private.cost,
                    }), div, "first" );    
    
                    const costDiv = $(`${div}_wealth_cost`);
                    const cost = parseInt(costDiv.innerHTML);
                    let discountedCost = cost - args.args._private.discount;
                    discountedCost = discountedCost < 0 ? 0 : discountedCost;
                    if (discountedCost !== cost)
                    {
                        costDiv.innerHTML = parseInt(discountedCost);
                        dojo.addClass(costDiv, '_7sfs-discounted-wealth-cost');
                    }
    
                    $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                    this.factionHand.setSelectionMode('multiple');
                }, 500);
            }
        },

        'duelChooseGambleCard': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Gamble Cards');

                args.args._private.cards.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(1);
            }
        },

        'plunderPhaseBegin': () => {
            $('city-day-phase').innerHTML = _('Plunder');
        },

        'duskPhaseBegin': () => {
            $('city-day-phase').innerHTML = _('Dusk');
        },

        'duskPhaseCleanup': () => {
            //Remove all control chips from locations
            dojo.query('._7sfs-location-control-chip').forEach((element) => {
                element.remove();
            });
        },
    };
    
    if (methods[stateName])
        methods[stateName](args);

    this.onEnteringState_7s5s( stateName, args );
},

})
});
