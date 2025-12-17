/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * modules\js\EventHandlers.js
 *
 * Event handlers for SeventhSeaCityOfFiveSails
 * 
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.eventhandlers', null, {

    onApproachCardClicked: function( control_name, item_id )
    {
        const methods = {
            'planningPhase': () => {
                var items = this.approachDeck.getSelectedItems();
                // Grab the type of card from the properties cache and make sure we are only selecting 1 of each type
                const selectedType = this.cardProperties[item_id].type;        
                items.forEach((item) => {
                    const type = this.cardProperties[item.id].type;            
                    if (selectedType === type && item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                });

                //Get a count of available schemes and characters
                let schemeCount = 0;
                let characterCount = 0;
                items = this.approachDeck.getAllItems();
                items.forEach((item) => {
                    card = this.cardProperties[item.id];
                    if (card.type === 'Scheme') {
                        schemeCount++;
                    } else if (card.type === 'Character') {
                        characterCount++;
                    }
                });

                let schemeSelected = false;
                let characterSelected = false;
                items = this.approachDeck.getSelectedItems();
                items.forEach((item) => {
                    const type = this.cardProperties[item.id].type;            
                    if (selectedType === type && item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                    
                    if (type === 'Scheme') {
                        schemeSelected = true;
                    } else if (type === 'Character') {
                        characterSelected = true;
                    }
                });
        
                // Enable the confirm button if we have 2 cards selected or there are no schemes or characters left
                if (schemeSelected && characterSelected || 
                    (schemeCount === 0 && characterSelected) || 
                    (schemeSelected && characterCount === 0)) {
                    dojo.removeClass('actEndPlanningPhase', 'disabled');
                } else {
                    dojo.addClass('actEndPlanningPhase', 'disabled');
                }
            },

            'highDramaPhase01072_2': () => {
                var items = this.approachDeck.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.approachDeck.unselectItem(item.id);
                    }
                });
                if (item_id)
                {
                    const card = this.cardProperties[item_id];
                    if (card.type === 'Scheme') {
                        this.approachDeck.unselectItem(item_id);
                    }
                }

                if (this.approachDeck.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            }
        };

        if (methods[this.gamedatas.gamestate.name])
            methods[this.gamedatas.gamestate.name]();
    },

    onChooseCardClicked: function(control_name, item_id) 
    {
        const methods = {
            'highDramaPhase01038_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if (card.type !== 'Attachment') {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01111': () => {
                if (item_id === undefined) return;

                if (this.chooseList.getSelectedItems().length == 3) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01134_2': () => {
                const performerId = this.clientStateArgs.performerId;                
                const performer = this.cardProperties[performerId];
                const maxSelected = performer.modifiedInfluence;

                if (this.chooseList.getSelectedItems().length > maxSelected) {
                    this.chooseList.unselectItem(item_id);
                }
                
                if (this.chooseList.getSelectedItems().length <= maxSelected) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01134_3': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }                
            },

            'highDramaPhase01180_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if ( ! card.traits.includes('Artifact')) {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'highDramaPhase01192_3': () => {
                if (item_id === undefined) return;
                var items = this.chooseList.getSelectedItems();
                items.forEach((item) => {
                    if (item.id != item_id) {
                        this.chooseList.unselectItem(item.id);
                    }
                });
                const card = this.cardProperties[item_id];
                if (card.type !== 'Risk') {
                    this.chooseList.unselectItem(item_id);
                }

                if (this.chooseList.getSelectedItems().length === 1) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            },

            'duskPhaseBegin01177_2': () => {
                this.addSortTagToCard(item_id);

                if (this.chooseList.getSelectedItems().length === this.chooseList.getAllItems().length) {
                    dojo.removeClass('actChooseCardSelected', 'disabled');
                } else {
                    dojo.addClass('actChooseCardSelected', 'disabled');
                }
            }
        };

        if (methods[this.gamedatas.gamestate.name])
            methods[this.gamedatas.gamestate.name]();
        else
        {
            if (this.chooseList.getSelectedItems().length === 1) {
                dojo.removeClass('actChooseCardSelected', 'disabled');
            } else {
                dojo.addClass('actChooseCardSelected', 'disabled');
            }
        }
    },

    onFactionCardClicked: function( control_name, item_id )
    {
        const methods = {

            'highDramaBeginning_01144_2': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });

                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaPhase01180_5': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });

                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaRecruitActionPayForMercenary': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },

            'highDramaEquipActionChooseAttachmentFromHand': () => {
                var items = this.factionHand.getSelectedItems();
                items.forEach((item) => {
                    this.factionHand.unselectItem(item.id);
                });                
                const type = this.cardProperties[item_id]?.type;
                if (type == 'Attachment')
                    this.factionHand.selectItem(item_id);

                // Enable the confirm button if we have a card selected
                items = this.factionHand.getSelectedItems();
                if (items.length === 1) {
                    dojo.removeClass('actFactionCardsSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardsSelected', 'disabled');
                }
            },

            'highDramaEquipActionPayForAttachmentFromHand': () => {
                this.payForCard(item_id);
            },

            'highDramaEquipActionPayForAttachmentFromPlay': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                var translated = dojo.string.substitute(
                    _("(${wealth} Wealth worth of cards selected)"),
                    {
                        wealth: wealth
                    }
                );
                $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
            },
            
            'highDramaInHandActionPay': () => {
                this.payForCard(item_id);
            },

            'highDramaBruteActionChooseBrute': () => {
                var items = this.factionHand.getSelectedItems();
                items.forEach((item) => {
                    this.factionHand.unselectItem(item.id);
                });                
                const type = this.cardProperties[item_id]?.type;
                if (type == 'Character' && this.cardProperties[item_id].traits.includes('Brute'))
                    this.factionHand.selectItem(item_id);

                // Enable the confirm button if we have a card selected
                items = this.factionHand.getSelectedItems();
                if (items.length === 1) {
                    dojo.removeClass('actFactionCardsSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardsSelected', 'disabled');
                }
            },

            'highDramaBruteActionPayForBrute': () => {
                this.payForCard(item_id);
            },


            'highDramaPhase01064': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01069': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },
            
            'highDramaPhase01091_2': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actFactionCardSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardSelected', 'disabled');
                }
            },

            'highDramaPhase01095': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01102': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01113_3': () => {
                this.payForCard(item_id);
            },

            'highDramaPhase01148_3': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actFactionCardSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardSelected', 'disabled');
                }
            },

            'highDramaPhase01156': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'highDramaPhase01158': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'highDramaPhase01185': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'duelChooseAction': () => {
                if (!$('btnCombatCard'))
                    return;
                
                const items = this.factionHand.getSelectedItems();
                if (items.length === 1) {
                    dojo.removeClass('btnCombatCard', 'disabled');
                } else {
                    dojo.addClass('btnCombatCard', 'disabled');
                }
            },

            'duelPayForManeuverFromCombatCard': () => {
                this.payForCard(item_id);
            },

            'duelResolveManeuver_01108': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelResolveManeuver_01113_2': () => {
                this.payForCard(item_id);
            },

            'duelResolveManeuver_01115': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelNewRound_01090': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duelChooseTechnique_01093': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCard', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCard', 'disabled');
                }
            },

            'duskPhaseDiscard': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'playerPayForReaction': () => {
                this.payForCard(item_id);
            },


        };

        if (methods[this.gamedatas.gamestate.name]) {
            methods[this.gamedatas.gamestate.name]();
        }
    },

    onCityLocationClicked: function( event )
    {
        const location = event.target.id;
        //Check to see if we are selecting or deselecting
        if (dojo.hasClass(location, '_7sfs-selected')) 
        {
            dojo.removeClass(location, '_7sfs-selected');
            this.selectedCityLocations = this.selectedCityLocations.filter((loc) => loc !== location);
        } 
        else if (this.selectedCityLocations.length == this.numberOfCityLocationsSelectable == 1)
        {
            this.selectedCityLocations.forEach((loc) => {
                dojo.removeClass(loc, '_7sfs-selected');
            });
            this.selectedCityLocations = [];
            dojo.addClass(location, '_7sfs-selected');
            this.selectedCityLocations.push(location);
        }
        else if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable) {
            dojo.addClass(location, '_7sfs-selected');
            this.selectedCityLocations.push(location);
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCityLocations.length == this.numberOfCityLocationsSelectable) {
            dojo.removeClass('actCityLocationsSelected', 'disabled');
        } else {
            dojo.addClass('actCityLocationsSelected', 'disabled');
        }
    },

    onCardInPlayClicked: function( event )
    {
        let id = event.target.id;

        //If id does not contain '_image' then ignore, we clicked on the some element inside the image that is not the image
        if (!id.includes('_image')) { return }

        //Remove '_image' from the id to get the card divId
        const divId = id.substring(0, id.length - 6);

        const card = this.getCardPropertiesByDivId(divId);
        let image = $(id);
        if (dojo.hasClass(image, '_7sfs-selected')) 
        {
            dojo.removeClass(image, '_7sfs-selected');
            this.selectedCards = this.selectedCards.filter((char) => char !== card.id);
        } 
        else if (this.selectedCards.length == this.numberOfCardsSelectable == 1)
        {
            this.selectedCards.forEach((unsetId) => {
                unsetCard = this.cardProperties[unsetId];
                unsetImageElement = dojo.query('._7sfs-card', unsetCard.divId)[0];
                dojo.removeClass(unsetImageElement, '_7sfs-selected');
            });
            this.selectedCards = [];

            dojo.addClass(image, '_7sfs-selected');
            this.selectedCards.push(card.id);
        }
        else if (this.selectedCards.length < this.numberOfCardsSelectable) 
        {
            dojo.addClass(image, '_7sfs-selected');
            this.selectedCards.push(card.id);
        }

        //Enable the confirm button if we have at least once card selected
        if (this.selectedCards.length > 0) {
            dojo.removeClass('actChooseCardSelected', 'disabled');
        } else {
            dojo.addClass('actChooseCardSelected', 'disabled');
        }
    },

    onCityDiscardClicked: function( event )
    {
        this.myDlg = new ebg.popindialog();
        this.myDlg.create( 'discardDialog' );
        this.myDlg.setTitle( _("City Discard Pile") );
        this.myDlg.setMaxWidth( 675 );

        let cards = "";
        this.gamedatas.cityDiscard.forEach(card => {
             cards += this.format_block('jstpl_discard_card', {
                image : g_gamethemeurl + card.image,
             });
        });

        this.myDlg.setContent( cards ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
        this.myDlg.show();
    },

    onPlayerDiscardClicked: function (event)
    {
        //Get the data-player-id attribute from the element
        let playerId = $(event.target.id).getAttribute('data-player-id');
        let playerName = this.getFormattedPlayerName(playerId);

        this.myDlg = new ebg.popindialog();
        this.myDlg.create( 'discardDialog' );
        var translated = dojo.string.substitute(
            _("${playerName} Discard Pile"),
            {
                playerName: playerName
            }
        );
        this.myDlg.setTitle( translated );
        this.myDlg.setMaxWidth( 675 );

        let cards = "";
        this.gamedatas.players[playerId].discard.forEach(card => {
             cards += this.format_block('jstpl_discard_card', {
                image : g_gamethemeurl + card.image,
             });
        });

        this.myDlg.setContent( cards ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
        this.myDlg.show();
    },

    onPlayerLockerClicked: function (event)
    {
        //Get the data-player-id attribute from the element
        let playerId = $(event.target.id).getAttribute('data-player-id');
        let playerName = this.getFormattedPlayerName(playerId);

        this.myDlg = new ebg.popindialog();
        this.myDlg.create( 'discardDialog' );
        var translated = dojo.string.substitute(
            _("${playerName} Locker"),
            {
                playerName: playerName
            }
        );
        this.myDlg.setTitle( translated );
        this.myDlg.setMaxWidth( 675 );

        let cards = "";
        this.gamedatas.players[playerId].locker.forEach(card => {
             cards += this.format_block('jstpl_discard_card', {
                image : g_gamethemeurl + card.image,
             });
        });

        this.myDlg.setContent( cards ); // Must be set before calling show() so that the size of the content is defined before positioning the dialog
        this.myDlg.show();
    },

    payForCard: function(item_id)
    {
        var items = this.factionHand.getSelectedItems();
        let wealth = 0;
        const div = this.factionHand.getItemDivId(item_id);                
        if (item_id !== undefined && dojo.hasClass(div, '_7sfs-unselectable')) {
            this.factionHand.unselectItem(item_id);
            return;
        }
        items.forEach((item) => {
            const card = this.cardProperties[item.type];
            wealth += card.traits.includes('Wealth') ? 2 : 1;
            
        });
        var translated = dojo.string.substitute(
            _("(${wealth} Wealth worth of cards selected)"),
            {
                wealth: wealth
            }
        );
        $('faction_hand_info').innerHTML = items.length > 0 ? translated : '';
    },

    addSortTagToCard: function(item_id)
    {
        // Declare static var named order
        if (typeof this.addSortTagToCard.order === 'undefined') {
            this.addSortTagToCard.order = 0;
        }

        const length = this.chooseList.getAllItems().length;
        if (this.chooseList.isSelected(item_id) && this.addSortTagToCard.order < length) 
        {                    
            const div = this.chooseList.getItemDivId(item_id);

            //Set the data element to the order of the card
            $(div).setAttribute('order', ++this.addSortTagToCard.order);

            // Create a small red square html element to show that the card is selected
            dojo.place(this.format_block('jstpl_number_order_chip', {
                id: this.addSortTagToCard.order,
            }), div, 'last');
        } 
        else 
        {
            this.addSortTagToCard.order = 0;
            this.chooseList.unselectAll();

            // Remove all the red square html elements
            dojo.query('._7sfs-number-order-chip').forEach((element) => {
                //Delete the data element named order on the parent div
                element.parentNode.removeAttribute('order');                        
                
                dojo.destroy(element);
            });
        }
    },

})
});