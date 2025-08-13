define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.onleavingstate', null, {

// Main game methods only
onLeavingState: function( stateName )
{
    debug( 'Leaving state: '+ stateName );

    const methods = {

        'pickDecks': () => {
            if (! this.isSpectator)
            {
                dojo.destroy('deck-picker');
                dojo.removeClass('city', 'hidden');
                dojo.removeClass('approachDeck-container', 'hidden');
                dojo.removeClass('factionHand-container', 'hidden');
            }
        },

        'planningPhase': () => {
            if (! this.isSpectator)
            {
                this.showApproachDeckAtBottom();
                this.approachDeck.setSelectionMode(0);
            }
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
                dojo.removeClass(home, '_7sfs-selectable');
                dojo.removeClass(home, '_7sfs-selected');
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
                        dojo.removeClass(cost, '_7sfs-discounted-wealth-cost');
                    }
                }
            }
        },

        'highDramaRecruitActionPayForMercenary': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.showHandAtBottom();
                this.factionHand.setSelectionMode(0);
                let card = this.cardProperties[this.clientStateArgs.performerId];
                let image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image)
    
                card = this.cardProperties[this.clientStateArgs.recruitId];
                image = $(`${card.divId}_image`);
                this.clearCardAsSelectable(image)
    
                $('faction_hand_info').innerHTML = '';
    
                const cost = $(`${card.divId}_wealth_cost`);
                cost.innerHTML = card.wealthCost;
                dojo.removeClass(cost, '_7sfs-discounted-wealth-cost');
            }                
        },

        'highDramaInPlayActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {
                let card = this.cardProperties[this.clientStateArgs.actionCardId];
                const image = $(`${card.divId}_image`);
                dojo.removeClass(image, '_7sfs-chosen');

                this.clientStateArgs.ids.forEach((cardId) => {
                    card = this.cardProperties[cardId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);
                });
            }
        },

        'highDramaInHandActionChooseAction': () => {
            if (this.isCurrentPlayerActive()) 
            {
                this.factionHand.getAllItems().forEach((card, index) => {
                    let div = this.factionHand.getItemDivId(card.id);
                    if (dojo.hasClass(div, '_7sfs-selectable')) {
                        dojo.removeClass(div, '_7sfs-selectable');
                    }
                });
                this.showHandAtBottom();
            }
        },

        'highDramaInHandActionChoosePerformer' : () => {
            if (this.isCurrentPlayerActive()) 
            {                
                let div = this.factionHand.getItemDivId(this.clientStateArgs.actionCardId);
                dojo.removeClass(div, '_7sfs-selected');

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
                    if (dojo.hasClass(div, '_7sfs-unselectable')) {
                        dojo.removeClass(div, '_7sfs-unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                this.showHandAtBottom();

                if (this.clientStateArgs.performerId)
                {
                    performer = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${performer.divId}_image`);
                    this.clearCardAsSelectable(image);
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
                    dojo.removeClass(div, '_7sfs-selectable');
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
                    if (dojo.hasClass(div, '_7sfs-unselectable')) {
                        dojo.removeClass(div, '_7sfs-unselectable');
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
                let performer = this.cardProperties[this.clientStateArgs.performerId];
                const performerImage = $(`${performer.divId}_image`);
                dojo.removeClass(performerImage, '_7sfs-chosen');

                let card = this.cardProperties[this.clientStateArgs.chosenAttachmentId];
                const image = $(`${card.divId}_image`);
                dojo.removeClass(image, '_7sfs-chosen');

                const cost = $(`${card.divId}_wealth_cost`);
                cost.innerHTML = card.wealthCost;
                dojo.removeClass(cost, '_7sfs-discounted-wealth-cost');

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
                    if (dojo.hasClass(div, '_7sfs-unselectable')) {
                        dojo.removeClass(div, '_7sfs-unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
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
                    if (dojo.hasClass(div, '_7sfs-unselectable')) 
                        {
                        dojo.removeClass(div, '_7sfs-unselectable');
                        dojo.destroy(`${div}_wealth_cost`);
                    }
                });
                this.factionHand.setSelectionMode(0);
                $('faction_hand_info').innerHTML = '';
                    this.showHandAtBottom();
            }
        }
    };

    if (methods[stateName])
        methods[stateName]();
    
    this.onLeavingState_7s5s( stateName );

    this.selectedCityLocations = [];
    this.selectedCards = [];

    //Disconnect any connect handlers that were created
    this.connects.forEach((handle) => {
        dojo.disconnect(handle);
    });
},

})
});
