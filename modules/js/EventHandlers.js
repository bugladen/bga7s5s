define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.eventhandlers', null, {

    onApproachCardClicked: function( control_name, item_id )
    {
        var items = this.approachDeck.getSelectedItems();
        // Grab the type of card from the properties cache and make sure we are only selecting 1 of each type
        const selectedType = this.cardProperties[item_id].type;        
        items.forEach((item) => {
            const type = this.cardProperties[item.id].type;            
            if (selectedType === type && item.id != item_id) {
                this.approachDeck.unselectItem(item.id);
            }
        });

        var items = this.approachDeck.getSelectedItems();

        // Enable the confirm button if we have 2 cards selected
        if (items.length === 2) {
            dojo.removeClass('actEndPlanningPhase', 'disabled');
        } else {
            dojo.addClass('actEndPlanningPhase', 'disabled');
        }
    },

    onChooseCardClicked: function(control_name, item_id) 
    {
        const methods = {
            'highDramaPhase01180': () => {
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

            'highDramaPhase01192': () => {
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
                // Declare static var named order
                if (typeof this.onChooseCardClicked.order === 'undefined') {
                    this.onChooseCardClicked.order = 0;
                }

                if (this.chooseList.isSelected(item_id) && this.onChooseCardClicked.order < 3) 
                {                    
                    const div = this.chooseList.getItemDivId(item_id);

                    //Set the data element to the order of the card
                    $(div).setAttribute('order', ++this.onChooseCardClicked.order);

                    // Create a small red square html element to show that the card is selected
                    dojo.place(this.format_block('jstpl_number_order_chip', {
                        id: this.onChooseCardClicked.order,
                    }), div, 'last');
                } else {
                    this.onChooseCardClicked.order = 0;
                    this.chooseList.unselectAll();

                    // Remove all the red square html elements
                    dojo.query('.number-order-chip').forEach((element) => {
                        //Delete the data element named order on the parent div
                        element.parentNode.removeAttribute('order');                        
                        
                        dojo.destroy(element);
                    });
                }

                if (this.chooseList.getSelectedItems().length === 3) {
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

            'highDramaBeginning_01144_client': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = items.length > 0 ? _(`(${wealth} Wealth worth of cards selected)`) : '';
            },

            'highDramaPhase01180_3': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
            },

            'highDramaRecruitActionPayForMercenary_client': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = items.length > 0 ? _(`(${wealth} Wealth worth of cards selected)`) : '';
            },

            'highDramaEquipActionChooseAttachmentFromHand': () => {
                var items = this.factionHand.getSelectedItems();
                const types = {};
                items.forEach((item) => {
                    const type = this.cardProperties[item.type].type;
                    if (type != 'Attachment')
                        this.factionHand.unselectItem(item.id);
                    else if (types[type])
                    {
                        this.factionHand.unselectItem(item.id);
                        this.factionHand.selectItem(item_id);
                    }
                    else
                        types[type] = true;
                });

                // Enable the confirm button if we have a card selected
                items = this.factionHand.getSelectedItems();
                if (items.length === 1) {
                    dojo.removeClass('actFactionCardsSelected', 'disabled');
                } else {
                    dojo.addClass('actFactionCardsSelected', 'disabled');
                }
            },

            'highDramaEquipActionPayForAttachmentFromHand': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                const div = this.factionHand.getItemDivId(item_id);                
                if (item_id !== undefined && dojo.hasClass(div, 'unselectable')) {
                    this.factionHand.unselectItem(item_id);
                    return;
                }
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
            },

            'highDramaEquipActionPayForAttachmentFromPlay': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
            },
            
            'highDramaInHandActionPay': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                const div = this.factionHand.getItemDivId(item_id);                
                if (item_id !== undefined && dojo.hasClass(div, 'unselectable')) {
                    this.factionHand.unselectItem(item_id);
                    return;
                }
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
            },

            'highDramaPhase01185': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'duelChooseAction': () => {
                const items = this.factionHand.getSelectedItems();
                if (items.length === 1) {
                    dojo.removeClass('btnCombatCard', 'disabled');
                } else {
                    dojo.addClass('btnCombatCard', 'disabled');
                }
            },

            'duelPayForManeuverFromCombatCard': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                const div = this.factionHand.getItemDivId(item_id);                
                if (item_id !== undefined && dojo.hasClass(div, 'unselectable')) {
                    this.factionHand.unselectItem(item_id);
                    return;
                }
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
            },

            'duskPhaseDiscard': () => {
                if (this.factionHand.getSelectedItems().length > 0) {
                    dojo.removeClass('actChooseDiscardCards', 'disabled');
                } else {
                    dojo.addClass('actChooseDiscardCards', 'disabled');
                }
            },

            'playerPayForReaction': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                const div = this.factionHand.getItemDivId(item_id);                
                if (item_id !== undefined && dojo.hasClass(div, 'unselectable')) {
                    this.factionHand.unselectItem(item_id);
                    return;
                }
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = _(`(${wealth} Wealth worth of cards selected)`);
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
        if (dojo.hasClass(location, 'selected')) 
        {
            dojo.removeClass(location, 'selected');
            this.selectedCityLocations = this.selectedCityLocations.filter((loc) => loc !== location);
        } 
        else if (this.selectedCityLocations.length == this.numberOfCityLocationsSelectable == 1)
        {
            this.selectedCityLocations.forEach((loc) => {
                dojo.removeClass(loc, 'selected');
            });
            this.selectedCityLocations = [];
            dojo.addClass(location, 'selected');
            this.selectedCityLocations.push(location);
        }
        else if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable) {
            dojo.addClass(location, 'selected');
            this.selectedCityLocations.push(location);
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCityLocations.length > 0) {
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
        if (dojo.hasClass(image, 'selected')) 
        {
            dojo.removeClass(image, 'selected');
            this.selectedCards = this.selectedCards.filter((char) => char !== card.id);
        } 
        else if (this.selectedCards.length == this.numberOfCardsSelectable == 1)
        {
            this.selectedCards.forEach((unsetId) => {
                unsetCard = this.cardProperties[unsetId];
                unsetImageElement = dojo.query('.card', unsetCard.divId)[0];
                dojo.removeClass(unsetImageElement, 'selected');
            });
            this.selectedCards = [];

            dojo.addClass(image, 'selected');
            this.selectedCards.push(card.id);
        }
        else if (this.selectedCards.length < this.numberOfCardsSelectable) {
            dojo.addClass(image, 'selected');
            this.selectedCards.push(card.id);
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCards.length === this.numberOfCardsSelectable) {
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
        this.myDlg.setTitle( _(`${playerName} Discard Pile`) );
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
        this.myDlg.setTitle( _(`${playerName} Locker`) );
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

})
});