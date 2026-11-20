jQuery( $ => {
    // console.log( 'User Notices Edit JS Loaded...' );

    // TODO: Force metakey values
    
    
    // Capture
    var postTypes = user_notices_edit.post_types;
    var siteLocationLabels = user_notices_edit.site_location_labels;
    var siteLocationType = $( '#un-site-location-type' ).val();
    var siteLocation = siteLocationType && ( siteLocationType == 'post_types' || siteLocationType == 'ids' ) ? user_notices_edit.site_location : '';
    var selectedSiteLocations = siteLocationType == 'post_types' && siteLocation ? siteLocation.split( ',' ) : [];

    var roles = user_notices_edit.roles;
    var audienceLabels = user_notices_edit.audience_labels;
    var audienceType = $( '#un-audience-type' ).val();
    var audience = audienceType && ( audienceType == 'roles' || audienceType == 'users' ) ? user_notices_edit.audience : '';
    var selectedRoles = audienceType == 'roles' && audience ? audience.split( ',' ) : [];

    // Add site location field
    addSiteLocationField( siteLocationType );
    $( '#un-site-location-type' ).on( 'change', function( e ) {
        var type = $( this ).val();
        addSiteLocationField( type, true );
    } );

    function addSiteLocationField( type, clearValue = false ) {
        var siteLocationContainer = $( '#un-site-location-cont' );
        siteLocationContainer.empty();

        if ( clearValue ) {
            siteLocation = '';
            selectedSiteLocations = [];
        }

        if ( type == 'shortcode' ) {
            $( '#screen-location-field' ).hide();
        } else {
            $( '#screen-location-field' ).show();
        }

        // Post types
        if ( type == 'post_types' ) {
            var postTypeCheckboxes = `<div id="un-site-location-post-types"><p class="desc">${siteLocationLabels.post_types}</p>`;
                $.each( postTypes, function ( key, label ) {
                    var checked = selectedSiteLocations.includes( key ) ? 'checked' : '';
                    postTypeCheckboxes += `<div class="checkboxes">
                        <input id="un-site-location-post-type-${key}" type="checkbox" name="site_location[]" value="${key}" ${checked}>
                        <label for="un-site-location-post-type-${key}">${label}</label>
                    </div>`;
                } );
            postTypeCheckboxes += `</div>`;

            siteLocationContainer.html( postTypeCheckboxes );

        // Ids
        } else if ( type == 'ids' ) {
            var idsTextField = `<div id="un-site-location-ids">
                <label for="un-site-location-ids">${siteLocationLabels.ids}:</label>
                <textarea id="un-site-location-ids" name="site_location" required>${siteLocation}</textarea>
            </div>`;

            siteLocationContainer.html( idsTextField );

        // Shortcode
        } else if ( type == 'shortcode' ) {
            var shortcodeDiv = `<div id="un-site-location-shortcode">
                <label>${siteLocationLabels.sc_label}:</label>
                <br>
                <a href="#" class="un-click-to-copy"><code class="shortcode" title="${siteLocationLabels.sc_title}">[user_notice id="${user_notices_edit.notice_id}" force_display="true"]</code></a>
            </div>`;

            siteLocationContainer.html( shortcodeDiv );
        }
    }

    // Add audience field
    addAudienceField( audienceType );
    $( '#un-audience-type' ).on( 'change', function( e ) {
        var type = $( this ).val();
        addAudienceField( type, true );
    } );

    // Site locations fields
    function addAudienceField( type, clearValue = false ) {
        var audienceContainer = $( '#un-audience' );
        audienceContainer.empty();

        if ( clearValue ) {
            audience = '';
            selectedRoles = [];
        }

        // Post types
        if ( type == 'roles' ) {
            var rolesCheckboxes = `<div id="un-audience-roles"><p class="desc">${audienceLabels.roles}</p>`;
                $.each( roles, function ( key, label ) {
                    var checked = selectedRoles.includes( key ) ? 'checked' : '';
                    rolesCheckboxes += `<div class="checkboxes">
                        <input id="un-audience-roles-${key}" type="checkbox" name="audience[]" value="${key}" ${checked}>
                        <label for="un-audience-roles-${key}">${label}</label>
                    </div>`;
                } );
            rolesCheckboxes += `</div>`;

            audienceContainer.html( rolesCheckboxes );

        // Ids
        } else if ( type == 'users' ) {
            var usersTextField = `<div id="un-audience-users">
                <label for="un-audience-users">${audienceLabels.users}:</label>
                <textarea id="un-audience-users" name="audience" required>${audience}</textarea>
            </div>`;

            audienceContainer.html( usersTextField );
        }
    }

    // Click to copy
    $( document ).on( 'click', '.un-click-to-copy', async function( e ) {
        e.preventDefault();
        var shortcodeText = $( this ).text();
        try {
            await navigator.clipboard.writeText( shortcodeText );
            alert( siteLocationLabels.sc_copied );
        } catch ( err ) {
            console.error( 'Failed to copy shortcode to clipboard: ', err );
        }
    } );
} )