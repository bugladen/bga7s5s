define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
    return declare('seventhseacityoffivesails.onleavingstate_7s5s', null, {
    
    // 7s5s Core Set methods only
    onLeavingState_7s5s: function( stateName )
    {

        const methods = {

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
    
            'planningPhaseResolveSchemes_01147' : () => {
                //Exposed to all players
                let card = this.cardProperties[this.clientStateArgs.letsHaggleId];
                let image = $(`${card.divId}_image`);
                dojo.removeClass(image, 'chosen');
    
                dojo.addClass('choose_container', 'hidden');
                dojo.addClass('chooseList', 'hidden');
                this.chooseList.removeAll();
                this.chooseList.setSelectionMode(0);
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
    
            'highDramaPhase01035' : () => {
                //Exposed to all players
                let card = this.cardProperties[this.clientStateArgs.kasparId];
                let image = $(`${card.divId}_image`);
                dojo.removeClass(image, 'chosen');
    
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
    
            'highDramaPhase01044' : () => 
            {
                if (this.isCurrentPlayerActive()) 
                {
                    let card = this.cardProperties[this.clientStateArgs.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
                }
            },
            'highDramaPhase01044_2' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    let card = this.cardProperties[this.clientStateArgs.actionCardId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
    
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
                    dojo.removeClass(image, 'chosen');
    
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
    
                    card = this.cardProperties[this.clientStateArgs.opposingCharacterId];
                    const opposingCharacterImage = $(`${card.divId}_image`);
                    dojo.removeClass(opposingCharacterImage, 'chosen');
                }
            },

            'highDramaPhase01046a': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');
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
                    dojo.removeClass(image, 'chosen');
                }
            },

            'highDramaPhase01055_2': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    let image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');

                    card = this.cardProperties[this.clientStateArgs.targetId];
                    image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');
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
                    dojo.removeClass(image, 'chosen');
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
                    this.showHandAtBottom();
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
                
            'highDramaPhase01072_2' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.clientStateArgs.targetCardIds.forEach((cardId) => {
                        card = this.cardProperties[cardId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, 'chosen');
                }
            },
    
            'highDramaPhase01072_3' : () => {
                if (this.isCurrentPlayerActive()) 
                {
                    if (this.clientStateArgs.chosenCardId)
                    {
                        card = this.cardProperties[this.clientStateArgs.chosenCardId];
                        const image = $(`${card.divId}_image`);
                        dojo.removeClass(image, 'chosen');
                    }
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, 'chosen');
    
                    this.showApproachDeckAtBottom();
                    this.approachDeck.setSelectionMode(0);
    
                    items = this.approachDeck.getAllItems();
                    items.forEach((item) => {
                        card = this.cardProperties[item.id];
                        if (card.type === 'Scheme') {
                            let div = this.approachDeck.getItemDivId(item.id);
                            dojo.removeClass(div, 'unselectable');
                        }
                    });
                }
            },

            'highDramaPhase01076': () => {
                if (this.isCurrentPlayerActive()) {
                    this.resetCityLocations();

                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const image = $(`${card.divId}_image`);
                    dojo.removeClass(image, 'chosen');
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
                    dojo.removeClass(performerImage, 'chosen');
                }
            },

            'highDramaPhase01081': () => {
                if (this.isCurrentPlayerActive()) {
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');

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
                    dojo.removeClass(image, 'chosen');

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
    
            'highDramaPhase01147': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    let performer = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${performer.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
    
                    this.clientStateArgs.attachmentsInPlay.forEach((attachmentId) => {
                        let card = this.cardProperties[attachmentId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                }
            },
    
            'highDramaPhase01149': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    this.resetCityLocations();
    
                    let card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
    
                    card = this.cardProperties[this.clientStateArgs.schemeId];
                    const schemeImage = $(`${card.divId}_image`);
                    dojo.removeClass(schemeImage, 'chosen');
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


            'highDramaChallengeActionActivateTechnique_01063': () => {
                if (this.isCurrentPlayerActive()) {
                    this.clientStateArgs.characterIds.forEach((characterId) => {
                        card = this.cardProperties[characterId];
                        const image = $(`${card.divId}_image`);
                        this.clearCardAsSelectable(image);
                    });
                    card = this.cardProperties[this.clientStateArgs.performerId];
                    const performerImage = $(`${card.divId}_image`);
                    dojo.removeClass(performerImage, 'chosen');
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
                    dojo.removeClass(performerImage, 'chosen');
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

            'duelResolveManeuver_01077': () => {
                if (this.isCurrentPlayerActive()) 
                {
                    dojo.addClass('choose_container', 'hidden');
                    dojo.addClass('chooseList', 'hidden');
                    this.chooseList.removeAll();
                    this.chooseList.setSelectionMode(0);
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
                
        };

        if ( methods[stateName] )
            methods[stateName]();
    }

});
});