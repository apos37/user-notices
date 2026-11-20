// console.log( 'UserNotices Background Color Plugin Loaded' );

/**
 * Background Color Button
 */
tinymce.PluginManager.add( 'usernotices_bgcolor_plugin', function( editor, url ) { 
    editor.addButton( 'usernotices_bgcolor_button', {
        text: 'Background Color',
        icon: false,
        classes: 'widget btn usernotices-bgcolor-button',
        onclick: function() {
            var body = editor.getBody();
            var hiddenField = document.getElementById( 'un-bg-color' );
            var currentColor = body.style.backgroundColor.trim();

            // Convert RGB to HEX if necessary
            if ( currentColor.startsWith( 'rgb' ) ) {
                var rgbValues = currentColor.match( /\d+/g );
                if ( rgbValues ) {
                    currentColor = '#' + 
                        ( ( 1 << 24 ) + ( parseInt( rgbValues[ 0 ] ) << 16 ) + 
                        ( parseInt( rgbValues[ 1 ] ) << 8 ) + parseInt( rgbValues[ 2 ] ) )
                        .toString( 16 ).slice( 1 ).toUpperCase();
                }
            }

            // If body color is not set, check hidden field
            if ( !currentColor || currentColor === 'transparent' ) {
                currentColor = hiddenField ? hiddenField.value.trim() : '';
            }

            // If still empty, set default
            if ( !currentColor ) {
                currentColor = 'transparent';
            }

            editor.windowManager.open( {
                title: 'Select Background Color',
                body: [
                    {
                        type: 'textbox',
                        name: 'bg_color',
                        label: 'Color',
                        subtype: 'color',
                        value: currentColor
                    }
                ],
                buttons: [
                    {
                        text: 'Cancel',
                        onclick: function() {
                            editor.windowManager.close();
                        }
                    },
                    {
                        text: 'Clear',
                        onclick: function() {
                            var body = editor.getBody();
                            body.style.backgroundColor = 'transparent';

                            var hiddenField = document.getElementById( 'un-bg-color' );
                            if ( hiddenField ) {
                                hiddenField.value = '';
                            }

                            editor.windowManager.close();
                        }
                    },
                    {
                        text: 'OK',
                        subtype: 'primary',
                        onclick: function() {
                            var data = editor.windowManager.getWindows()[0].toJSON();
                            var color = data.bg_color;

                            if ( color ) {
                                var body = editor.getBody();
                                body.style.backgroundColor = color;

                                var hiddenField = document.getElementById( 'un-bg-color' );
                                if ( hiddenField ) {
                                    hiddenField.value = color;
                                }
                            }

                            editor.windowManager.close();
                        }
                    }
                ],
            } );
        }
    } );
} );