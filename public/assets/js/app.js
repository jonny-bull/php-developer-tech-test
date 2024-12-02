
/**
 * Toggles show/hiding details for a match.
 * 
 * @param {Event} event A click event.
 */
const matchLinkRevealer = event => {
    const eventTarget = event.target;
    const matchContainer = eventTarget.closest( '.matches__match' );

    // Exit early if we don't have a container.
    if ( typeof( matchContainer ) === 'undefined' ) {
        return;
    }

    const matchDetails = matchContainer.querySelector( '.matches__match__details' );

    // Exit early if we don't have a details block.
    if ( typeof( matchDetails ) === 'undefined' ) {
        return;
    }

    matchDetails.classList.toggle( 'matches__match__details--hidden' );
}

const formPreventSubmit = event => {
    const formButton = event.target;
    const formParent = formButton.closest('.match-form');
    const formInputs = formParent.querySelectorAll( 'input' );

    let validForm = true;

    // Check the form is valid.
    formInputs.forEach( formInput => {
        if ( formInput.checkValidity() === false ) {
            validForm = false;
        }
    } );

    // If the form isn't valid, we don't need to worry.
    if ( validForm === false ) {
        return;
    }

    // We now have a valid form.
    // If our form click data element is zero, disable the button to prevent further clicks.
    if ( formButton.dataset.formClicks === '0' ) {
        formButton.dataset.formClicks = '1';
        formParent.submit();
        formButton.disabled = true;
        return;
    }
}

/**
 * Function that looks for single match elements.
 * In each match element, we look for a 'more' link and add an event listener to it.
 */
const matchReveal = () => {
    const matchElements = document.querySelectorAll( '.matches .matches__match' );
    
    // Exit early if there are no elements to handle.
    if ( matchElements.length === 0 ) {
        return;
    }

    matchElements.forEach( matchElement => {
        const matchElementLink = matchElement.querySelector( 'a.matches__match__more' );

        if ( typeof( matchElementLink ) !== 'undefined' ) {
            // Add an event listener if we find a link.
            matchElementLink.addEventListener( 'click', matchLinkRevealer );
        }
    } );

};

const formPrevent = () => {
    const formElements = document.querySelectorAll( 'form.match-form' );

    // Exit early if there are no elements to handle.
    if ( formElements.length === 0 ) {
        return;
    }    

    formElements.forEach( formElement => {
        const formSubmitButton = formElement.querySelector( 'input[type="submit"]' );
        formSubmitButton.addEventListener( 'click', formPreventSubmit );
    } );
}

// Run the top level functions.
matchReveal();
formPrevent();