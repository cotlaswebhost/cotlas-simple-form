( function( blocks, element, blockEditor, components ) {
    var el = element.createElement;
    var registerBlockType = blocks.registerBlockType;
    var registerBlockVariation = blocks.registerBlockVariation;
    var InspectorControls = blockEditor.InspectorControls;
    var BlockControls = blockEditor.BlockControls;
    var RichText = blockEditor.RichText;
    var MediaUpload = blockEditor.MediaUpload;
    var TextControl = components.TextControl;
    var SelectControl = components.SelectControl;
    var CheckboxControl = components.CheckboxControl;
    var TextareaControl = components.TextareaControl;
    var PanelBody = components.PanelBody;
    var Button = components.Button;
    var ToolbarGroup = components.ToolbarGroup;
    var ToolbarButton = components.ToolbarButton;
    var data = window.wp && window.wp.data ? window.wp.data : null;

    // --- FIELD BLOCK ---
    registerBlockType( 'csf/field', {
        title: 'Form Field',
        icon: 'forms',
        category: 'common',
        attributes: {
            label: { type: 'string', default: 'New Field' },
            type: { type: 'string', default: 'text' },
            required: { type: 'boolean', default: false },
            placeholder: { type: 'string', default: '' },
            helpText: { type: 'string', default: '' },
            width: { type: 'string', default: '100' },
            options: { type: 'string', default: '' },
            taxonomy: { type: 'string', default: '' },
            taxonomyShowEmpty: { type: 'boolean', default: true },
            name: { type: 'string', default: '' },
            class: { type: 'string', default: '' },
            hideLabel: { type: 'boolean', default: false },
            isHidden: { type: 'boolean', default: false },
            countryDefault: { type: 'string', default: '' },
            countryDetect: { type: 'boolean', default: false },
            customCss: { type: 'string', default: '' },
            addressLine1: { type: 'boolean', default: true },
            addressLine2: { type: 'boolean', default: true },
            addressCity: { type: 'boolean', default: true },
            addressState: { type: 'boolean', default: true },
            addressPostcode: { type: 'boolean', default: true },
            addressCountry: { type: 'boolean', default: true },
            checkboxAllowMultiple: { type: 'boolean', default: true },
            userMetaTarget: { type: 'string', default: '' },
            userMetaKey: { type: 'string', default: '' },
            postMetaTarget: { type: 'string', default: '' },
            postMetaKey: { type: 'string', default: '' },
            conditionalEnabled: { type: 'boolean', default: false },
            conditionalField: { type: 'string', default: '' },
            conditionalOperator: { type: 'string', default: 'equals' },
            conditionalValue: { type: 'string', default: '' },
            useTinyMCE: { type: 'boolean', default: false },
            useEditorJS: { type: 'boolean', default: false },
        },
        edit: function( props ) {
            var attributes = props.attributes;
            var taxOptions = (window.csfBlockVars && window.csfBlockVars.taxonomies) ? window.csfBlockVars.taxonomies.map(function(t) {
                return { label: t.label, value: t.slug };
            }) : [];
            var countryOptions = (window.csfBlockVars && window.csfBlockVars.countries) ? window.csfBlockVars.countries.map(function(c) {
                return { label: c.label, value: c.code };
            }) : [];

            function onChangeLabel( newLabel ) {
                props.setAttributes( { label: newLabel } );
                if ( ! attributes.name ) {
                    props.setAttributes( { name: newLabel.toLowerCase().replace(/[^a-z0-9]/g, '_') } );
                }
            }

            function insertAdjacentField( position ) {
                if ( ! data ) {
                    return;
                }
                var editorSelect = data.select( 'core/block-editor' );
                var editorDispatch = data.dispatch( 'core/block-editor' );
                if ( ! editorSelect || ! editorDispatch ) {
                    return;
                }
                var clientId = props.clientId;
                var parentId = editorSelect.getBlockRootClientId( clientId );
                var index = editorSelect.getBlockIndex( clientId, parentId );
                if ( index === -1 ) {
                    return;
                }
                var newBlock = blocks.createBlock( 'csf/field', {
                    type: attributes.type,
                    width: attributes.width
                } );
                var targetIndex = position === 'before' ? index : index + 1;
                editorDispatch.insertBlocks( newBlock, targetIndex, parentId );
            }

            function renderPreviewControl() {
                var placeholder = attributes.placeholder || (attributes.type === 'select' || attributes.type === 'select2' || attributes.type === 'taxonomy_select2' || attributes.type === 'country' ? 'Select an option' : 'Sample input');

                if ( attributes.type === 'textarea' ) {
                    return el( 'textarea', {
                        className: 'csf-field-preview-textarea',
                        disabled: true,
                        placeholder: placeholder,
                        rows: 3
                    } );
                }

                if ( attributes.type === 'select' || attributes.type === 'select2' || attributes.type === 'taxonomy_select2' || attributes.type === 'country' ) {
                    return el(
                        'select',
                        {
                            className: 'csf-field-preview-select',
                            disabled: true
                        },
                        el( 'option', { value: '' }, placeholder )
                    );
                }

                if ( attributes.type === 'address' ) {
                    var showLine1 = attributes.addressLine1 !== false;
                    var showLine2 = attributes.addressLine2 !== false;
                    var showCity = attributes.addressCity !== false;
                    var showState = attributes.addressState !== false;
                    var showPostcode = attributes.addressPostcode !== false;
                    var showCountry = attributes.addressCountry !== false;

                    var children = [];

                    if ( showLine1 || showLine2 ) {
                        var lineChildren = [];
                        if ( showLine1 ) {
                            lineChildren.push(
                                el(
                                    'div',
                                    { key: 'line1', style: { flex: 1 } },
                                    el( 'input', {
                                        type: 'text',
                                        className: 'csf-field-preview-input',
                                        disabled: true,
                                        placeholder: attributes.placeholder || 'Address line 1'
                                    } )
                                )
                            );
                        }
                        if ( showLine2 ) {
                            lineChildren.push(
                                el(
                                    'div',
                                    { key: 'line2', style: { flex: 1 } },
                                    el( 'input', {
                                        type: 'text',
                                        className: 'csf-field-preview-input',
                                        disabled: true,
                                        placeholder: 'Address line 2'
                                    } )
                                )
                            );
                        }
                        children.push(
                            el(
                                'div',
                                { key: 'line-row', style: { display: 'flex', gap: '8px', marginBottom: '6px' } },
                                lineChildren
                            )
                        );
                    }

                    if ( showCity || showState ) {
                        var cityStateChildren = [];
                        if ( showCity ) {
                            cityStateChildren.push(
                                el(
                                    'div',
                                    { key: 'city', style: { flex: 1 } },
                                    el( 'input', {
                                        type: 'text',
                                        className: 'csf-field-preview-input',
                                        disabled: true,
                                        placeholder: 'City'
                                    } )
                                )
                            );
                        }
                        if ( showState ) {
                            cityStateChildren.push(
                                el(
                                    'div',
                                    { key: 'state', style: { flex: 1 } },
                                    el( 'input', {
                                        type: 'text',
                                        className: 'csf-field-preview-input',
                                        disabled: true,
                                        placeholder: 'State/Region'
                                    } )
                                )
                            );
                        }
                        children.push(
                            el(
                                'div',
                                { key: 'citystate', style: { display: 'flex', gap: '8px', marginBottom: '6px' } },
                                cityStateChildren
                            )
                        );
                    }

                    if ( showPostcode || showCountry ) {
                        var zipCountryChildren = [];
                        if ( showPostcode ) {
                            zipCountryChildren.push(
                                el(
                                    'div',
                                    { key: 'postcode', style: { flex: 1 } },
                                    el( 'input', {
                                        type: 'text',
                                        className: 'csf-field-preview-input',
                                        disabled: true,
                                        placeholder: 'Postal code'
                                    } )
                                )
                            );
                        }
                        if ( showCountry ) {
                            zipCountryChildren.push(
                                el(
                                    'div',
                                    { key: 'country', style: { flex: 1 } },
                                    el(
                                        'select',
                                        {
                                            className: 'csf-field-preview-select',
                                            disabled: true
                                        },
                                        el( 'option', { value: '' }, 'Country' )
                                    )
                                )
                            );
                        }
                        children.push(
                            el(
                                'div',
                                { key: 'postcodecountry', style: { display: 'flex', gap: '8px' } },
                                zipCountryChildren
                            )
                        );
                    }

                    return el( 'div', null, children );
                }

                if ( attributes.type === 'mobile' ) {
                    return el(
                        'div',
                        null,
                        el( 'div', {
                            style: { display: 'flex', gap: '8px' }
                        },
                            el( 'input', {
                                type: 'text',
                                className: 'csf-field-preview-input',
                                disabled: true,
                                value: '+91'
                            } ),
                            el( 'input', {
                                type: 'text',
                                className: 'csf-field-preview-input',
                                disabled: true,
                                placeholder: attributes.placeholder || 'Mobile number'
                            } )
                        )
                    );
                }

                if ( attributes.type === 'checkbox_group' ) {
                    return el(
                        'div',
                        {
                            style: {
                                display: 'grid',
                                gridTemplateColumns: 'repeat(2, minmax(0, 1fr))',
                                gap: '6px 16px'
                            }
                        },
                        el(
                            'label',
                            { style: { display: 'flex', alignItems: 'center', gap: '6px' } },
                            el( 'input', {
                                type: 'checkbox',
                                disabled: true
                            } ),
                            'Option 1'
                        ),
                        el(
                            'label',
                            { style: { display: 'flex', alignItems: 'center', gap: '6px' } },
                            el( 'input', {
                                type: 'checkbox',
                                disabled: true
                            } ),
                            'Option 2'
                        )
                    );
                }

                if ( attributes.type === 'date_range' ) {
                    return el(
                        'div',
                        { style: { display: 'flex', gap: '8px' } },
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'From date'
                        } ),
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'To date'
                        } )
                    );
                }

                if ( attributes.type === 'datetime_range' ) {
                    return el(
                        'div',
                        { style: { display: 'flex', gap: '8px' } },
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'From date/time'
                        } ),
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'To date/time'
                        } )
                    );
                }

                if ( attributes.type === 'date_range' ) {
                    return el(
                        'div',
                        { style: { display: 'flex', gap: '8px' } },
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'From date'
                        } ),
                        el( 'input', {
                            type: 'text',
                            className: 'csf-field-preview-input',
                            disabled: true,
                            placeholder: 'To date'
                        } )
                    );
                }

                return el( 'input', {
                    type: 'text',
                    className: 'csf-field-preview-input',
                    disabled: true,
                    placeholder: placeholder
                } );
            }

            var widthOptions = [
                { label: '100%', value: '100' },
                { label: '75%', value: '75' },
                { label: '67%', value: '67' },
                { label: '50%', value: '50' },
                { label: '33%', value: '33' },
                { label: '25%', value: '25' }
            ];

            return el(
                'div',
                { className: props.className + ' csf-field-editor' },
                el(
                    BlockControls,
                    {},
                    el(
                        ToolbarGroup,
                        {},
                        widthOptions.map( function( opt ) {
                            return el(
                                ToolbarButton,
                                {
                                    key: opt.value,
                                    isPressed: attributes.width === opt.value,
                                    onClick: function() {
                                        props.setAttributes( { width: opt.value } );
                                    }
                                },
                                opt.label
                            );
                        } )
                    ),
                    el(
                        ToolbarGroup,
                        {},
                        el(
                            ToolbarButton,
                            {
                                onClick: function() {
                                    insertAdjacentField( 'before' );
                                }
                            },
                            'Add before'
                        ),
                        el(
                            ToolbarButton,
                            {
                                onClick: function() {
                                    insertAdjacentField( 'after' );
                                }
                            },
                            'Add after'
                        )
                    )
                ),
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Field Settings', initialOpen: true },
                        el( SelectControl, {
                            label: 'Field Type',
                            value: attributes.type,
                            options: [
                                { label: 'Text', value: 'text' },
                                { label: 'Email', value: 'email' },
                                { label: 'Password', value: 'password' },
                                { label: 'Mobile', value: 'mobile' },
                                { label: 'Number', value: 'number' },
                                { label: 'URL', value: 'url' },
                                { label: 'Time', value: 'time' },
                                { label: 'Age', value: 'age' },
                                { label: 'Date', value: 'date' },
                                { label: 'Date Range', value: 'date_range' },
                                { label: 'Date Time Range', value: 'datetime_range' },
                                { label: 'Searchable Dropdown (Select2)', value: 'select2' },
                                { label: 'Taxonomy Dropdown (Select2)', value: 'taxonomy_select2' },
                                { label: 'Textarea', value: 'textarea' },
                                { label: 'Dropdown (Select)', value: 'select' },
                                { label: 'Repeater (Text Rows)', value: 'repeater' },
                                { label: 'Checkbox', value: 'checkbox' },
                                { label: 'Checkboxes (Multiple)', value: 'checkbox_group' },
                                { label: 'File Upload', value: 'file' },
                                { label: 'Address', value: 'address' },
                                { label: 'Google Address', value: 'google_address' },
                                { label: 'Country Dropdown', value: 'country' },
                                { label: 'City (Google Places)', value: 'city_google' },
                                { label: 'State (Google Places)', value: 'state_google' },
                            ],
                            onChange: function( val ) { props.setAttributes( { type: val } ); }
                        } ),
                        el( CheckboxControl, {
                            label: 'Required',
                            checked: attributes.required,
                            onChange: function( val ) { props.setAttributes( { required: val } ); }
                        } ),
                        el( TextareaControl, {
                            label: 'Help Text',
                            value: attributes.helpText,
                            onChange: function( val ) { props.setAttributes( { helpText: val } ); }
                        } ),
                        el( CheckboxControl, {
                            label: 'Hide Label',
                            checked: attributes.hideLabel,
                            onChange: function( val ) { props.setAttributes( { hideLabel: val } ); }
                        } ),
                        el( CheckboxControl, {
                            label: 'Honeypot (Hidden Field)',
                            help: 'If checked, this field will be hidden and must remain empty to submit.',
                            checked: attributes.isHidden,
                            onChange: function( val ) { props.setAttributes( { isHidden: val } ); }
                        } ),
                        el( TextControl, {
                            label: 'Placeholder',
                            help: 'You can use {post_title} to dynamically insert the current page title.',
                            value: attributes.placeholder,
                            onChange: function( val ) { props.setAttributes( { placeholder: val } ); }
                        } ),
                        attributes.type === 'textarea' && el( CheckboxControl, {
                            label: 'Use TinyMCE Editor',
                            help: 'Render as a rich text editor instead of a plain textarea.',
                            checked: attributes.useTinyMCE,
                            onChange: function( val ) { props.setAttributes( { useTinyMCE: val } ); }
                        } ),
                        attributes.type === 'textarea' && el( CheckboxControl, {
                            label: 'Use Block Editor (Editor.js)',
                            help: 'Render as a modern block editor instead of a plain textarea.',
                            checked: attributes.useEditorJS,
                            onChange: function( val ) { props.setAttributes( { useEditorJS: val } ); }
                        } ),
                        ( attributes.type === 'address' || attributes.type === 'google_address' ) && el(
                            PanelBody,
                            { title: 'Address Fields', initialOpen: true },
                            el( CheckboxControl, {
                                label: 'Address line 1',
                                checked: attributes.addressLine1 !== false,
                                onChange: function( val ) { props.setAttributes( { addressLine1: val } ); }
                            } ),
                            el( CheckboxControl, {
                                label: 'Address line 2',
                                checked: attributes.addressLine2 !== false,
                                onChange: function( val ) { props.setAttributes( { addressLine2: val } ); }
                            } ),
                            el( CheckboxControl, {
                                label: 'City',
                                checked: attributes.addressCity !== false,
                                onChange: function( val ) { props.setAttributes( { addressCity: val } ); }
                            } ),
                            el( CheckboxControl, {
                                label: 'State/Region',
                                checked: attributes.addressState !== false,
                                onChange: function( val ) { props.setAttributes( { addressState: val } ); }
                            } ),
                            el( CheckboxControl, {
                                label: 'Postal code',
                                checked: attributes.addressPostcode !== false,
                                onChange: function( val ) { props.setAttributes( { addressPostcode: val } ); }
                            } ),
                            el( CheckboxControl, {
                                label: 'Country',
                                checked: attributes.addressCountry !== false,
                                onChange: function( val ) { props.setAttributes( { addressCountry: val } ); }
                            } )
                        ),
                        ( attributes.type === 'select' || attributes.type === 'select2' || attributes.type === 'radio' || attributes.type === 'checkbox_group' ) && el( TextareaControl, {
                            label: 'Options (One per line)',
                            help: 'Format: value : label (optional) or just value',
                            value: attributes.options,
                            onChange: function( val ) { props.setAttributes( { options: val } ); }
                        } ),
                        attributes.type === 'checkbox_group' && el( CheckboxControl, {
                            label: 'Allow multiple selections',
                            checked: attributes.checkboxAllowMultiple !== false,
                            onChange: function( val ) { props.setAttributes( { checkboxAllowMultiple: val } ); }
                        } ),
                        ( attributes.type === 'select' || attributes.type === 'select2' ) && el( TextControl, {
                            label: 'Or Populate from Taxonomy (slug)',
                            value: attributes.taxonomy,
                            onChange: function( val ) { props.setAttributes( { taxonomy: val } ); }
                        } ),
                        attributes.type === 'taxonomy_select2' && el( SelectControl, {
                            label: 'Taxonomy',
                            value: attributes.taxonomy,
                            options: taxOptions,
                            onChange: function( val ) { props.setAttributes( { taxonomy: val } ); }
                        } ),
                        attributes.type === 'taxonomy_select2' && el( CheckboxControl, {
                            label: 'Display empty terms',
                            checked: attributes.taxonomyShowEmpty !== false,
                            onChange: function( val ) { props.setAttributes( { taxonomyShowEmpty: val } ); }
                        } ),
                        ( attributes.type === 'country' || attributes.type === 'address' || attributes.type === 'google_address' || attributes.type === 'mobile' ) && el( SelectControl, {
                            label: 'Default Country',
                            value: attributes.countryDefault,
                            options: [ { label: 'Select country', value: '' } ].concat(countryOptions),
                            onChange: function( val ) { props.setAttributes( { countryDefault: val } ); }
                        } ),
                        ( attributes.type === 'country' || attributes.type === 'address' || attributes.type === 'google_address' || attributes.type === 'mobile' ) && el( CheckboxControl, {
                            label: 'Detect user country from IP',
                            checked: attributes.countryDetect,
                            onChange: function( val ) { props.setAttributes( { countryDetect: val } ); }
                        } ),
                        el( TextControl, {
                            label: 'Field Name (Slug)',
                            help: 'Unique identifier for saving data',
                            value: attributes.name,
                            onChange: function( val ) { props.setAttributes( { name: val } ); }
                        } ),
                        el( SelectControl, {
                            label: 'User meta mapping',
                            help: 'Tie this field to a WordPress user field (for login/registration forms).',
                            value: attributes.userMetaTarget,
                            options: [
                                { label: '— No mapping —', value: '' },
                                { label: 'Username (user_login)', value: 'user_login' },
                                { label: 'Email (user_email)', value: 'user_email' },
                                { label: 'Password', value: 'user_pass' },
                                { label: 'First name', value: 'first_name' },
                                { label: 'Last name', value: 'last_name' },
                                { label: 'Display name', value: 'display_name' },
                                { label: 'Website (user_url)', value: 'user_url' },
                                { label: 'Biographical info (description)', value: 'description' },
                                { label: 'Custom user meta key', value: 'meta' },
                            ],
                            onChange: function( val ) { props.setAttributes( { userMetaTarget: val } ); }
                        } ),
                        attributes.userMetaTarget === 'meta' && el( TextControl, {
                            label: 'Custom meta key',
                            help: 'user_meta key, e.g. instagram, linkedin_url, whatsapp_number',
                            value: attributes.userMetaKey,
                            onChange: function( val ) { props.setAttributes( { userMetaKey: val } ); }
                        } ),
                        el( SelectControl, {
                            label: 'Post field mapping',
                            help: 'Tie this field to a WordPress post field (for Add Post forms).',
                            value: attributes.postMetaTarget,
                            options: [
                                { label: '— No mapping —', value: '' },
                                { label: 'Post Title', value: 'post_title' },
                                { label: 'Post Excerpt', value: 'post_excerpt' },
                                { label: 'Featured Image', value: 'featured_image' },
                                { label: 'Post Categories', value: 'post_category' },
                                { label: 'Post Tags', value: 'post_tags' },
                                { label: 'Post Content', value: 'post_content' },
                                { label: 'Custom post meta key', value: 'meta' },
                            ],
                            onChange: function( val ) { props.setAttributes( { postMetaTarget: val } ); }
                        } ),
                        attributes.postMetaTarget === 'meta' && el( TextControl, {
                            label: 'Custom post meta key',
                            help: 'post meta key, e.g. price, location, color',
                            value: attributes.postMetaKey,
                            onChange: function( val ) { props.setAttributes( { postMetaKey: val } ); }
                        } ),
                        el( TextControl, {
                            label: 'Custom CSS Class',
                            value: attributes.class,
                            onChange: function( val ) { props.setAttributes( { class: val } ); }
                        } )
                    ),
                    el( PanelBody, { title: 'Conditional logic', initialOpen: false },
                        el( CheckboxControl, {
                            label: 'Enable conditional logic',
                            checked: attributes.conditionalEnabled,
                            onChange: function( val ) { props.setAttributes( { conditionalEnabled: val } ); }
                        } ),
                        attributes.conditionalEnabled && el( TextControl, {
                            label: 'Depends on field name',
                            help: 'Enter the Field Name (Slug) of the controlling field, e.g. country',
                            value: attributes.conditionalField,
                            onChange: function( val ) { props.setAttributes( { conditionalField: val } ); }
                        } ),
                        attributes.conditionalEnabled && el( SelectControl, {
                            label: 'Condition',
                            value: attributes.conditionalOperator,
                            options: [
                                { label: 'Equals', value: 'equals' },
                                { label: 'Not equals', value: 'not_equals' }
                            ],
                            onChange: function( val ) { props.setAttributes( { conditionalOperator: val } ); }
                        } ),
                        attributes.conditionalEnabled && el( TextControl, {
                            label: 'Value',
                            help: 'Show this field when the other field value matches this. For dropdowns use the option value (e.g. IN).',
                            value: attributes.conditionalValue,
                            onChange: function( val ) { props.setAttributes( { conditionalValue: val } ); }
                        } )
                    ),
                    el( PanelBody, { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            help: 'CSS applied on frontend; use your field classes as selectors.',
                            value: attributes.customCss,
                            onChange: function( val ) { props.setAttributes( { customCss: val } ); }
                        } )
                    )
                ),
                el(
                    'div',
                    { className: 'csf-field-preview' },
                    el( RichText, {
                        tagName: 'div',
                        className: 'csf-field-preview-label',
                        value: attributes.label,
                        onChange: onChangeLabel,
                        placeholder: 'Field label'
                    } ),
                    attributes.helpText && attributes.helpText.length
                        ? el(
                            'div',
                            { className: 'csf-field-preview-help' },
                            attributes.helpText
                        )
                        : null,
                    renderPreviewControl()
                )
            );
        },
        save: function() {
            return null;
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-text',
        title: 'Text',
        icon: 'editor-textcolor',
        scope: [ 'inserter' ],
        attributes: {
            type: 'text',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-email',
        title: 'Email',
        icon: 'email',
        scope: [ 'inserter' ],
        attributes: {
            type: 'email',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-password',
        title: 'Password',
        icon: 'lock',
        scope: [ 'inserter' ],
        attributes: {
            type: 'password',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-mobile',
        title: 'Phone',
        icon: 'phone',
        scope: [ 'inserter' ],
        attributes: {
            type: 'mobile',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-number',
        title: 'Number',
        icon: 'editor-ol',
        scope: [ 'inserter' ],
        attributes: {
            type: 'number',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-url',
        title: 'URL',
        icon: 'admin-links',
        scope: [ 'inserter' ],
        attributes: {
            type: 'url',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-time',
        title: 'Time',
        icon: 'clock',
        scope: [ 'inserter' ],
        attributes: {
            type: 'time',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-age',
        title: 'Age',
        icon: 'universal-access-alt',
        scope: [ 'inserter' ],
        attributes: {
            type: 'age',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-textarea',
        title: 'Textarea',
        icon: 'editor-paragraph',
        scope: [ 'inserter' ],
        attributes: {
            type: 'textarea',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-select',
        title: 'Dropdown',
        icon: 'arrow-down-alt2',
        scope: [ 'inserter' ],
        attributes: {
            type: 'select',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-select2',
        title: 'Searchable Dropdown',
        icon: 'search',
        scope: [ 'inserter' ],
        attributes: {
            type: 'select2',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-taxonomy-select2',
        title: 'Taxonomy Dropdown',
        icon: 'category',
        scope: [ 'inserter' ],
        attributes: {
            type: 'taxonomy_select2',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-city-google',
        title: 'City (Google Places)',
        icon: 'location-alt',
        scope: [ 'inserter' ],
        attributes: {
            type: 'city_google',
            label: 'City',
            name: 'city',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-state-google',
        title: 'State (Google Places)',
        icon: 'location-alt',
        scope: [ 'inserter' ],
        attributes: {
            type: 'state_google',
            label: 'State',
            name: 'state',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-checkbox',
        title: 'Checkbox',
        icon: 'yes',
        scope: [ 'inserter' ],
        attributes: {
            type: 'checkbox',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-checkbox-group',
        title: 'Checkboxes (Multiple)',
        icon: 'yes',
        scope: [ 'inserter' ],
        attributes: {
            type: 'checkbox_group',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-date',
        title: 'Date',
        icon: 'calendar',
        scope: [ 'inserter' ],
        attributes: {
            type: 'date',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-date-range',
        title: 'Date Range',
        icon: 'calendar',
        scope: [ 'inserter' ],
        attributes: {
            type: 'date_range',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-datetime-range',
        title: 'Date Time Range',
        icon: 'calendar',
        scope: [ 'inserter' ],
        attributes: {
            type: 'datetime_range',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-file',
        title: 'File Upload',
        icon: 'download',
        scope: [ 'inserter' ],
        attributes: {
            type: 'file',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-address',
        title: 'Address',
        icon: 'location-alt',
        scope: [ 'inserter' ],
        attributes: {
            type: 'address',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-google-address',
        title: 'Google Address',
        icon: 'location-alt',
        scope: [ 'inserter' ],
        attributes: {
            type: 'google_address',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-country',
        title: 'Country Dropdown',
        icon: 'admin-site-alt3',
        scope: [ 'inserter' ],
        attributes: {
            type: 'country',
        },
    } );

    registerBlockVariation( 'csf/field', {
        name: 'csf-field-honeypot',
        title: 'Honeypot',
        icon: 'hidden',
        scope: [ 'inserter' ],
        attributes: {
            type: 'text',
            isHidden: true,
            label: 'Leave this field empty',
        },
    } );

    registerBlockType( 'csf/heading', {
        title: 'Heading',
        icon: 'heading',
        category: 'common',
        attributes: {
            content: { type: 'string', default: '' },
            customCss: { type: 'string', default: '' },
            level: { type: 'string', default: '' },
            fontSize: { type: 'string', default: '' },
            fontWeight: { type: 'string', default: '' },
            lineHeight: { type: 'string', default: '' },
            letterSpacing: { type: 'string', default: '' },
            textTransform: { type: 'string', default: '' },
            textDecoration: { type: 'string', default: '' },
            textAlign: { type: 'string', default: '' },
        },
        edit: function( props ) {
            var attrs = props.attributes;
            var level = attrs.level || 'h3';

            return el(
                'div',
                { className: props.className + ' csf-heading-block' },
                el(
                    BlockControls,
                    {},
                    el(
                        ToolbarGroup,
                        {},
                        [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ].map( function( tag ) {
                            return el(
                                ToolbarButton,
                                {
                                    key: tag,
                                    isPressed: level === tag,
                                    onClick: function() {
                                        props.setAttributes( { level: tag } );
                                    },
                                },
                                tag.toUpperCase()
                            );
                        } )
                    )
                ),
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            value: attrs.customCss,
                            onChange: function( val ) {
                                props.setAttributes( { customCss: val } );
                            },
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: 'Typography', initialOpen: false },
                        el( TextControl, {
                            label: 'Font size (e.g. 24px)',
                            value: attrs.fontSize,
                            onChange: function( val ) {
                                props.setAttributes( { fontSize: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Font weight',
                            value: attrs.fontWeight,
                            options: [
                                { label: 'Default', value: '' },
                                { label: '300', value: '300' },
                                { label: '400', value: '400' },
                                { label: '500', value: '500' },
                                { label: '600', value: '600' },
                                { label: '700', value: '700' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { fontWeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Line height (e.g. 1.4)',
                            value: attrs.lineHeight,
                            onChange: function( val ) {
                                props.setAttributes( { lineHeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Letter spacing (e.g. 0.05em)',
                            value: attrs.letterSpacing,
                            onChange: function( val ) {
                                props.setAttributes( { letterSpacing: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text transform',
                            value: attrs.textTransform,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Uppercase', value: 'uppercase' },
                                { label: 'Lowercase', value: 'lowercase' },
                                { label: 'Capitalize', value: 'capitalize' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textTransform: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text decoration',
                            value: attrs.textDecoration,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Underline', value: 'underline' },
                                { label: 'Line through', value: 'line-through' },
                                { label: 'Overline', value: 'overline' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textDecoration: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Alignment',
                            value: attrs.textAlign,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Left', value: 'left' },
                                { label: 'Center', value: 'center' },
                                { label: 'Right', value: 'right' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textAlign: val } );
                            },
                        } )
                    )
                ),
                el(
                    RichText,
                    {
                        tagName: level,
                        className: 'csf-heading',
                        value: attrs.content,
                        onChange: function( val ) {
                            props.setAttributes( { content: val } );
                        },
                        placeholder: 'Heading text',
                    }
                )
            );
        },
        save: function( props ) {
            var attrs = props.attributes;
            var level = attrs.level || 'h3';

            return el( RichText.Content, {
                tagName: level,
                className: 'csf-heading',
                value: attrs.content,
            } );
        },
    } );

    registerBlockType( 'csf/text', {
        title: 'Text',
        icon: 'editor-paragraph',
        category: 'common',
        attributes: {
            content: { type: 'string', default: '' },
            customCss: { type: 'string', default: '' },
            fontSize: { type: 'string', default: '' },
            fontWeight: { type: 'string', default: '' },
            lineHeight: { type: 'string', default: '' },
            letterSpacing: { type: 'string', default: '' },
            textTransform: { type: 'string', default: '' },
            textDecoration: { type: 'string', default: '' },
            textAlign: { type: 'string', default: '' },
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return el(
                'div',
                { className: props.className + ' csf-text-block' },
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            value: attrs.customCss,
                            onChange: function( val ) {
                                props.setAttributes( { customCss: val } );
                            },
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: 'Typography', initialOpen: false },
                        el( TextControl, {
                            label: 'Font size (e.g. 16px)',
                            value: attrs.fontSize,
                            onChange: function( val ) {
                                props.setAttributes( { fontSize: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Font weight',
                            value: attrs.fontWeight,
                            options: [
                                { label: 'Default', value: '' },
                                { label: '300', value: '300' },
                                { label: '400', value: '400' },
                                { label: '500', value: '500' },
                                { label: '600', value: '600' },
                                { label: '700', value: '700' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { fontWeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Line height (e.g. 1.5)',
                            value: attrs.lineHeight,
                            onChange: function( val ) {
                                props.setAttributes( { lineHeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Letter spacing (e.g. 0.02em)',
                            value: attrs.letterSpacing,
                            onChange: function( val ) {
                                props.setAttributes( { letterSpacing: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text transform',
                            value: attrs.textTransform,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Uppercase', value: 'uppercase' },
                                { label: 'Lowercase', value: 'lowercase' },
                                { label: 'Capitalize', value: 'capitalize' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textTransform: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text decoration',
                            value: attrs.textDecoration,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Underline', value: 'underline' },
                                { label: 'Line through', value: 'line-through' },
                                { label: 'Overline', value: 'overline' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textDecoration: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Alignment',
                            value: attrs.textAlign,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Left', value: 'left' },
                                { label: 'Center', value: 'center' },
                                { label: 'Right', value: 'right' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textAlign: val } );
                            },
                        } )
                    )
                ),
                el(
                    RichText,
                    {
                        tagName: 'p',
                        className: 'csf-text',
                        value: attrs.content,
                        onChange: function( val ) {
                            props.setAttributes( { content: val } );
                        },
                        placeholder: 'Text content',
                    }
                )
            );
        },
        save: function( props ) {
            return el( RichText.Content, {
                tagName: 'p',
                className: 'csf-text',
                value: props.attributes.content,
            } );
        },
    } );

    registerBlockType( 'csf/image', {
        title: 'Image',
        icon: 'format-image',
        category: 'common',
        attributes: {
            url: { type: 'string', default: '' },
            alt: { type: 'string', default: '' },
            customCss: { type: 'string', default: '' },
        },
        edit: function( props ) {
            var attrs = props.attributes;

            function onSelectImage( media ) {
                props.setAttributes( { url: media.url, alt: media.alt } );
            }

            return el(
                'div',
                { className: props.className + ' csf-image-block' },
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            value: attrs.customCss,
                            onChange: function( val ) { props.setAttributes( { customCss: val } ); }
                        } )
                    )
                ),
                attrs.url && el( 'img', { src: attrs.url, alt: attrs.alt, style: { maxWidth: '100%', height: 'auto', display: 'block', marginBottom: '10px' } } ),
                el( MediaUpload, {
                    onSelect: onSelectImage,
                    allowedTypes: [ 'image' ],
                    render: function( obj ) {
                        return el(
                            Button,
                            {
                                className: 'button button-secondary',
                                onClick: obj.open,
                            },
                            attrs.url ? 'Change Image' : 'Select Image'
                        );
                    },
                } ),
                attrs.url && el( TextControl, {
                    label: 'Alt Text',
                    value: attrs.alt,
                    onChange: function( val ) { props.setAttributes( { alt: val } ); },
                } )
            );
        },
        save: function( props ) {
            if ( ! props.attributes.url ) {
                return null;
            }
            return el(
                'div',
                { className: 'csf-image' },
                el( 'img', { src: props.attributes.url, alt: props.attributes.alt } )
            );
        },
    } );

    registerBlockType( 'csf/page-heading', {
        title: 'Page Heading',
        icon: 'editor-textcolor',
        category: 'common',
        attributes: {
            content: { type: 'string', default: '' },
            customCss: { type: 'string', default: '' },
            fontSize: { type: 'string', default: '' },
            fontWeight: { type: 'string', default: '' },
            lineHeight: { type: 'string', default: '' },
            letterSpacing: { type: 'string', default: '' },
            textTransform: { type: 'string', default: '' },
            textDecoration: { type: 'string', default: '' },
            textAlign: { type: 'string', default: '' },
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return el(
                'div',
                { className: props.className + ' csf-page-heading-block' },
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            value: attrs.customCss,
                            onChange: function( val ) {
                                props.setAttributes( { customCss: val } );
                            },
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: 'Typography', initialOpen: false },
                        el( TextControl, {
                            label: 'Font size (e.g. 16px)',
                            value: attrs.fontSize,
                            onChange: function( val ) {
                                props.setAttributes( { fontSize: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Font weight',
                            value: attrs.fontWeight,
                            options: [
                                { label: 'Default', value: '' },
                                { label: '300', value: '300' },
                                { label: '400', value: '400' },
                                { label: '500', value: '500' },
                                { label: '600', value: '600' },
                                { label: '700', value: '700' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { fontWeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Line height (e.g. 1.5)',
                            value: attrs.lineHeight,
                            onChange: function( val ) {
                                props.setAttributes( { lineHeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Letter spacing (e.g. 0.02em)',
                            value: attrs.letterSpacing,
                            onChange: function( val ) {
                                props.setAttributes( { letterSpacing: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text transform',
                            value: attrs.textTransform,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Uppercase', value: 'uppercase' },
                                { label: 'Lowercase', value: 'lowercase' },
                                { label: 'Capitalize', value: 'capitalize' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textTransform: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text decoration',
                            value: attrs.textDecoration,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Underline', value: 'underline' },
                                { label: 'Line through', value: 'line-through' },
                                { label: 'Overline', value: 'overline' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textDecoration: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Alignment',
                            value: attrs.textAlign,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Left', value: 'left' },
                                { label: 'Center', value: 'center' },
                                { label: 'Right', value: 'right' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textAlign: val } );
                            },
                        } )
                    )
                ),
                el(
                    RichText,
                    {
                        tagName: 'p',
                        className: 'csf-page-heading',
                        value: attrs.content,
                        onChange: function( val ) {
                            props.setAttributes( { content: val } );
                        },
                        placeholder: 'Page heading',
                    }
                )
            );
        },
        save: function() {
            return null;
        },
    } );

    // --- STEP BLOCK ---
    registerBlockType( 'csf/step', {
        title: 'Multi-Step Separator',
        icon: 'minus',
        category: 'layout',
        attributes: {
            title: { type: 'string', default: 'Next Step' },
        },
        edit: function( props ) {
            return el(
                'div',
                { className: props.className + ' csf-step-editor' },
                el( 'div', { className: 'csf-step-line' }, '--- Step Separator ---' ),
                el( TextControl, {
                    label: 'Next Step Button Label',
                    value: props.attributes.title,
                    onChange: function( val ) { props.setAttributes( { title: val } ); }
                } )
            );
        },
        save: function() {
            return null;
        },
    } );

    // --- SUBMIT BLOCK ---
    registerBlockType( 'csf/submit', {
        title: 'Submit Button',
        icon: 'button',
        category: 'common',
        attributes: {
            label: { type: 'string', default: 'Submit' },
            align: { type: 'string', default: 'left' },
            class: { type: 'string', default: '' },
            fontSize: { type: 'string', default: '' },
            fontWeight: { type: 'string', default: '' },
            letterSpacing: { type: 'string', default: '' },
            textTransform: { type: 'string', default: '' },
            textDecoration: { type: 'string', default: '' },
        },
        edit: function( props ) {
            var attrs = props.attributes;

            return el(
                'div',
                { className: props.className + ' csf-submit-editor' },
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: 'Button Settings' },
                        el( SelectControl, {
                            label: 'Alignment',
                            value: attrs.align,
                            options: [
                                { label: 'Left', value: 'left' },
                                { label: 'Center', value: 'center' },
                                { label: 'Right', value: 'right' },
                                { label: 'Full Width', value: 'full' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { align: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Custom CSS Class',
                            help: 'Applied directly to the button element',
                            value: attrs.class,
                            onChange: function( val ) {
                                props.setAttributes( { class: val } );
                            },
                        } )
                    ),
                    el(
                        PanelBody,
                        { title: 'Typography', initialOpen: false },
                        el( TextControl, {
                            label: 'Font size (e.g. 16px)',
                            value: attrs.fontSize,
                            onChange: function( val ) {
                                props.setAttributes( { fontSize: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Font weight',
                            value: attrs.fontWeight,
                            options: [
                                { label: 'Default', value: '' },
                                { label: '400', value: '400' },
                                { label: '500', value: '500' },
                                { label: '600', value: '600' },
                                { label: '700', value: '700' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { fontWeight: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: 'Letter spacing (e.g. 0.05em)',
                            value: attrs.letterSpacing,
                            onChange: function( val ) {
                                props.setAttributes( { letterSpacing: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text transform',
                            value: attrs.textTransform,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Uppercase', value: 'uppercase' },
                                { label: 'Lowercase', value: 'lowercase' },
                                { label: 'Capitalize', value: 'capitalize' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textTransform: val } );
                            },
                        } ),
                        el( SelectControl, {
                            label: 'Text decoration',
                            value: attrs.textDecoration,
                            options: [
                                { label: 'Default', value: '' },
                                { label: 'Underline', value: 'underline' },
                                { label: 'Line through', value: 'line-through' },
                                { label: 'Overline', value: 'overline' },
                            ],
                            onChange: function( val ) {
                                props.setAttributes( { textDecoration: val } );
                            },
                        } )
                    )
                ),
                el( TextControl, {
                    label: 'Button Label',
                    value: attrs.label,
                    onChange: function( val ) {
                        props.setAttributes( { label: val } );
                    },
                } )
            );
        },
        save: function() {
            return null;
        },
    } );

    registerBlockType( 'csf/html', {
        title: 'HTML',
        icon: 'editor-code',
        category: 'common',
        attributes: {
            content: { type: 'string', default: '' },
            customCss: { type: 'string', default: '' },
        },
        edit: function( props ) {
            return el(
                'div',
                { className: props.className + ' csf-html-block' },
                el( InspectorControls, {},
                    el( PanelBody, { title: 'Styling', initialOpen: false },
                        el( TextareaControl, {
                            label: 'Custom CSS',
                            value: props.attributes.customCss,
                            onChange: function( val ) { props.setAttributes( { customCss: val } ); }
                        } )
                    )
                ),
                el( TextareaControl, {
                    label: 'HTML Content',
                    value: props.attributes.content,
                    onChange: function( val ) { props.setAttributes( { content: val } ); },
                    placeholder: '<p>Custom HTML here</p>'
                } )
            );
        },
        save: function() {
            return null;
        },
    } );

}( window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components ) );
