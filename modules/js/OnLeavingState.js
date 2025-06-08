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
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId != this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = dojo.query('.card', card.divId)[0];
                        this.clearCardAsSelectable(image);
                    }
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
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.traits.includes('Leader') && card.controllerId && card.controllerId != this.getActivePlayerId()) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'planningPhaseEnd_01098_2': () => {
            //Exposed to all players
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
        },

        'highDramaBeginning_01144': () => {
            if (this.isCurrentPlayerActive()) 
            {
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
            }
        },

        'highDramaBeginning_01144_client': () => {
            this.factionHand.setSelectionMode(0);
            this.clientStateArgs = {};
            $('faction_hand_info').innerHTML = '';
        },

        'highDramaMoveActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaMoveActionChooseLocation': () => {
            if (this.isCurrentPlayerActive()) 
            {
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
            }
        },

        'highDramaRecruitActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaRecruitActionParley': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaRecruitActionChooseMercenary': () => {
            if (this.isCurrentPlayerActive()) 
            {
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
            }
        },

        'highDramaRecruitActionPayForMercenary_client': () => {
            this.showHandAtBottom();
            this.factionHand.setSelectionMode(0);
            const card = this.cardProperties[this.clientStateArgs.performerId];
            const image = $(`${card.divId}_image`);
            this.clearCardAsSelectable(image)
            $('faction_hand_info').innerHTML = '';

            const cost = $(`${card.divId}_wealth_cost`);
            cost.innerHTML = card.wealthCost;
            dojo.removeClass(cost, 'discounted-wealth-cost');
        },

        'highDramaInPlayActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (this.isCardInCity(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaInHandActionChooseAction': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, 'selectable')) {
                        dojo.removeClass(div, 'selectable');
                    }
                });
                this.showHandAtBottom();
            }
        },

        'highDramaInHandActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {                
                let div = this.factionHand.getItemDivId(this.clientStateArgs.actionCardId);
                dojo.removeClass(div, 'selected');

                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (this.isCardInCity(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
                this.clientStateArgs = {};
            }
        },

        'highDramaInHandActionPay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, 'unselectable')) {
                        dojo.removeClass(div, 'unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                this.showHandAtBottom();
    
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaEquipActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaEquipActionChooseAttachmentFromHand': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    dojo.removeClass(div, 'selectable');
                });
                this.showHandAtBottom();
                this.factionHand.setSelectionMode(0);
            }
        },

        'highDramaEquipActionChooseAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Attachment' && ! card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaEquipActionPayForAttachmentFromHand': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, 'unselectable')) {
                        dojo.removeClass(div, 'unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                this.showHandAtBottom();

                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaEquipActionPayForAttachmentFromPlay': () => {
            if (this.isCurrentPlayerActive()) 
            {   
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Attachment' && ! card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                this.showHandAtBottom();
            }
        },

        'highDramaClaimActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                            this.clearCardAsSelectable(image);
                        }
                }
            }
        },

        'highDramaChallengeActionChoosePerformer': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for ( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaChallengeActionChooseTarget': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaChallengeActionActivateTechnique': () => {
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaChallengeActionAcceptChallenge' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaPhase01029': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.clientStateArgs.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                });
                this.clientStateArgs = {};
            }
        },

        'highDramaPhase01180' : () => {
            //Exposed to all players
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
            this.chooseList.setSelectionMode(0);
        },

        'highDramaPhase01180_3' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            }
        },

        'highDramaPhase01180_4' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);

                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                            this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaPhase01180_5' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);

                this.showHandAtBottom();
                this.factionHand.setSelectionMode(0);
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && card.controllerId == this.getActivePlayerId() && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaPhase01185': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.setSelectionMode(0);
                this.showHandAtBottom();
                $('faction_hand_info').innerHTML = '';

                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Event' && this.isCardInCity(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'highDramaPhase01189a': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.resetCityLocations();
                card = this.cardProperties[this.clientStateArgs.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
            }
        },

        'highDramaPhase01189b': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.resetCityLocations();
                card = this.cardProperties[this.clientStateArgs.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
            }
        },

        'highDramaPhase01192' : () => {
            //Exposed to all players
            dojo.addClass('choose_container', 'hidden');
            dojo.addClass('chooseList', 'hidden');
            this.chooseList.removeAll();
            this.chooseList.setSelectionMode(0);
        },

        'highDramaPhase01192_3' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            }
        },

        'highDramaPhase01194': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[this.clientStateArgs.characterId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);
            }
        },

        'highDramaPhase01194_2': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[this.clientStateArgs.characterId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);

                this.clientStateArgs.targetCharacterIds.forEach((characterId) => {
                    card = this.cardProperties[characterId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                });
            }
        },

        'highDramaPhase01197': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[this.clientStateArgs.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);  

                this.clientStateArgs.targetCharacterIds.forEach((characterId) => {
                    card = this.cardProperties[characterId];
                    const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                });
            }
        },

        'highDramaPhase01197_2': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[this.clientStateArgs.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);

                card = this.cardProperties[this.clientStateArgs.chosenCharacterId];
                const chosenImage = $(`${card.divId}_image`);
                dojo.removeClass(chosenImage, 'chosen');
            }
        },

        'highDramaPhase01197_3': () => {
            if (this.isCurrentPlayerActive()) 
            {
                card = this.cardProperties[this.clientStateArgs.performerId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);

                card = this.cardProperties[this.clientStateArgs.chosenCharacterId];
                const chosenImage = $(`${card.divId}_image`);
                dojo.removeClass(chosenImage, 'chosen');

                this.clientStateArgs.targetCharacterIds.forEach((characterId) => {
                    card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
            }
        },

        'highDramaPhase01200_2': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            }
        },

        'highDramaPhase01205': () => {
            if (this.isCurrentPlayerActive()) {
                card = this.cardProperties[this.clientStateArgs.characterId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);

                this.clientStateArgs.targetCharacterIds.forEach((characterId) => {
                    card = this.cardProperties[characterId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                });
            }
        },

        'highDramaPhase01205_2': () => {
            if (this.isCurrentPlayerActive()) {
                this.resetCityLocations();
                let card = this.cardProperties[this.clientStateArgs.characterId];
                const image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image);

                card = this.cardProperties[this.clientStateArgs.victimId];
                const victimImage = $(`${card.divId}_image`);
                dojo.removeClass(victimImage, 'chosen');
            }
        },


        'duelChooseAction': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode(0);
            }
        },

        'duelChooseGambleCard': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            }
        },

        'duelUseManeuverFromCombatCard' : () => {
            if (this.isCurrentPlayerActive()) {
                var items = this.factionHand.getSelectedItems();
                const types = {};
                items.forEach((item) => {
                    this.factionHand.unselectItem(item.id);
                });
            }
        },

        'duelPayForManeuverFromCombatCard' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, 'unselectable')) {
                        dojo.removeClass(div, 'unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
            }
        },

        'duskPhaseBegin01177' : () => {
            if (this.isCurrentPlayerActive()) {
                for( const cardId in this.cardProperties ) {
                    card = this.cardProperties[cardId];
                    if (card.type === 'Character' && card.controllerId && this.isCardInPlay(card.id)) {
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    }
                }
            }
        },

        'duskPhaseBegin01177_2': () => {
            if (this.isCurrentPlayerActive()) {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            }
        },

        'duskPhaseDiscard': () => {
            if (this.isCurrentPlayerActive()) {
                this.factionHand.setSelectionMode(0);
                this.showHandAtBottom();
                $('faction_hand_info').innerHTML = '';
            }
        },

        'playerPayForReaction': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, 'unselectable')) 
                        {
                        dojo.removeClass(div, 'unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                    this.showHandAtBottom();
            }
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
