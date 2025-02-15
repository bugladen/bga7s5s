define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onleavingstate', null, {

onLeavingState: function( stateName )
{
    debug( 'Leaving state: '+ stateName );

    const methods = {

        'planningPhase': () => {
            this.approachDeck.setSelectionMode(0);
        },

        'planningPhaseResolveSchemes_01016': () => {
            this.resetCityLocations();
        },
        
        'planningPhaseResolveSchemes_01016_2': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'planningPhaseResolveSchemes_01016_3': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'planningPhaseResolveSchemes_01044': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },
        
        'planningPhaseResolveSchemes_01045': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'planningPhaseResolveSchemes_01071': () => {
            this.resetCityLocations();
        },
        
        'planningPhaseResolveSchemes_01072': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01098': () => {
            this.resetCityLocations();
        },
        
        'planningPhaseResolveSchemes_01125': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_2': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_3': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01125_4': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = dojo.query('.card', card.divId)[0];
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'planningPhaseResolveSchemes_01126': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01126_2_client': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01143': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01144': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01144_2': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01145': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01145_2_client': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01150': () => {
            dojo.removeClass("forum-image", 'darkened');
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01152': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01152_2': () => {
            this.resetCityLocations();
        },

        'planningPhaseResolveSchemes_01152_3': () => {
            this.resetCityLocations();
        },

        //Clear the leader cards after one is selected
        'planningPhaseEnd_01098': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.traits.includes('Leader') && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'planningPhaseEnd_01098_2': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'highDramaBeginning_01144': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && this.isCardInCity(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);

                    const cost = $(`${card.divId}_wealth_cost`);
                    cost.innerHTML = card.wealthCost;
                    dojo.removeClass(cost, 'discounted-wealth-cost');
                }
            }
        },

        'highDramaBeginning_01144_client': () => {
            this.factionHand.setSelectionMode(0);
            this.clientStateArgs = {};
            $('faction_hand_info').innerHTML = '';
        },

        'highDramaMoveActionChoosePerformer': () => {
            for ( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaMoveActionChooseLocation': () => {
            this.resetCityLocations();
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
            var home = $(`${this.getActivePlayerId()}-home-anchor`);
            dojo.removeClass(home, 'selectable');
            dojo.removeClass(home, 'selected');
            dojo.style(home, 'cursor', 'default');
        },

        'highDramaRecruitActionChoosePerformer': () => {
            for ( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaRecruitActionParley': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && this.isCardInCity(card.id) && card.id != this.clientStateArgs.performerId) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);

                    const cost = $(`${card.divId}_wealth_cost`);
                    cost.innerHTML = card.wealthCost;
                    dojo.removeClass(cost, 'discounted-wealth-cost');
                }
            }
        },

        'highDramaRecruitActionChooseMercenary_client': () => {
            this.factionHand.setSelectionMode(0);
            const card = this.cardProperties[this.clientStateArgs.performerId];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image)
            $('faction_hand_info').innerHTML = '';
        },

        'highDramaEquipActionChoosePerformer': () => {
            for ( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand_client': () => {
            this.clientStateArgs.attachmentsInHand.forEach((cardId) => {
                let div = this.factionHand.getItemDivId(cardId);
                dojo.removeClass(div, 'selectable');
            });
            this.factionHand.setSelectionMode(0);
        },

        'highDramaEquipActionChooseAttachmentFromPlay_client': () => {
            this.clientStateArgs.attachmentsInPlay.forEach((cardId) => {
                let div = this.cardProperties[cardId].divId;
                this.clearCardAsSelectable(`${div}_image`);
            });
        },

        'highDramaEquipActionPayForAttachmentFromHand_client': () => {
            this.clientStateArgs.attachmentsInHand.forEach((cardId) => {
                let div = this.factionHand.getItemDivId(cardId);
                if (dojo.hasClass(div, 'unselectable')) {
                    dojo.removeClass(div, 'unselectable');
                    dojo.destroy(`${div}_wealth_cost`);
                }
            });
            this.factionHand.setSelectionMode(0);
            $('faction_hand_info').innerHTML = '';

            let performer = this.cardProperties[this.clientStateArgs.performerId];
            dojo.removeClass(`${performer.divId}_image`, 'chosen');
        },

        'highDramaEquipActionPayForAttachmentFromPlay_client': () => {
            this.clientStateArgs.attachmentsInPlay.forEach((cardId) => {
                let div = this.cardProperties[cardId].divId;
                this.clearCardAsSelectable(`${div}_image`);
            });
            this.factionHand.setSelectionMode(0);
            $('faction_hand_info').innerHTML = '';

            let performer = this.cardProperties[this.clientStateArgs.performerId];
            dojo.removeClass(`${performer.divId}_image`, 'chosen');
        },

        'highDramaClaimActionChoosePerformer': () => {
            for ( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaChallengeActionChoosePerformer': () => {
            for ( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaChallengeActionChooseTarget': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaChallengeActionActivateTechnique': () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'highDramaChallengeActionAcceptChallenge' : () => {
            for( const cardId in this.cardProperties ) {
                card = this.cardProperties[cardId];
                if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                }
            }
        },

        'duelChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode(0);
            }
        },

        'duelChooseGambleCard': () => {
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        }

    };

    if (methods[stateName]) {
        methods[stateName]();
    }

    this.selectedCityLocations = [];
    this.selectedCards = [];

    //Disconnect any connect handlers that were created
    this.connects.forEach((handle) => {
        dojo.disconnect(handle);
    });
},

})
});
