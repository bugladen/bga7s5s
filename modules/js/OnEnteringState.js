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
            //Enable the approach deck
            this.approachDeck.setSelectionMode(2);
        },

        'planningPhaseResolveSchemes_01016': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 2;
                locations.forEach((location) => {
                    dojo.addClass(location, 'selectable');
                    dojo.style(location, 'cursor', 'pointer');

                    const handle = dojo.connect($(location), 'onclick', this, 'onCityLocationClicked');
                    this.connects.push(handle);
                });
            }
        },

        'planningPhaseResolveSchemes_01016_2': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Red Hand Thugs in Your Faction Deck');

                // For each Red Hand Thug card in the players deck, create a stock item
                args.args.thugs.forEach((card) => {
                    this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(1);
            }
        },

        'planningPhaseResolveSchemes_01016_3': () => {
            dojo.removeClass('choose_container', 'hidden');
            dojo.removeClass('chooseList', 'hidden');
            $('choose_container_name').innerHTML = _('Chosen Red Hand Thug');

            //Wait a second for stock object to catch up?
            setTimeout(() => {
                this.addCardToDeck(this.chooseList, args.args.card);
                this.chooseList.setSelectionMode(0);
            }, 500);
        },


        'planningPhaseResolveSchemes_01044': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Your Discard Pile');

                // For each card in the players discard pile, create a stock item
                const player = this.gamedatas.players[this.getActivePlayerId()];      
                player.discard.forEach((card) => {
                    if (card.type === 'Attachment')
                        this.addCardToDeck(this.chooseList, card);
                });
                this.chooseList.setSelectionMode(1);

                if (this.chooseList.count() > 0) 
                    dojo.addClass('actPass', 'disabled');
            }
        },

        'planningPhaseResolveSchemes_01045': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.removeClass('choose_container', 'hidden');
                dojo.removeClass('chooseList', 'hidden');
                $('choose_container_name').innerHTML = _('Mercenaries in the City Deck Discard Pile');

                // For each card in the city discard pile, create a stock item
                this.gamedatas.cityDiscard.forEach((card) => {
                    if (card.traits.includes('Mercenary')) {
                        this.addCardToDeck(this.chooseList, card);
                    }                            

                });
                this.chooseList.setSelectionMode(1);

                if (this.chooseList.count() > 0) 
                    dojo.addClass('actPass', 'disabled');
            }
        },

        'planningPhaseResolveSchemes_01071': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01072': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown > 0) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01098': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 2;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01125': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01125_2': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                let count = 0;
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown == 0) return;

                    this.makeCityLocationSelectable(location);
                    count++;
                });

                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01125_3': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];

                const locations = this.getListOfLocationsAdjacentToLocation(selectedLocationElement.id);
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01125_4': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                let count = 0;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                        //Get the element that is a child of card.divId with the class 'card'
                        const image = dojo.query('.card', card.divId)[0];
                        this.makeCardSelectable(image);

                        count++;
                    }
                }
                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01126': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofOutermostCityLocations();
                this.numberOfCityLocationsSelectable = 1;

                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },
                

        'planningPhaseResolveSchemes_01126_2_client': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 2;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01143': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01144': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01144_2': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];

                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01145': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                let count = 0;
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown == 0) return;

                    this.makeCityLocationSelectable(location);
                    count++;
                });

                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01145_2_client': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = $(this.clientStateArgs.selectedCityLocations[0]);
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01150': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    if (location == 'forum-image')
                    {
                        dojo.addClass(location, 'darkened');
                        return;
                    } 

                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown === 0) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01152': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    this.makeCityLocationSelectable(location);
                });
            }
        },

        'planningPhaseResolveSchemes_01152_2': () => {
            if (this.isCurrentPlayerActive()) {
                const locations = this.getListofAvailableCityLocationImages();
                let count = 0;
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    const reknownElement = dojo.query('.city-reknown-chip', imageElement.parentElement)[0];
                    const reknown = parseInt(reknownElement.innerHTML);
                    if (reknown == 0) return;

                    this.makeCityLocationSelectable(location);
                    count++;
                });

                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseResolveSchemes_01152_3': () => {
            if (this.isCurrentPlayerActive()) {
                const selectedLocationElement = dojo.query(`[data-location="${args.args.location}"]`)[0];

                const locations = this.getListOfLocationsAdjacentToLocation(selectedLocationElement.id);
                this.numberOfCityLocationsSelectable = 1;
                locations.forEach((location) => {
                    const imageElement = $(location);
                    if (imageElement.id == selectedLocationElement.id) return;

                    this.makeCityLocationSelectable(location);
                });
            }
        },
        
        'planningPhaseEnd_01098': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                let count = 0;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.traits.includes('Leader') && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);

                        count++;
                    }
                }
                if (count > 0) {
                    dojo.addClass('actPass', 'disabled');
                }
            }
        },

        'planningPhaseEnd_01098_2': () => {
            dojo.removeClass('choose_container', 'hidden');
            dojo.removeClass('chooseList', 'hidden');
            $('choose_container_name').innerHTML = _('Revealed Card');

            // For each card in the players discard pile, create a stock item
            this.addCardToDeck(this.chooseList, args.args.card);
            this.chooseList.setSelectionMode(0);
        },

        'highDramaBeginning': () => {
            $('city-day-phase').innerHTML = _('High Drama');
        },

        'highDramaBeginning_01144': () => {
            if (this.isCurrentPlayerActive()) {
                this.numberOfCardsSelectable = 1;
                this.clientStateArgs.discount = args.args.discount;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && !card.controllerId && this.isCardInCity(card.id) ) {
                        const image = $(`${card.divId}_image`);
                        this.makeCardSelectable(image);

                        const cost = $(`${card.divId}_wealth_cost`);
                        let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
                        discountedCost = discountedCost < 0 ? 0 : discountedCost;
                        cost.innerHTML = parseInt(discountedCost);
                        dojo.addClass(cost, 'discounted-wealth-cost');
                    }
                }
            }
        },

        'highDramaBeginning_01144_client': () => {
            const card = this.cardProperties[this.clientStateArgs.selectedCards[0]];
            const image = $(`${card.divId}_image`);
            dojo.addClass(image, 'selectable');

            const cost = $(`${card.divId}_wealth_cost`);
            let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
            discountedCost = discountedCost < 0 ? 0 : discountedCost;
            this.clientStateArgs.discountedCost = discountedCost;
            cost.innerHTML = parseInt(discountedCost);
            dojo.addClass(cost, 'discounted-wealth-cost');

            this.factionHand.setSelectionMode(2);
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
                        const selectedLocationElement = dojo.query(`[data-location="${location}"]`)[0];
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
                card = this.cardProperties[args.args.performerId];
                this.clientStateArgs.performerId = card.id;
                const image = $(`${card.divId}_image`);
                dojo.addClass(image, 'chosen');

                this.numberOfCardsSelectable = 1;
                this.clientStateArgs.discount = args.args.discount;
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && !card.controllerId && this.isCardInCity(card.id) ) {
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

        'highDramaRecruitActionChooseMercenary_client': () => {
            const card = this.cardProperties[this.clientStateArgs.selectedCards[0]];
            const image = $(`${card.divId}_image`);
            dojo.addClass(image, 'chosen');

            const cost = $(`${card.divId}_wealth_cost`);
            let discountedCost = parseInt(cost.innerHTML) - this.clientStateArgs.discount;
            discountedCost = discountedCost < 0 ? 0 : discountedCost;
            this.clientStateArgs.discountedCost = discountedCost;
            cost.innerHTML = parseInt(discountedCost);
            dojo.addClass(cost, 'discounted-wealth-cost');

            this.factionHand.setSelectionMode(2);
        },    

        'highDramaEquipActionChoosePerformer': () => {
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

        'highDramaEquipActionChooseAttachmentLocation': () => {
            if (this.isCurrentPlayerActive()) {
                card = this.cardProperties[args.args.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                dojo.addClass(image, 'chosen');

                this.clientStateArgs = args.args;
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand_client': () => {
            card = this.cardProperties[this.clientStateArgs.performerId];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image);
            dojo.addClass(image, 'chosen');

            this.clientStateArgs.attachmentsInHand.forEach((cardId) => {
                let div = this.factionHand.getItemDivId(cardId);
                dojo.addClass(div, 'selectable');
            });
            this.factionHand.setSelectionMode(2);
        },

        'highDramaEquipActionChooseAttachmentFromPlay_client': () => {
            this.numberOfCardsSelectable = 1;
            card = this.cardProperties[this.clientStateArgs.performerId];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image);
            dojo.addClass(image, 'chosen');

            this.clientStateArgs.attachmentsInPlay.forEach((cardId) => {
                card = this.cardProperties[cardId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
                this.makeCardSelectable(image);
            });
        },

        'highDramaEquipActionPayForAttachmentFromHand_client': () => {
            const chosenAttachmentId = this.clientStateArgs.chosenAttachmentId;
            const card = this.cardProperties[chosenAttachmentId];
            let div = this.factionHand.getItemDivId(chosenAttachmentId);
            dojo.addClass(div, 'unselectable');

            dojo.place( this.format_block( 'jstpl_hand_wealth_cost_chip', {
                id: div,
                cost: card.wealthCost,
            }), div, "first" );    

            const costDiv = $(`${div}_wealth_cost`);
            const cost = parseInt(costDiv.innerHTML);
            let discountedCost = cost - this.clientStateArgs.discount;
            discountedCost = discountedCost < 0 ? 0 : discountedCost;
            if (discountedCost !== cost)
            {
                this.clientStateArgs.discountedCost = discountedCost;
                costDiv.innerHTML = parseInt(discountedCost);
                dojo.addClass(costDiv, 'discounted-wealth-cost');
            }

            $('faction_hand_info').innerHTML = `(0 Wealth worth of cards selected)`;
            this.factionHand.setSelectionMode(2);
        },

        'highDramaEquipActionPayForAttachmentFromPlay_client': () => {
            this.clientStateArgs.chosenAttachmentId = this.clientStateArgs.selectedCards[0];
            const card = this.cardProperties[this.clientStateArgs.chosenAttachmentId];
            const image = $(`${card.divId}_image`);
            dojo.addClass(image, 'chosen');

            const costDiv = $(`${card.divId}_wealth_cost`);
            const cost = parseInt(costDiv.innerHTML);
            let discountedCost = cost - this.clientStateArgs.discount;
            discountedCost = discountedCost < 0 ? 0 : discountedCost;
            if (discountedCost !== cost)
            {
                this.clientStateArgs.discountedCost = discountedCost;
                cost.innerHTML = parseInt(discountedCost);
                dojo.addClass(costDiv, 'discounted-wealth-cost');
            }
    
            $('faction_hand_info').innerHTML = `(0 Wealth worth of cards selected)`;
            this.factionHand.setSelectionMode(2);
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

        'duelChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode(1);
            }
        }
    };
    
    if (methods[stateName]) {
        methods[stateName](args);
    }
},

})
});
