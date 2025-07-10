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
            this.showApproachDeckAtTop();
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
                        var home = $(`${this.getActivePlayerId()}-home-anchor`);
                        dojo.addClass(home, 'selectable');
                        dojo.style(home, 'cursor', 'pointer');
                        const handle = dojo.connect($(home), 'onclick', this, 'onCityLocationClicked');
                        this.connects.push(handle);                
                    }
                    else
                    {
                        const selectedLocationElement = this.getCityLocationElement(location);
                        this.makeCityLocationSelectable(selectedLocationElement.id);
                    }
                });
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, 'chosen');
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
                dojo.addClass(image, 'chosen');
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            if (this.isCurrentPlayerActive()) {
                const performer = this.cardProperties[args.args.performerId];
                this.clientStateArgs.performerId = performer.id;
                const image = $(`${performer.divId}_image`);
                dojo.addClass(image, 'chosen');

                this.numberOfCardsSelectable = 1;
                this.clientStateArgs.discount = args.args.discount;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && !card.controllerId && this.isCardInCity(card.id) && card.location == performer.location ) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                        this.makeCardSelectable(image);

                        if (card.negotiable)                             
                        {
                            const cost = $(`${card.divId}_wealth_cost`);
                            let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
                            discountedCost = discountedCost < 0 ? 0 : discountedCost;
                            cost.innerHTML = parseInt(discountedCost);
                            dojo.addClass(cost, 'discounted-wealth-cost');
                        }
                    }
                }
            }
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (this.isCurrentPlayerActive()) 
            {
                let card = this.cardProperties[args.args.performerId];
                let image = $(`${card.divId}_image`);
                dojo.addClass(image, 'chosen');
                this.clientStateArgs.performerId = card.id;
    
                card = this.cardProperties[args.args.recruitId];
                image = $(`${card.divId}_image`);
                dojo.addClass(image, 'chosen');
                this.clientStateArgs.recruitId = card.id;
    
                const cost = $(`${card.divId}_wealth_cost`);
                let discountedCost = parseInt(cost.innerHTML) - args.args.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                this.clientStateArgs.discountedCost = discountedCost;
                cost.innerHTML = parseInt(discountedCost);
                dojo.addClass(cost, 'discounted-wealth-cost');
    
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
            }
        },    

        'highDramaInPlayActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                if (args.args.actionCardId) 
                {
                    const card = this.cardProperties[args.args.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.addClass(image, 'chosen');
                }
                this.clientStateArgs.actionCardId = args.args.actionCardId;

                this.numberOfCardsSelectable = 1;
                args.args.ids.forEach((id) => { 
                    const card = this.cardProperties[id];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
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
                dojo.addClass(image, 'chosen');
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[args.args._private.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

                args.args._private.attachmentsInHand.forEach((cardId) => {
                    let div = this.factionHand.getItemDivId(cardId);
                    dojo.addClass(div, 'selectable');
                });
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
            }
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.numberOfCardsSelectable = 1;
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

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
                dojo.addClass(image, 'chosen');

                const chosenAttachmentId = args.args._private.chosenAttachmentId;
                const card = args.args._private.chosenAttachment;

                let items = this.factionHand.getAllItems();

                let div = this.factionHand.getItemDivId(chosenAttachmentId);
                dojo.addClass(div, 'unselectable');

                dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                    id: div,
                    cost: card.wealthCost,
                }), div, "first" );    
    
                const costDiv = $(`${div}_wealth_cost`);
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    this.clientStateArgs.discountedCost = discountedCost;
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, 'discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
            }
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                const performer = this.cardProperties[args.args._private.performerId];
                let image = $(`${performer.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');
                this.clientStateArgs.performerId = performer.id;
    
                const card = this.cardProperties[args.args._private.chosenAttachmentId];
                image = $(`${card.divId}_image`);
                dojo.addClass(image, 'chosen');
                this.clientStateArgs.chosenAttachmentId = card.id;
    
                const costDiv = $(`${card.divId}_wealth_cost`);
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, 'discounted-wealth-cost');
                }
        
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
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
                this.showHandAtTop();
                args.args._private.ids.forEach((id) => { 
                    const div = this.factionHand.getItemDivId(id);
                    dojo.addClass(div, 'selectable');
                });
            }
        },

        'highDramaInHandActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) {
                this.showHandAtTop();
                this.clientStateArgs.actionCardId = args.args._private.actionCardId;
                const div = this.factionHand.getItemDivId(args.args._private.actionCardId);
                dojo.addClass(div, 'selected');

                this.numberOfCardsSelectable = 1;
                args.args._private.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                    this.makeCardSelectable(image);
                });
            }
        },

        'highDramaInHandActionPay': () => {
            if (this.isCurrentPlayerActive()) {
                performer = this.cardProperties[args.args._private.performerId];
                const image = $(`${performer.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

                const chosenActionCardId = args.args._private.choseActionCardId;
                const card = this.cardProperties[chosenActionCardId];

                let items = this.factionHand.getAllItems();

                let div = this.factionHand.getItemDivId(chosenActionCardId);
                dojo.addClass(div, 'unselectable');

                dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                    id: div,
                    cost: card.wealthCost,
                }), div, "first" );    
    
                const costDiv = $(`${div}_wealth_cost`);
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    this.clientStateArgs.discountedCost = discountedCost;
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, 'discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
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
                dojo.addClass(image, 'chosen');

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
                dojo.addClass(image, 'chosen');

                card = this.cardProperties[args.args.targetId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');
            }
        },

        'highDramaChallengeActionAcceptChallenge' : () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                card = this.cardProperties[args.args.performerId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

                card = this.cardProperties[args.args.targetId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

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
                const card = this.cardProperties[reactionId];

                let items = this.factionHand.getAllItems();

                let div = this.factionHand.getItemDivId(reactionId);
                dojo.addClass(div, 'unselectable');

                dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                    id: div,
                    cost: card.wealthCost,
                }), div, "first" );    
    
                const costDiv = $(`${div}_wealth_cost`);
                const cost = parseInt(costDiv.innerHTML);
                let discountedCost = cost - args.args._private.args.discount;
                discountedCost = discountedCost < 0 ? 0 : discountedCost;
                if (discountedCost !== cost)
                {
                    this.clientStateArgs.discountedCost = discountedCost;
                    costDiv.innerHTML = parseInt(discountedCost);
                    dojo.addClass(costDiv, 'discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.showHandAtTop();
                this.factionHand.setSelectionMode(2);
            }
        },

        'duelChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode(1);
            }
        },

        'duelUseManeuverFromCombatCard': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.selectItem(args.args._private.cardId);
            }
        },

        'duelPayForManeuverFromCombatCard' : () => {
            if (this.isCurrentPlayerActive()) {
                const cardId = args.args._private.combatCardId;
                const card = this.cardProperties[cardId];
                let div = this.factionHand.getItemDivId(cardId);
                dojo.addClass(div, 'unselectable');
    
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
                    dojo.addClass(costDiv, 'discounted-wealth-cost');
                }
    
                $('faction_hand_info').innerHTML = _(`(0 Wealth worth of cards selected)`);
                this.factionHand.setSelectionMode(2);
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
            dojo.query('.location-control-chip').forEach((element) => {
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
