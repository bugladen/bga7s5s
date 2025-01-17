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

    onChooseCardClicked: function()
    {
        if (this.chooseList.getSelectedItems().length === 1) {
            dojo.removeClass('actChooseCardSelected', 'disabled');
        } else {
            dojo.addClass('actChooseCardSelected', 'disabled');
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
                $('faction_hand_info').innerHTML = items.length > 0 ? `(${wealth} Wealth worth of cards selected)` : '';
            },

            'highDramaRecruitActionChooseMercenary_client': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = items.length > 0 ? `(${wealth} Wealth worth of cards selected)` : '';
            },

            'highDramaEquipActionChooseAttachmentFromHand_client': () => {
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

            'highDramaEquipActionPayForAttachmentFromHand_client': () => {
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
                $('faction_hand_info').innerHTML = `(${wealth} Wealth worth of cards selected)`;
            },

            'highDramaEquipActionPayForAttachmentFromPlay_client': () => {
                var items = this.factionHand.getSelectedItems();
                let wealth = 0;
                items.forEach((item) => {
                    const card = this.cardProperties[item.type];
                    wealth += card.traits.includes('Wealth') ? 2 : 1;
                    
                });
                $('faction_hand_info').innerHTML = `(${wealth} Wealth worth of cards selected)`;
            }

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