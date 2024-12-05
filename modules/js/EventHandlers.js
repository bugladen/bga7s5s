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
        else 
        {
            if (this.selectedCityLocations.length < this.numberOfCityLocationsSelectable) {
                dojo.addClass(location, 'selected');
                this.selectedCityLocations.push(location);
            }
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
        while (id === '') {
            id = event.target.parentElement.id;
        }
        const card = this.getCardPropertiesByDivId(id);
        const imageElement = dojo.query('.card', card.divId)[0];
        if (dojo.hasClass(imageElement, 'selected')) 
        {
            dojo.removeClass(imageElement, 'selected');
            this.selectedCharacters = this.selectedCharacters.filter((char) => char !== card.id);
        } 
        else 
        {
            if (this.selectedCharacters.length < this.numberOfCharactersSelectable) {
                dojo.addClass(imageElement, 'selected');
                this.selectedCharacters.push(card.id);
            }
        }

        //Enable the confirm button if we have the right number of locations selected
        if (this.selectedCharacters.length === this.numberOfCharactersSelectable) {
            dojo.removeClass('actChooseCardSelected', 'disabled');
        } else {
            dojo.addClass('actChooseCardSelected', 'disabled');
        }
    },

})
});