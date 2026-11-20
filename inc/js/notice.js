jQuery( $ => {
    // console.log( 'Notice JS Loaded...' );

    // Let's check for a user-notices div
    const userNotices = $( '#user-notices' );
    if ( !userNotices ) {
        return;
    }

    // Listen for dismissing
    $( document ).on( 'click', '.usernotice-dismiss', function( e ) {
        const noticeId = $( this ).data( 'id' );
        const nonce = user_notices.nonce;
        
        $( `#user-notice-${noticeId}` ).remove();
        $( `#user-notice-backdrop-${noticeId}` ).remove();

        if ( noticeId && nonce ) {
            $.ajax( {
                url: user_notices.ajax_url,
                type: 'POST',
                data: {
                    action: 'usernotices_dismiss',
                    nonce: nonce,
                    id: noticeId
                }
            } );
        }
    } );

    // Listen for actions
    $( document ).on( 'click', '.usernotice-btn, .user-notice button[type="submit"], .user-notice input[type="submit"]', function( e ) {
        e.preventDefault();
    
        const button = $( this );
        let noticeId, form, text;
    
        if ( button.is( 'button[type="submit"], input[type="submit"]' ) ) {
            const noticeContainer = button.closest( '.user-notice' );
            noticeId = noticeContainer.data( 'id' );
            form = button.closest( 'form' );
    
            // Validate required fields
            let isValid = true;
            form.find( '[required]' ).each( function() {
                if ( !$( this ).val().trim() ) {
                    isValid = false;
                    $( this ).addClass( 'error' );
                } else {
                    $( this ).removeClass( 'error' );
                }
            } );
    
            if ( !isValid ) {
                return;
            }
    
            text = button.is( 'input[type="submit"]' ) ? button.val() : button.text();
        } else {
            noticeId = button.data( 'id' );
            text = button.text();
        }
    
        const metakey = button.data( 'metakey' );
        const url = button.attr( 'href' );
        const nonce = user_notices.nonce;
    
        if ( noticeId && nonce ) {
            $.ajax( {
                url: user_notices.ajax_url,
                type: 'POST',
                data: {
                    action: 'usernotices_actions',
                    nonce: nonce,
                    id: noticeId,
                    text: text,
                    metakey: metakey
                },
                complete: function( response, status ) {
                    // If the button was a submit button, continue submitting the form
                    if ( form ) {
                        form.closest( 'form' )[0].submit();
                    }
    
                    // Redirect if a regular button
                    if ( url && url !== '' && url !== '#' ) {
                        window.location.href = url;
                    }
    
                    // Always remove the notice
                    $( `#user-notice-${noticeId}` ).remove();
                    $( `#user-notice-backdrop-${noticeId}` ).remove();
                },
            } );
        }
    } );       
    
} )