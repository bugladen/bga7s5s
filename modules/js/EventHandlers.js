define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.eventhandlers', null, {

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
        if (this.selectedCityLocations.length === this.numberOfCityLocationsSelectable) {
            dojo.removeClass('actCityLocationsSelected', 'disabled');
        } else {
            dojo.addClass('actCityLocationsSelected', 'disabled');
        }
    },

    onCharacterClicked: function( event )
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
            this.selectedCharacters = this.selectedCharacters.filter((char) => char !== card.id);
        } 
        else if (this.selectedCharacters.length == this.numberOfCharactersSelectable == 1)
        {
            this.selectedCharacters.forEach((unsetId) => {
                unsetCard = this.cardProperties[unsetId];
                unsetImageElement = dojo.query('.card', unsetCard.divId)[0];
                dojo.removeClass(unsetImageElement, 'selected');
            });
            this.selectedCharacters = [];

            dojo.addClass(image, 'selected');
            this.selectedCharacters.push(card.id);
        }
        else if (this.selectedCharacters.length < this.numberOfCharactersSelectable) {
            dojo.addClass(image, 'selected');
            this.selectedCharacters.push(card.id);
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCharacters.length === this.numberOfCharactersSelectable) {
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