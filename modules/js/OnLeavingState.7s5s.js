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
    return declare('seventhseacityoffivesails.onleavingstate_7s5s', null, {
    
    // 7s5s Core Set methods only
    onLeavingState_7s5s: function( stateName )
    {

        const methods = {

            'setupTable_01006': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
            'setupTable_01006_2': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
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
                            const image = dojo.query('._7sfs-card', card.divId)[0];
                            this.clearCardAsSelectable(image);
                        }
                    }
                }
            },
    
            'planningPhaseResolveSchemes_01126': () => {
                this.resetCityLocations();
            },
    
            'planningPhaseResolveSchemes_01126_2': () => {
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
    
            'planningPhaseResolveSchemes_01145_2': () => {
                this.resetCityLocations();
            },
    
            'planningPhaseResolveSchemes_01147' : () => {
                //Exposed to all players
                let card = this.cardProperties[this.clientStateArgs.letsHaggleId];
                let image = $(`${card.divId}_image`);
                dojo.removeClass(image, '_7sfs-chosen');
    
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },
    
            'planningPhaseResolveSchemes_01150': () => {
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
                            dojo.removeClass(cost, '_7sfs-discounted-wealth-cost');
                        }
                    }
                }
            },
    
            'highDramaBeginning_01144_2': () => {
                this.unhighlightCharacterChosen(this.clientStateArgs.mercenaryId);
                
                this.factionHand.setSelectionMode(0);
                this.clientStateArgs = {};
                $('faction_hand_info').innerHTML = '';
            },

            'highDramaPhase01008': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
            'highDramaPhase01008_4': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },

            'highDramaPhase01011': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01012': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01015': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
    
            'highDramaPhase01017': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01019': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01020': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01024': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase01025': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01026': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01028': () => {
                this.resetCityLocations();
            },
            'highDramaPhase01028_2': () => {
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

            'highDramaPhase01030': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },


            'highDramaPhase01034': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },
            'highDramaPhase01034_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.targetId);
                    this.clientStateArgs = {};
                }
            },
    
            'highDramaPhase01035' : () => {
                //Exposed to all players
                let card = this.cardProperties[this.clientStateArgs.kasparId];
                let image = $(`${card.divId}_image`);
                dojo.removeClass(image, '_7sfs-chosen');
    
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },
    
            'highDramaPhase01035_3' : () => {
                //Exposed to all players
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },
    
            'highDramaPhase01035_4' : () => {
                //Exposed to all players
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },

            'highDramaPhase01038' : () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },
            'highDramaPhase01038_3' : () => {
                if (this.isCurrentPlayerActive()) 
                    {
                        dojo.addClass('choose_container', 'hidden');
                        dojo.addClass('chooseList', 'hidden');
                        this.chooseList.removeAll();
                        this.chooseList.setSelectionMode(0);
                    }
            },

            'highDramaPhase01041': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },
    
            'highDramaPhase01044' : () => 
            {
                if (this.isCurrentPlayerActive()) 
                {
                    let card = this.cardProperties[this.clientStateArgs.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },
            'highDramaPhase01044_2' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    let card = this.cardProperties[this.clientStateArgs.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
    
                    this.clientStateArgs.ids.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
            'highDramaPhase01044_3' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    let card = this.cardProperties[this.clientStateArgs.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
    
                    card = this.cardProperties[this.clientStateArgs.opposingCharacterId];
                    const opposingCharacterImage = $(`${card.divId}_image`);
                    dojo.removeClass(opposingCharacterImage, '_7sfs-chosen');
                }
            },

            'highDramaPhase01046a': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01049': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01055': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01055_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01056': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01058': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01059': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01060': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },
            'highDramaPhase01060_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
            'highDramaPhase01060_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01064': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'highDramaPhase01064_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01068': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01068_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'highDramaPhase01069': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                    $('faction_hand_info').innerHTML = '';

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);   
                }
            },

            'highDramaPhase01069_2': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
                
            'highDramaPhase01072' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.clientStateArgs.targetCardIds.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');
                }
            },
    
            'highDramaPhase01072_2' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    if (this.clientStateArgs.chosenCardId)
                    {
                        card = this.cardProperties[this.clientStateArgs.chosenCardId];
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, '_7sfs-chosen');
                    }
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');
    
                    this.showApproachDeckAtBottom();
                    this.approachDeck.setSelectionMode(0);
    
                    items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        card = this.cardProperties[item.id];
                        if (card.type === 'Scheme') {
                            let div = this.approachDeck.getItemDivId(item.id);
                            dojo.removeClass(div, '_7sfs-unselectable');
                        }
                    });
                }
            },

            'highDramaPhase01076': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01076_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });

                    card = this.cardProperties[this.clientStateArgs.performerId]; 
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },

            'highDramaPhase01081': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');

                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01085': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01086': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'highDramaPhase01111': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },
            'highDramaPhase01111_3': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },
    
            'highDramaPhase01091': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },
            'highDramaPhase01091_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};

                    this.factionHand.setSelectionMode(0);
                }
            },

            'highDramaPhase01092': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01093': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01096': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01097': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01102': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'highDramaPhase01095': () => {
                this.factionHand.setSelectionMode(0);
            },

            'highDramaPhase01104': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01105': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01106_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase01112': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01113_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);

                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },
            'highDramaPhase01113_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
    
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

            'highDramaPhase01115': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01118': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01123': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01124': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);

                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase01133': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01133_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCharacterChosen(this.clientStateArgs.characterId);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01134': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                }
            },
            'highDramaPhase01134_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);

                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },
            'highDramaPhase01134_3': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);

                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                    
                    delete this.addSortTagToCard.order;
                }
            },
            'highDramaPhase01134_4': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                }
            },

            'highDramaPhase01138': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01147': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    let performer = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${performer.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
    
                    this.clientStateArgs.attachmentsInPlay.forEach((attachmentId) => {
                        let card = this.cardProperties[attachmentId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01148': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
            'highDramaPhase01148_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    if (card)
                    {
                        const targetImage = $(`${card.divId}_image`);
                        dojo.removeClass(targetImage, '_7sfs-chosen');
                    }

                    this.factionHand.setSelectionMode(0);
                }
            },
            'highDramaPhase01148_4': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    if (card)
                    {
                        const targetImage = $(`${card.divId}_image`);
                        dojo.removeClass(targetImage, '_7sfs-chosen');
                    }

                    this.factionHand.setSelectionMode(0);
                }
            },
    
            'highDramaPhase01149': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.resetCityLocations();
    
                    let card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, '_7sfs-chosen');
                }
            },

            'highDramaPhase01152a': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01152b': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'highDramaPhase01154': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase01156': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                    $('faction_hand_info').innerHTML = '';

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);   
                }
            },
            'highDramaPhase01156_2': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    this.clientStateArgs.charactersIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
            'highDramaPhase01156_3': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01158': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'highDramaPhase01160': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCards(this.clientStateArgs.ids);
                }
            },

            'highDramaPhase01161': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCards(this.clientStateArgs.ids);
                }
            },

            'highDramaPhase01162': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'highDramaPhase01164': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'highDramaPhase01167_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);

                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'highDramaPhase01167_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);

                    this.factionHand.setSelectionMode(0);

                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                }
            },


            'highDramaPhase01171': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'highDramaPhase01172': () => {
                if (this.isCurrentPlayerActive()) 
                    {
                        this.unhighlightCharacterChosen(this.clientStateArgs.performerId);
                        this.unhighlightCards(this.clientStateArgs.ids);
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
                    $('faction_hand_info').innerHTML = '';

                    card = this.cardProperties[this.clientStateArgs.id];
                    const image = $(`${card.divId}_image`);
                    this.clearCardAsSelectable(image);   
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
                    dojo.removeClass(chosenImage, '_7sfs-chosen');
                }
            },
    
            'highDramaPhase01197_3': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    card = this.cardProperties[this.clientStateArgs.chosenCharacterId];
                    const chosenImage = $(`${card.divId}_image`);
                    dojo.removeClass(chosenImage, '_7sfs-chosen');
    
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
                    dojo.removeClass(victimImage, '_7sfs-chosen');
                }
            },


            'highDramaChallengeActionResolveTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },

            'duelNewRound_01090': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);

                    this.factionHand.setSelectionMode(0);
                }
            },

            'duelChooseTechnique_01036': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'duelChooseTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, '_7sfs-chosen');
                }
            },

            'duelChooseTechnique_01090': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
            },

            'duelChooseTechnique_01093': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'duelResolveManeuver_01051': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },

            'duelResolveManeuver_01059': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, '_7sfs-chosen');
                }
            },

            'duelResolveManeuver_01077': () => {
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
            },
            'duelResolveManeuver_01077_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
                }
            },

            'duelResolveManeuver_01108': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'duelResolveManeuver_01113_2': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
    
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

            'duelResolveManeuver_01115': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.factionHand.setSelectionMode(0);
                }
            },

            'duelResolveManeuver_01133': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'duelResolveManeuver_01164': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                }
            },

            'duelResolveManeuver_01200_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                }
            },

            'duelEndOfRound_01031': () => {
                if (this.isCurrentPlayerActive()) {
                    this.unhighlightCards(this.clientStateArgs.ids);
                    this.clientStateArgs = {};
                }
            },

            'duelEndOfRound_01200_2': () => {
                if (this.isCurrentPlayerActive()) {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
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

                    delete this.addSortTagToCard.order;
                }
            },   
                
        };

        if ( methods[stateName] )
            methods[stateName]();
    }

});
});