// console.log( 'UserNotices Merge Tags Plugin Loaded' );

/**
 * Merge Tags Button
 */
tinymce.PluginManager.add( 'usernotices_mergetags_plugin', function( editor, url ) { 
    editor.addButton( 'usernotices_mergetags_button', {
        text: 'Merge Tags',
        icon: false,
        classes: 'widget btn usernotices-mergetags-button',
        type: 'menubutton',
        menu: buildMergeTagsMenu( editor )
    } );

    function buildMergeTagsMenu( editor ) {
        if ( !window.user_notices_edit || !window.user_notices_edit.merge_tags ) {
            console.error( 'UserNotices Merge Tags: No merge tags data found.' );
            return [];
        }

        var mergeTags = window.user_notices_edit.merge_tags;
        var menu = [];

        Object.keys( mergeTags ).forEach( function( prefix ) {
            var group = mergeTags[ prefix ];
            var submenuItems = [];

            Object.keys( group.keys ).forEach( function( key ) {
                submenuItems.push( {
                    text: group.keys[ key ],
                    onclick: function() {
                        editor.insertContent( '{' + prefix + ':' + key + '}' );
                    }
                } );
            } );

            menu.push( {
                text: group.label,
                menu: submenuItems
            } );
        } );

        return menu;
    }
} );
