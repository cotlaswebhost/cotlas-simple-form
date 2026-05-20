<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Blocks {

    public function __construct() {
        add_action( 'init', array( $this, 'register_blocks' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
        add_filter( 'allowed_block_types_all', array( $this, 'limit_allowed_blocks' ), 10, 2 );
    }

    public function enqueue_block_assets() {
        wp_enqueue_script(
            'csf-blocks-editor',
            CSF_PLUGIN_URL . 'assets/js/editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-data' ),
            filemtime( CSF_PLUGIN_DIR . 'assets/js/editor.js' )
        );
        
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $tax_data = array();
        foreach ( $taxonomies as $tax ) {
            $tax_data[] = array(
                'slug'  => $tax->name,
                'label' => $tax->labels->singular_name,
            );
        }

        $countries = self::get_countries();
        $country_data = array();
        foreach ( $countries as $code => $name ) {
            $country_data[] = array(
                'code'  => $code,
                'label' => $name,
            );
        }

        wp_localize_script( 'csf-blocks-editor', 'csfBlockVars', array(
            'taxonomies' => $tax_data,
            'countries'  => $country_data,
        ) );
        
        wp_add_inline_style( 'wp-edit-blocks', '
            .wp-block-csf-step { border-top: 2px dashed #ccc; padding: 20px 0; position: relative; }
            .wp-block-csf-step::before { content: "Step Separator"; position: absolute; top: -12px; left: 0; background: #fff; padding-right: 10px; font-size: 12px; color: #999; }
            .csf-field-editor .csf-field-preview { margin-top: 8px; }
            .csf-field-preview-label { display: block; margin-bottom: 4px; font-weight: 600; }
            .csf-field-preview-input,
            .csf-field-preview-select,
            .csf-field-preview-textarea {
                width: 100%;
                padding: 12px 14px;
                min-height: 44px;
                border-radius: 4px;
                border: 1px solid #ddd;
                background-color: #f7f7f7;
                color: #555;
                box-sizing: border-box;
            }
            .csf-field-preview-input[disabled],
            .csf-field-preview-select[disabled],
            .csf-field-preview-textarea[disabled] {
                cursor: default;
            }
        ' );
    }

    public function register_blocks() {
        register_block_type( 'csf/field', array(
            'attributes' => array(
                'label'           => array( 'type' => 'string', 'default' => 'New Field' ),
                'type'            => array( 'type' => 'string', 'default' => 'text' ),
                'required'        => array( 'type' => 'boolean', 'default' => false ),
                'placeholder'     => array( 'type' => 'string', 'default' => '' ),
                'width'           => array( 'type' => 'string', 'default' => '100' ),
                'options'         => array( 'type' => 'string', 'default' => '' ),
                'taxonomy'        => array( 'type' => 'string', 'default' => '' ),
                'name'            => array( 'type' => 'string', 'default' => '' ),
                'class'           => array( 'type' => 'string', 'default' => '' ),
                'hideLabel'       => array( 'type' => 'boolean', 'default' => false ),
                'isHidden'        => array( 'type' => 'boolean', 'default' => false ),
                'countryDefault'  => array( 'type' => 'string', 'default' => '' ),
                'countryDetect'   => array( 'type' => 'boolean', 'default' => false ),
                'customCss'       => array( 'type' => 'string', 'default' => '' ),
                'addressLine1'    => array( 'type' => 'boolean', 'default' => true ),
                'addressLine2'    => array( 'type' => 'boolean', 'default' => true ),
                'addressCity'     => array( 'type' => 'boolean', 'default' => true ),
                'addressState'    => array( 'type' => 'boolean', 'default' => true ),
                'addressPostcode' => array( 'type' => 'boolean', 'default' => true ),
                'addressCountry'  => array( 'type' => 'boolean', 'default' => true ),
                'userMetaTarget'  => array( 'type' => 'string', 'default' => '' ),
                'userMetaKey'     => array( 'type' => 'string', 'default' => '' ),
                'conditionalEnabled' => array( 'type' => 'boolean', 'default' => false ),
                'conditionalField'   => array( 'type' => 'string', 'default' => '' ),
                'conditionalOperator'=> array( 'type' => 'string', 'default' => 'equals' ),
                'conditionalValue'   => array( 'type' => 'string', 'default' => '' ),
                'taxonomyShowEmpty'  => array( 'type' => 'boolean', 'default' => true ),
                'useEditorJS' => array( 'type' => 'boolean', 'default' => false ),
                'useTinyMCE' => array( 'type' => 'boolean', 'default' => false ),
                'helpText' => array( 'type' => 'string', 'default' => '' ),
                'postMetaTarget' => array('type'=>'string','default'=>''),
                'postMetaKey' => array('type'=>'string','default'=>''),
            ),
            'render_callback' => array( $this, 'render_field_block' ),
        ) );

        register_block_type( 'csf/step', array(
            'attributes' => array(
                'title' => array( 'type' => 'string', 'default' => 'Next Step' ),
            ),
            'render_callback' => array( $this, 'render_step_block' ),
        ) );
        
        register_block_type( 'csf/submit', array(
            'attributes' => array(
                'label' => array( 'type' => 'string', 'default' => 'Submit' ),
                'align' => array( 'type' => 'string', 'default' => 'left' ),
                'class' => array( 'type' => 'string', 'default' => '' ),
                'fontSize' => array( 'type' => 'string', 'default' => '' ),
                'fontWeight' => array( 'type' => 'string', 'default' => '' ),
                'letterSpacing' => array( 'type' => 'string', 'default' => '' ),
                'textTransform' => array( 'type' => 'string', 'default' => '' ),
                'textDecoration' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_submit_block' ),
        ) );

        register_block_type( 'csf/heading', array(
            'attributes' => array(
                'content'   => array( 'type' => 'string', 'default' => '' ),
                'customCss' => array( 'type' => 'string', 'default' => '' ),
                'level' => array( 'type' => 'string', 'default' => '' ),
                'fontSize' => array( 'type' => 'string', 'default' => '' ),
                'fontWeight' => array( 'type' => 'string', 'default' => '' ),
                'lineHeight' => array( 'type' => 'string', 'default' => '' ),
                'letterSpacing' => array( 'type' => 'string', 'default' => '' ),
                'textTransform' => array( 'type' => 'string', 'default' => '' ),
                'textDecoration' => array( 'type' => 'string', 'default' => '' ),
                'textAlign' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_heading_block' ),
        ) );

        register_block_type( 'csf/text', array(
            'attributes' => array(
                'content'   => array( 'type' => 'string', 'default' => '' ),
                'customCss' => array( 'type' => 'string', 'default' => '' ),
                'fontSize' => array( 'type' => 'string', 'default' => '' ),
                'fontWeight' => array( 'type' => 'string', 'default' => '' ),
                'lineHeight' => array( 'type' => 'string', 'default' => '' ),
                'letterSpacing' => array( 'type' => 'string', 'default' => '' ),
                'textTransform' => array( 'type' => 'string', 'default' => '' ),
                'textDecoration' => array( 'type' => 'string', 'default' => '' ),
                'textAlign' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_text_block' ),
        ) );

        register_block_type( 'csf/page-heading', array(
            'attributes' => array(
                'content'   => array( 'type' => 'string', 'default' => '' ),
                'customCss' => array( 'type' => 'string', 'default' => '' ),
                'fontSize' => array( 'type' => 'string', 'default' => '' ),
                'fontWeight' => array( 'type' => 'string', 'default' => '' ),
                'lineHeight' => array( 'type' => 'string', 'default' => '' ),
                'letterSpacing' => array( 'type' => 'string', 'default' => '' ),
                'textTransform' => array( 'type' => 'string', 'default' => '' ),
                'textDecoration' => array( 'type' => 'string', 'default' => '' ),
                'textAlign' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_page_heading_block' ),
        ) );

        register_block_type( 'csf/image', array(
            'attributes' => array(
                'url'       => array( 'type' => 'string', 'default' => '' ),
                'alt'       => array( 'type' => 'string', 'default' => '' ),
                'customCss' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_image_block' ),
        ) );

        register_block_type( 'csf/html', array(
            'attributes' => array(
                'content'   => array( 'type' => 'string', 'default' => '' ),
                'customCss' => array( 'type' => 'string', 'default' => '' ),
            ),
            'render_callback' => array( $this, 'render_html_block' ),
        ) );

        // Register Block Pattern
        register_block_pattern(
            'cotlas-simple-forms/simple-enquiry',
            array(
                'title'       => __( 'Simple Enquiry Form', 'cotlas-simple-forms' ),
                'description' => _x( 'A simple enquiry form with Name, Email, Mobile, City, and Message.', 'Block pattern description', 'cotlas-simple-forms' ),
                'content'     => '<!-- wp:csf/field {"label":"Name","required":true,"placeholder":"Name","name":"name","width":"50"} /-->
<!-- wp:csf/field {"label":"Email","type":"email","required":true,"placeholder":"Email","name":"email","width":"50"} /-->
<!-- wp:csf/field {"label":"Mobile","type":"mobile","required":true,"placeholder":"Mobile","name":"mobile","width":"50"} /-->
<!-- wp:csf/field {"label":"City","placeholder":"City","name":"city","width":"50"} /-->
<!-- wp:csf/field {"label":"Message","type":"textarea","placeholder":"I\'d like to book...","name":"message"} /-->
<!-- wp:csf/submit {"label":"Send Enquiry","align":"full"} /-->',
                'categories'  => array( 'featured' ),
            )
        );
    }

    public function limit_allowed_blocks( $allowed_block_types, $editor_context ) {
        if ( empty( $editor_context->post ) || 'csf_form' !== $editor_context->post->post_type ) {
            return $allowed_block_types;
        }

        return array(
            'csf/field',
            'csf/step',
            'csf/submit',
            'csf/heading',
            'csf/text',
            'csf/page-heading',
            'csf/image',
            'csf/html',
        );
    }

    public function render_field_block( $attributes ) {
        // This is used for frontend rendering via do_blocks() if called manually,
        // but typically we process the form content.
        // However, if we use do_blocks() in the shortcode, this will be called.
        
        $type = $attributes['type'];
        $label = $attributes['label'];
        $name = ! empty( $attributes['name'] ) ? $attributes['name'] : sanitize_title( $label );
        $required = $attributes['required'] ? 'required' : '';
        $placeholder = $attributes['placeholder'];
        $width = $attributes['width'];
        $hide_label = isset( $attributes['hideLabel'] ) && $attributes['hideLabel'];
        $is_hidden = isset( $attributes['isHidden'] ) && $attributes['isHidden'];
        
        $value = '';

        /*
        |--------------------------------------------------------------------------
        | Frontend edit mode prefill
        |--------------------------------------------------------------------------
        */

        if (
            ! empty( $GLOBALS['csf_edit_post'] )
        ) {

            $edit_post = $GLOBALS['csf_edit_post'];

            /*
            Standard WP fields
            */

            if (
                ! empty( $attributes['postMetaTarget'] )
            ) {

                switch (
                    $attributes['postMetaTarget']
                ) {

                    case 'post_title':

                        $value =
                            $edit_post->post_title;

                    break;


                    case 'post_excerpt':

                        $value =
                            $edit_post->post_excerpt;

                    break;


                    case 'post_content':

                    if(
                    class_exists(
                    'CSF_EditorJS_Parser'
                    )
                    ){

                    $value=
                    CSF_EditorJS_Parser::
                    gutenberg_to_editorjs(
                    $edit_post->post_content
                    );

                    }else{

                    $value=
                    $edit_post->post_content;

                    }

                    break;
                    case 'post_category':

                        $terms=
                        wp_get_post_categories(
                            $edit_post->ID
                        );

                        $value=
                        implode(
                            ',',
                            $terms
                        );

                    break;



                    case 'post_tags':

                        $terms=
                        wp_get_post_tags(
                            $edit_post->ID,
                            array(
                                'fields'=>'names'
                            )
                        );

                        $value=
                        implode(
                            ', ',
                            $terms
                        );

                    break;



                    case 'featured_image':

                        $thumb=
                        get_post_thumbnail_id(
                            $edit_post->ID
                        );

                        if($thumb){

                            $value=
                            wp_get_attachment_url(
                                $thumb
                            );

                        }

                    break;

                    case 'meta':

                        if (
                            ! empty(
                                $attributes['postMetaKey']
                            )
                        ) {

                            $value =
                                get_post_meta(

                                    $edit_post->ID,

                                    $attributes['postMetaKey'],

                                    true

                                );

                        }

                    break;

                }

            }

        }

        $form_id = isset( $GLOBALS['csf_current_form_id'] )
        ? intval( $GLOBALS['csf_current_form_id'] )
        : 0;
        $label_mode = $form_id ? get_post_meta( $form_id, 'csf_form_label_mode', true ) : '';
        $hide_help_when_label = $form_id ? get_post_meta( $form_id, 'csf_form_hide_help_when_label', true ) : '';
        $hide_help_all = $form_id ? get_post_meta( $form_id, 'csf_form_hide_help_all', true ) : '';

        // Handle Dynamic Placeholders (e.g. {post_title})
        if ( ! empty( $placeholder ) && strpos( $placeholder, '{post_title}' ) !== false ) {
            $current_title = get_the_title();
            $placeholder = str_replace( '{post_title}', $current_title, $placeholder );
            
            // If it contains {post_title}, treat it as pre-filled value instead of just a placeholder
            // This allows users to submit immediately without typing.
            $value = $placeholder;
        }
        
        if ( $label_mode === 'placeholder' && ! $is_hidden && ! empty( $label ) && $placeholder === '' ) {
            $placeholder = $label;
        }

        if ( $label_mode === 'hide' || $label_mode === 'placeholder' ) {
            $hide_label = true;
        }

        $class = 'csf-field csf-field-' . $type . ' csf-width-' . $width . ' ' . $attributes['class'];
        if ( $is_hidden ) {
            $class .= ' csf-honeypot';
            $required = ''; // Force not required for honeypot
        }

        // Let's fix the ID collision.
        $unique_id = $name . '-' . uniqid(); 

        $conditional_enabled  = ! empty( $attributes['conditionalEnabled'] );
        $conditional_field    = isset( $attributes['conditionalField'] ) ? trim( $attributes['conditionalField'] ) : '';
        $conditional_operator = isset( $attributes['conditionalOperator'] ) ? $attributes['conditionalOperator'] : 'equals';
        $conditional_value    = isset( $attributes['conditionalValue'] ) ? $attributes['conditionalValue'] : '';

        $data_attributes = '';
        if ( $conditional_enabled && $conditional_field !== '' ) {
            $data_attributes .= ' data-csf-conditional="1"';
            $data_attributes .= ' data-csf-cond-field="' . esc_attr( $conditional_field ) . '"';
            $data_attributes .= ' data-csf-cond-operator="' . esc_attr( $conditional_operator ) . '"';
            $data_attributes .= ' data-csf-cond-value="' . esc_attr( $conditional_value ) . '"';
        }

        $html = '<div class="' . esc_attr( $class ) . '"' . ( $is_hidden ? ' style="display:none;"' : '' ) . $data_attributes . '>';
        
        $label_html = '';
        if ( ! $hide_label && ! $is_hidden && $label_mode !== 'placeholder' ) {
            $label_html = '<label for="' . esc_attr( $unique_id ) . '">' . esc_html( $label ) . ( $attributes['required'] ? ' <span class="required">*</span>' : '' ) . '</label>';
        } elseif ( $hide_label && ! $is_hidden && $attributes['required'] ) {
            if ( ! empty( $placeholder ) ) {
                $placeholder .= ' *';
            } else {
                $placeholder = '*';
            }
        }
        
        $help_html = '';
        $show_help = true;
        if ( $hide_help_all === '1' ) {
            $show_help = false;
        } elseif ( $label_mode !== '' && $label_mode !== 'hide' && $label_mode !== 'placeholder' && $hide_help_when_label === '1' && ! $hide_label ) {
            $show_help = false;
        }
        if ( ! $is_hidden && $show_help && ! empty( $attributes['helpText'] ) ) {
            $help_html = '<div class="csf-help-text">' . esc_html( $attributes['helpText'] ) . '</div>';
        }

        if ( ( $label_html !== '' || $help_html !== '' ) && ! $is_hidden ) {
            $html .= $label_html . $help_html;
        }
        
        $country_default = isset( $attributes['countryDefault'] ) ? $attributes['countryDefault'] : '';
        $country_detect = ! empty( $attributes['countryDetect'] );
        $resolved_country = $country_default;
        if ( $country_detect ) {
            $auto_country = '';
            if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
                $auto_country = $_SERVER['HTTP_CF_IPCOUNTRY'];
            } elseif ( ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) {
                $auto_country = $_SERVER['GEOIP_COUNTRY_CODE'];
            } elseif ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) {
                $auto_country = $_SERVER['HTTP_X_COUNTRY_CODE'];
            }
            if ( $auto_country ) {
                $resolved_country = strtoupper( sanitize_text_field( $auto_country ) );
            }
        }

        
        switch ( $type ) {
            case 'textarea':
                $use_editorjs = !empty($attributes['useEditorJS']);
                
                if ($use_editorjs) {
                    // Enqueue Editor.js assets
                    $this->enqueue_editorjs_assets();
                    
                    $editor_id = $unique_id . '_editorjs';
                    
                    // Custom Toolbar
                    $html .= '<div  id="' . esc_attr($editor_id) . '"  class="csf-editorjs-container"  data-textarea-id="' . esc_attr($unique_id) . '" style="border:1px solid #ddd;border-radius:4px;min-height:300px;background:#fff;"> </div>';

                    $html .= '<textarea name="' . esc_attr($name) . '"  id="' . esc_attr($unique_id) . '"  style="display:none;" ' . $required . '>' . esc_textarea($value) . '</textarea>';
                } else {
                    // Regular textarea
                    $html .= '<textarea name="' . esc_attr($name) . '" id="' . esc_attr($unique_id) . '" placeholder="' . esc_attr($placeholder) . '" ' . $required . ' rows="5">' . esc_textarea($value) . '</textarea>';
                }
                break;
            case 'taxonomy_select2':
                $select_class = 'csf-select2';
                $html .= '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" ' . $required . ' class="' . esc_attr( $select_class ) . '" style="width:100%;">';
                $html .= '<option value="">' . __( 'Select...', 'cotlas-simple-forms' ) . '</option>';
                if ( ! empty( $attributes['taxonomy'] ) ) {
                    $show_empty = isset( $attributes['taxonomyShowEmpty'] ) ? (bool) $attributes['taxonomyShowEmpty'] : true;
                    $terms = get_terms( array( 'taxonomy' => $attributes['taxonomy'], 'hide_empty' => ! $show_empty ) );
                    if ( ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {

                            $selected_values =
                            array_map(
                                'trim',
                                explode(',', $value)
                            );

                            $html .= '<option value="' .
                            esc_attr($term->term_id) .
                            '" ' .

                            selected(
                                in_array(
                                    $term->term_id,
                                    $selected_values
                                ),
                                true,
                                false
                            )

                            .'>' .

                            esc_html(
                                $term->name
                            )

                            .'</option>';

                        }
                    }
                }
                $html .= '</select>';
                break;
            case 'repeater':
                $row_placeholder = ! empty( $placeholder ) ? $placeholder : __( 'Add item', 'cotlas-simple-forms' );
                $html .= '<div class="csf-repeater" data-name="' . esc_attr( $name ) . '">';
                $html .= '<div class="csf-repeater-row">';
                $html .= '<input type="text" name="' . esc_attr( $name ) . '[]" placeholder="' . esc_attr( $row_placeholder ) . '" ' . $required . '>';
                $html .= '<button type="button" class="csf-repeater-remove" aria-label="' . esc_attr__( 'Remove row', 'cotlas-simple-forms' ) . '">−</button>';
                $html .= '</div>';
                $html .= '<div class="csf-repeater-footer">';
                $html .= '<button type="button" class="csf-repeater-add" aria-label="' . esc_attr__( 'Add row', 'cotlas-simple-forms' ) . '">' . esc_html__( 'Add another', 'cotlas-simple-forms' ) . '</button>';
                $html .= '</div>';
                $html .= '</div>';
                break;
            case 'select2':
                $select_class = 'csf-select2';
                $html .= '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" ' . $required . ' class="' . esc_attr( $select_class ) . '" style="width:100%;">';
                $html .= '<option value="">' . __( 'Select...', 'cotlas-simple-forms' ) . '</option>';
                
                // Manual Options
                if ( ! empty( $attributes['options'] ) ) {
                    $opts = explode( "\n", $attributes['options'] );
                    foreach ( $opts as $opt ) {
                        $opt = trim( $opt );
                        if ( empty( $opt ) ) continue;
                        // Handle "Value : Label" format
                        if ( strpos( $opt, ':' ) !== false ) {
                            list( $val, $lbl ) = explode( ':', $opt, 2 );
                            $html .= '<option value="' . esc_attr( trim( $val ) ) . '">' . esc_html( trim( $lbl ) ) . '</option>';
                        } else {
                            $html .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
                        }
                    }
                } 
                // Taxonomy Options
                elseif ( ! empty( $attributes['taxonomy'] ) ) {
                    $terms = get_terms( array( 'taxonomy' => $attributes['taxonomy'], 'hide_empty' => false ) );
                    if ( ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {

                            $selected_values =
                            array_map(
                                'trim',
                                explode(',', $value)
                            );

                            $html .= '<option value="' .
                            esc_attr($term->term_id) .
                            '" ' .

                            selected(
                                in_array(
                                    $term->term_id,
                                    $selected_values
                                ),
                                true,
                                false
                            )

                            .'>' .

                            esc_html(
                                $term->name
                            )

                            .'</option>';

                        }
                    }
                }
                $html .= '</select>';
                break;

            case 'checkbox_group':
                $allow_multiple = ! isset( $attributes['checkboxAllowMultiple'] ) || $attributes['checkboxAllowMultiple'];
                $html .= '<div class="csf-checkbox-group" data-csf-multiple="' . ( $allow_multiple ? '1' : '0' ) . '">';
                if ( ! empty( $attributes['options'] ) ) {
                    $raw_options = $attributes['options'];
                    $normalized = str_replace( array( "\r\n", "\r" ), "\n", $raw_options );
                    $normalized = str_replace( array( "\\r\\n", "\\n", "\\r" ), "\n", $normalized );
                    $opts = preg_split( '/\n+/', $normalized );
                    foreach ( $opts as $opt ) {
                        $opt = trim( $opt );
                        if ( empty( $opt ) ) {
                            continue;
                        }
                        $val = $opt;
                        $lbl = $opt;
                        if ( strpos( $opt, ':' ) !== false ) {
                            list( $v, $l ) = explode( ':', $opt, 2 );
                            $val = trim( $v );
                            $lbl = trim( $l );
                        }
                        $html .= '<label class="csf-checkbox-group-item">';
                        $html .= '<input type="checkbox" name="' . esc_attr( $name ) . '[]" value="' . esc_attr( $val ) . '">';
                        $html .= '<span class="csf-checkbox-group-label">' . esc_html( $lbl ) . '</span>';
                        $html .= '</label>';
                    }
                }
                $html .= '</div>';
                break;

            case 'city_google':
                $placeholder_text = $placeholder !== '' ? $placeholder : __( 'City', 'cotlas-simple-forms' );
                $google_key = get_option( 'csf_google_places_api_key' );
                if ( $google_key ) {
                    $html .= '<input type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" class="csf-city-google-input" placeholder="' . esc_attr( $placeholder_text ) . '" ' . $required . ' data-csf-city-country="IN">';
                } else {
                    $html .= '<input type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr( $placeholder_text ) . '" ' . $required . '>';
                }
                break;

            case 'state_google':
                $placeholder_text = $placeholder !== '' ? $placeholder : __( 'State', 'cotlas-simple-forms' );
                $google_key = get_option( 'csf_google_places_api_key' );
                if ( $google_key ) {
                    $html .= '<input type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" class="csf-state-google-input" placeholder="' . esc_attr( $placeholder_text ) . '" ' . $required . '>';
                } else {
                    $html .= '<input type="text" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr( $placeholder_text ) . '" ' . $required . '>';
                }
                break;

            case 'country':
                $countries = self::get_countries();
                if ( $resolved_country && ! isset( $countries[ $resolved_country ] ) ) {
                    foreach ( $countries as $code => $country_name ) {
                        if ( strcasecmp( $country_name, $resolved_country ) === 0 ) {
                            $resolved_country = $code;
                            break;
                        }
                    }
                }
                $html .= '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" ' . $required . ' class="csf-select2" style="width:100%;">';
                $html .= '<option value="">' . __( 'Select country', 'cotlas-simple-forms' ) . '</option>';
                foreach ( $countries as $code => $country_name ) {
                    $selected = $resolved_country === $code ? ' selected="selected"' : '';
                    $html .= '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $country_name ) . '</option>';
                }
                $html .= '</select>';
                break;

            case 'address':
                $show_line1    = ! isset( $attributes['addressLine1'] ) || $attributes['addressLine1'];
                $show_line2    = ! isset( $attributes['addressLine2'] ) || $attributes['addressLine2'];
                $show_city     = ! isset( $attributes['addressCity'] ) || $attributes['addressCity'];
                $show_state    = ! isset( $attributes['addressState'] ) || $attributes['addressState'];
                $show_postcode = ! isset( $attributes['addressPostcode'] ) || $attributes['addressPostcode'];
                $show_country  = ! isset( $attributes['addressCountry'] ) || $attributes['addressCountry'];

                $countries = self::get_countries();
                if ( $resolved_country && ! isset( $countries[ $resolved_country ] ) ) {
                    foreach ( $countries as $code => $country_name ) {
                        if ( strcasecmp( $country_name, $resolved_country ) === 0 ) {
                            $resolved_country = $code;
                            break;
                        }
                    }
                }
                $html .= '<div class="csf-address">';

                if ( $show_line1 || $show_line2 ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_line1 ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_line1" placeholder="' . esc_attr( $placeholder ? $placeholder : __( 'Address line 1', 'cotlas-simple-forms' ) ) . '" ' . $required . '>';
                        $html .= '</div>';
                    }
                    if ( $show_line2 ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_line2" placeholder="' . esc_attr( __( 'Address line 2', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                if ( $show_city || $show_state ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_city ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_city" placeholder="' . esc_attr( __( 'City', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    if ( $show_state ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_state" placeholder="' . esc_attr( __( 'State/Region', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                if ( $show_postcode || $show_country ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_postcode ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_postcode" placeholder="' . esc_attr( __( 'Postal code', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    if ( $show_country ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<select name="' . esc_attr( $name ) . '_country" class="csf-address-country csf-select2" style="width:100%;">';
                        $html .= '<option value="">' . __( 'Select country', 'cotlas-simple-forms' ) . '</option>';
                        foreach ( $countries as $code => $country_name ) {
                            $selected = $resolved_country === $code ? ' selected="selected"' : '';
                            $html .= '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $country_name ) . '</option>';
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" class="csf-address-combined" data-address-base="' . esc_attr( $name ) . '">';
                $html .= '</div>';
                break;

            case 'google_address':
                $show_line1    = ! isset( $attributes['addressLine1'] ) || $attributes['addressLine1'];
                $show_line2    = ! isset( $attributes['addressLine2'] ) || $attributes['addressLine2'];
                $show_city     = ! isset( $attributes['addressCity'] ) || $attributes['addressCity'];
                $show_state    = ! isset( $attributes['addressState'] ) || $attributes['addressState'];
                $show_postcode = ! isset( $attributes['addressPostcode'] ) || $attributes['addressPostcode'];
                $show_country  = ! isset( $attributes['addressCountry'] ) || $attributes['addressCountry'];

                $countries = self::get_countries();
                if ( $resolved_country && ! isset( $countries[ $resolved_country ] ) ) {
                    foreach ( $countries as $code => $country_name ) {
                        if ( strcasecmp( $country_name, $resolved_country ) === 0 ) {
                            $resolved_country = $code;
                            break;
                        }
                    }
                }
                $html .= '<div class="csf-address csf-google-address">';

                if ( $show_line1 || $show_line2 ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_line1 ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_line1" class="csf-google-address-line1" placeholder="' . esc_attr( $placeholder ? $placeholder : __( 'Address line 1', 'cotlas-simple-forms' ) ) . '" ' . $required . '>';
                        $html .= '</div>';
                    }
                    if ( $show_line2 ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_line2" placeholder="' . esc_attr( __( 'Address line 2', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                if ( $show_city || $show_state ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_city ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_city" placeholder="' . esc_attr( __( 'City', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    if ( $show_state ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_state" placeholder="' . esc_attr( __( 'State/Region', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                if ( $show_postcode || $show_country ) {
                    $html .= '<div class="csf-address-row csf-address-row-two">';
                    if ( $show_postcode ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<input type="text" name="' . esc_attr( $name ) . '_postcode" placeholder="' . esc_attr( __( 'Postal code', 'cotlas-simple-forms' ) ) . '">';
                        $html .= '</div>';
                    }
                    if ( $show_country ) {
                        $html .= '<div class="csf-address-col">';
                        $html .= '<select name="' . esc_attr( $name ) . '_country" class="csf-address-country csf-select2" style="width:100%;">';
                        $html .= '<option value="">' . __( 'Select country', 'cotlas-simple-forms' ) . '</option>';
                        foreach ( $countries as $code => $country_name ) {
                            $selected = $resolved_country === $code ? ' selected="selected"' : '';
                            $html .= '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $country_name ) . '</option>';
                        }
                        $html .= '</select>';
                        $html .= '</div>';
                    }
                    $html .= '</div>';
                }

                $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" class="csf-address-combined" data-address-base="' . esc_attr( $name ) . '">';
                $html .= '</div>';
                break;

            case 'select':
                $html .= '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" ' . $required . '>';
                $html .= '<option value="">' . __( 'Select...', 'cotlas-simple-forms' ) . '</option>';
                if ( ! empty( $attributes['options'] ) ) {
                    $opts = explode( "\n", $attributes['options'] );
                    foreach ( $opts as $opt ) {
                        $opt = trim( $opt );
                        if ( empty( $opt ) ) continue;
                        // Handle "Value : Label" format
                        if ( strpos( $opt, ':' ) !== false ) {
                            list( $val, $lbl ) = explode( ':', $opt, 2 );
                            $html .= '<option value="' . esc_attr( trim( $val ) ) . '">' . esc_html( trim( $lbl ) ) . '</option>';
                        } else {
                            $html .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
                        }
                    }
                } 
                // Taxonomy Options
                elseif ( ! empty( $attributes['taxonomy'] ) ) {
                    $terms = get_terms( array( 'taxonomy' => $attributes['taxonomy'], 'hide_empty' => false ) );
                    if ( ! is_wp_error( $terms ) ) {
                        foreach ( $terms as $term ) {
                            $html .= '<option value="' . esc_attr( $term->name ) . '">' . esc_html( $term->name ) . '</option>';
                        }
                    }
                }
                $html .= '</select>';
                break;
                
            case 'checkbox':
                // Single checkbox for consent/yes-no
                $html .= '<div class="csf-checkbox-wrapper">';
                $html .= '<input type="checkbox" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" value="Yes" ' . $required . '>';
                $html .= '</div>';
                break;
                
            case 'file':

                if(!empty($value)){

                    $html .= '

                    <div
                    class="csf-existing-image"
                    style="
                    position:relative;
                    display:inline-block;
                    ">

                        <img
                        src="'.
                        esc_url($value).
                        '"

                        style="
                        max-width:150px;
                        border-radius:6px;
                        display:block;
                        ">

                        <span
                        class="csf-remove-image"

                        style="
                        position:absolute;
                        top:-8px;
                        left: 157px;
                        width:22px;
                        height:22px;
                        background:#e53935;
                        color:#fff;
                        border-radius:50%;
                        cursor:pointer;
                        text-align:center;
                        line-height:22px;
                        font-size:14px;
                        font-weight:bold;
                        "

                        data-target="'.
                        esc_attr($unique_id).
                        '"

                        >×</span>

                    </div>

                <input
                type="hidden"
                name="remove_featured_image"
                value="0"
                id="remove_featured_image">

                ';
                }

                $html .=
                '<input

                type="file"

                name="'.
                esc_attr($name).
                '"

                id="'.
                esc_attr($unique_id).
                '"

                '.$required.'

                >';

            break;
                
            case 'date':
                $html .= '<input type="date" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" value="' . esc_attr( $value ) . '" ' . $required . ' class="csf-date-input">';
                break;

            case 'date_range':
                $html .= '<div class="csf-daterange">';
                $html .= '<div class="csf-daterange-row">';
                $html .= '<div class="csf-daterange-col">';
                $html .= '<input type="date" name="' . esc_attr( $name ) . '_from" id="' . esc_attr( $unique_id ) . '-from" ' . $required . ' class="csf-date-input">';
                $html .= '</div>';
                $html .= '<div class="csf-daterange-col">';
                $html .= '<input type="date" name="' . esc_attr( $name ) . '_to" id="' . esc_attr( $unique_id ) . '-to" ' . $required . ' class="csf-date-input">';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" class="csf-daterange-combined" data-daterange-base="' . esc_attr( $name ) . '">';
                $html .= '</div>';
                break;

            case 'datetime_range':
                $html .= '<div class="csf-daterange">';
                $html .= '<div class="csf-daterange-row">';
                $html .= '<div class="csf-daterange-col">';
                $html .= '<input type="datetime-local" name="' . esc_attr( $name ) . '_from" id="' . esc_attr( $unique_id ) . '-from" ' . $required . ' class="csf-date-input">';
                $html .= '</div>';
                $html .= '<div class="csf-daterange-col">';
                $html .= '<input type="datetime-local" name="' . esc_attr( $name ) . '_to" id="' . esc_attr( $unique_id ) . '-to" ' . $required . ' class="csf-date-input">';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<input type="hidden" name="' . esc_attr( $name ) . '" class="csf-daterange-combined" data-daterange-base="' . esc_attr( $name ) . '">';
                $html .= '</div>';
                break;

            case 'time':
                $html .= '<input type="time" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" value="' . esc_attr( $value ) . '" ' . $required . ' class="csf-time-input">';
                break;

            case 'url':
                $html .= '<input type="url" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" ' . $required . '>';
                break;
                
            case 'age':
                $html .= '<input type="number" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" min="0" step="1" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" ' . $required . '>';
                break;

            case 'mobile':
                $dials = array(
                    'AF' => '+93',
                    'AL' => '+355',
                    'DZ' => '+213',
                    'AD' => '+376',
                    'AO' => '+244',
                    'AG' => '+1-268',
                    'AR' => '+54',
                    'AM' => '+374',
                    'AU' => '+61',
                    'AT' => '+43',
                    'AZ' => '+994',
                    'BS' => '+1-242',
                    'BH' => '+973',
                    'BD' => '+880',
                    'BB' => '+1-246',
                    'BY' => '+375',
                    'BE' => '+32',
                    'BZ' => '+501',
                    'BJ' => '+229',
                    'BT' => '+975',
                    'BO' => '+591',
                    'BA' => '+387',
                    'BW' => '+267',
                    'BR' => '+55',
                    'BN' => '+673',
                    'BG' => '+359',
                    'BF' => '+226',
                    'BI' => '+257',
                    'CV' => '+238',
                    'KH' => '+855',
                    'CM' => '+237',
                    'CA' => '+1',
                    'CF' => '+236',
                    'TD' => '+235',
                    'CL' => '+56',
                    'CN' => '+86',
                    'CO' => '+57',
                    'KM' => '+269',
                    'CD' => '+243',
                    'CG' => '+242',
                    'CR' => '+506',
                    'HR' => '+385',
                    'CU' => '+53',
                    'CY' => '+357',
                    'CZ' => '+420',
                    'DK' => '+45',
                    'DJ' => '+253',
                    'DM' => '+1-767',
                    'DO' => '+1-809',
                    'DO' => '+1-829',
                    'DO' => '+1-849',
                    'EC' => '+593',
                    'EG' => '+20',
                    'SV' => '+503',
                    'GQ' => '+240',
                    'ER' => '+291',
                    'EE' => '+372',
                    'SZ' => '+268',
                    'ET' => '+251',
                    'FJ' => '+679',
                    'FI' => '+358',
                    'FR' => '+33',
                    'GA' => '+241',
                    'GM' => '+220',
                    'GE' => '+995',
                    'DE' => '+49',
                    'GH' => '+233',
                    'GR' => '+30',
                    'GD' => '+1-473',
                    'GT' => '+502',
                    'GN' => '+224',
                    'GW' => '+245',
                    'GY' => '+592',
                    'HT' => '+509',
                    'HN' => '+504',
                    'HU' => '+36',
                    'IS' => '+354',
                    'IN' => '+91',
                    'ID' => '+62',
                    'IR' => '+98',
                    'IQ' => '+964',
                    'IE' => '+353',
                    'IL' => '+972',
                    'IT' => '+39',
                    'JM' => '+1-876',
                    'JP' => '+81',
                    'JO' => '+962',
                    'KZ' => '+7',
                    'KE' => '+254',
                    'KI' => '+686',
                    'KW' => '+965',
                    'KG' => '+996',
                    'LA' => '+856',
                    'LV' => '+371',
                    'LB' => '+961',
                    'LS' => '+266',
                    'LR' => '+231',
                    'LY' => '+218',
                    'LI' => '+423',
                    'LT' => '+370',
                    'LU' => '+352',
                    'MG' => '+261',
                    'MW' => '+265',
                    'MY' => '+60',
                    'MV' => '+960',
                    'ML' => '+223',
                    'MT' => '+356',
                    'MH' => '+692',
                    'MR' => '+222',
                    'MU' => '+230',
                    'MX' => '+52',
                    'FM' => '+691',
                    'MD' => '+373',
                    'MC' => '+377',
                    'MN' => '+976',
                    'ME' => '+382',
                    'MA' => '+212',
                    'MZ' => '+258',
                    'MM' => '+95',
                    'NA' => '+264',
                    'NR' => '+674',
                    'NP' => '+977',
                    'NL' => '+31',
                    'NZ' => '+64',
                    'NI' => '+505',
                    'NE' => '+227',
                    'NG' => '+234',
                    'KP' => '+850',
                    'MK' => '+389',
                    'NO' => '+47',
                    'OM' => '+968',
                    'PK' => '+92',
                    'PW' => '+680',
                    'PS' => '+970',
                    'PA' => '+507',
                    'PG' => '+675',
                    'PY' => '+595',
                    'PE' => '+51',
                    'PH' => '+63',
                    'PL' => '+48',
                    'PT' => '+351',
                    'QA' => '+974',
                    'RO' => '+40',
                    'RU' => '+7',
                    'RW' => '+250',
                    'KN' => '+1-869',
                    'LC' => '+1-758',
                    'VC' => '+1-784',
                    'WS' => '+685',
                    'SM' => '+378',
                    'ST' => '+239',
                    'SA' => '+966',
                    'SN' => '+221',
                    'RS' => '+381',
                    'SC' => '+248',
                    'SL' => '+232',
                    'SG' => '+65',
                    'SK' => '+421',
                    'SI' => '+386',
                    'SB' => '+677',
                    'SO' => '+252',
                    'ZA' => '+27',
                    'KR' => '+82',
                    'SS' => '+211',
                    'ES' => '+34',
                    'LK' => '+94',
                    'SD' => '+249',
                    'SR' => '+597',
                    'SE' => '+46',
                    'CH' => '+41',
                    'SY' => '+963',
                    'TW' => '+886',
                    'TJ' => '+992',
                    'TZ' => '+255',
                    'TH' => '+66',
                    'TL' => '+670',
                    'TG' => '+228',
                    'TO' => '+676',
                    'TT' => '+1-868',
                    'TN' => '+216',
                    'TR' => '+90',
                    'TM' => '+993',
                    'TV' => '+688',
                    'UG' => '+256',
                    'UA' => '+380',
                    'AE' => '+971',
                    'GB' => '+44',
                    'US' => '+1',
                    'UY' => '+598',
                    'UZ' => '+998',
                    'VU' => '+678',
                    'VA' => '+379',
                    'VE' => '+58',
                    'VN' => '+84',
                    'YE' => '+967',
                    'ZM' => '+260',
                    'ZW' => '+263',
                );
                $selected_country = $resolved_country && isset( $dials[ $resolved_country ] ) ? $resolved_country : 'IN';
                $html .= '<div class="csf-mobile">';
                $html .= '<select name="' . esc_attr( $name ) . '_country_code" class="csf-mobile-code csf-select2">';
                foreach ( $dials as $code => $dial ) {
                    $selected = $selected_country === $code ? ' selected="selected"' : '';
                    $label = $code . ' ' . $dial;
                    $html .= '<option value="' . esc_attr( $dial ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
                }
                $html .= '</select>';
                $html .= '<input type="tel" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" class="csf-mobile-input" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" ' . $required . '>';
                $html .= '</div>';
                break;

            default:
                $input_type = $type === 'mobile' ? 'tel' : $type;
                if ( $is_hidden ) {
                    $input_type = 'text';
                }
                $html .= '<input type="' . esc_attr( $input_type ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $unique_id ) . '" placeholder="' . esc_attr( $placeholder ) . '" value="' . esc_attr( $value ) . '" ' . $required . '>';
                break;
        }
        
        $html .= '</div>';
        return $html;
    }

    public function render_step_block( $attributes ) {
        // In the frontend, this marks the end of a step and start of new one.
        // We will handle this in the main render loop to wrap steps in divs.
        // But if called individually, we output a marker.
        return '<div class="csf-step-marker" data-title="' . esc_attr( $attributes['title'] ) . '"></div>';
    }
    
    public function render_submit_block( $attributes ) {
        $wrapper_class = isset( $attributes['className'] ) ? $attributes['className'] : '';
        $button_class = 'csf-submit-btn ' . ( isset( $attributes['class'] ) ? $attributes['class'] : '' );

        $button_style = $this->build_typography_style( $attributes );
        $button_style_attr = $button_style !== '' ? ' style="' . esc_attr( $button_style ) . '"' : '';

        return '<div class="csf-submit-wrapper ' . esc_attr( $wrapper_class ) . '" style="text-align:' . esc_attr( $attributes['align'] ) . '"><button type="submit" class="' . esc_attr( trim( $button_class ) ) . '"' . $button_style_attr . '>' . esc_html( $attributes['label'] ) . '</button></div>';
    }

    public function render_heading_block( $attributes ) {
        $content = isset( $attributes['content'] ) ? $attributes['content'] : '';
        if ( $content === '' ) {
            return '';
        }
        $allowed_levels = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
        $level = isset( $attributes['level'] ) ? $attributes['level'] : '';
        if ( ! in_array( $level, $allowed_levels, true ) ) {
            $level = 'h3';
        }

        $style = $this->build_typography_style( $attributes );
        $style_attr = $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '';

        return '<' . $level . ' class="csf-heading"' . $style_attr . '>' . wp_kses_post( $content ) . '</' . $level . '>';
    }

    public function render_text_block( $attributes ) {
        $content = isset( $attributes['content'] ) ? $attributes['content'] : '';
        if ( $content === '' ) {
            return '';
        }
        $style = $this->build_typography_style( $attributes );
        $style_attr = $style !== '' ? ' style="' . esc_attr( $style ) . '"' : '';

        return '<p class="csf-text"' . $style_attr . '>' . wp_kses_post( $content ) . '</p>';
    }

    public function render_page_heading_block( $attributes ) {
        return '';
    }

    public function render_image_block( $attributes ) {
        $url = isset( $attributes['url'] ) ? $attributes['url'] : '';
        if ( $url === '' ) {
            return '';
        }
        $alt = isset( $attributes['alt'] ) ? $attributes['alt'] : '';
        return '<div class="csf-image"><img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '"></div>';
    }

    private function build_typography_style( $attributes ) {
        $parts = array();

        if ( ! empty( $attributes['fontSize'] ) ) {
            $parts[] = 'font-size:' . $attributes['fontSize'];
        }
        if ( ! empty( $attributes['fontWeight'] ) ) {
            $parts[] = 'font-weight:' . $attributes['fontWeight'];
        }
        if ( ! empty( $attributes['lineHeight'] ) ) {
            $parts[] = 'line-height:' . $attributes['lineHeight'];
        }
        if ( ! empty( $attributes['letterSpacing'] ) ) {
            $parts[] = 'letter-spacing:' . $attributes['letterSpacing'];
        }
        if ( ! empty( $attributes['textTransform'] ) ) {
            $parts[] = 'text-transform:' . $attributes['textTransform'];
        }
        if ( ! empty( $attributes['textDecoration'] ) ) {
            $parts[] = 'text-decoration:' . $attributes['textDecoration'];
        }
        if ( ! empty( $attributes['textAlign'] ) ) {
            $parts[] = 'text-align:' . $attributes['textAlign'];
        }

        if ( empty( $parts ) ) {
            return '';
        }

        return implode( ';', $parts ) . ';';
    }

    public function render_html_block( $attributes ) {
        $content = isset( $attributes['content'] ) ? $attributes['content'] : '';
        if ( $content === '' ) {
            return '';
        }
        return '<div class="csf-html">' . wp_kses_post( $content ) . '</div>';
    }

    public static function get_countries() {
        return array(
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'CV' => 'Cabo Verde',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CR' => 'Costa Rica',
            'CI' => 'Côte d\'Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czechia',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'SZ' => 'Eswatini',
            'ET' => 'Ethiopia',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GR' => 'Greece',
            'GD' => 'Grenada',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KP' => 'Korea, North',
            'KR' => 'Korea, South',
            'XK' => 'Kosovo',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'MK' => 'North Macedonia',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'QA' => 'Qatar',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'KN' => 'Saint Kitts and Nevis',
            'LC' => 'Saint Lucia',
            'VC' => 'Saint Vincent and the Grenadines',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican City',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        );
    }
    // Add this method to CSF_Blocks class
    public function enqueue_editorjs_assets() {
        // Enqueue Editor.js core
        wp_enqueue_script(
            'editorjs-core',
            'https://cdn.jsdelivr.net/npm/@editorjs/editorjs@latest/dist/editorjs.umd.min.js',
            array(),
            '2.30.0',
            true
        );
        
        // Enqueue Editor.js tools
        $tools = array(
            'header' => 'https://cdn.jsdelivr.net/npm/@editorjs/header@latest/dist/header.umd.min.js',
            'list' => 'https://cdn.jsdelivr.net/npm/@editorjs/list@latest/dist/list.umd.min.js',
            'paragraph' => 'https://cdn.jsdelivr.net/npm/@editorjs/paragraph@latest/dist/paragraph.umd.min.js',
            'image' => 'https://cdn.jsdelivr.net/npm/@editorjs/image@latest/dist/image.umd.min.js',
            'embed' => 'https://cdn.jsdelivr.net/npm/@editorjs/embed@latest/dist/embed.umd.min.js',
            'table' => 'https://cdn.jsdelivr.net/npm/@editorjs/table@latest/dist/table.umd.min.js',
            'delimiter' => 'https://cdn.jsdelivr.net/npm/@editorjs/delimiter@latest/dist/delimiter.umd.min.js',
            'warning' => 'https://cdn.jsdelivr.net/npm/@editorjs/warning@latest/dist/warning.umd.min.js',
            'code' => 'https://cdn.jsdelivr.net/npm/@editorjs/code@latest/dist/code.umd.min.js',
            'raw' => 'https://cdn.jsdelivr.net/npm/@editorjs/raw@latest/dist/raw.umd.min.js',
            'quote' => 'https://cdn.jsdelivr.net/npm/@editorjs/quote@latest/dist/quote.umd.min.js',
            'checklist' => 'https://cdn.jsdelivr.net/npm/@editorjs/checklist@latest/dist/checklist.umd.min.js',
            'marker' => 'https://cdn.jsdelivr.net/npm/@editorjs/marker@latest/dist/marker.umd.min.js',
            'text-color' => 'https://cdn.jsdelivr.net/npm/editorjs-text-color-plugin@2.0.4/dist/bundle.js',
            'underline' => 'https://cdn.jsdelivr.net/npm/@editorjs/underline@latest/dist/bundle.js',
            'inline-code' => 'https://cdn.jsdelivr.net/npm/@editorjs/inline-code@latest/dist/inline-code.umd.min.js',
        );
        
        foreach ($tools as $tool => $url) {
            wp_enqueue_script(
                'editorjs-tool-' . $tool,
                $url,
                array('editorjs-core'),
                null,
                true
            );
        }
        
        // Enqueue our custom integration
        wp_enqueue_script(
            'csf-editorjs-integration',
            CSF_PLUGIN_URL . 'assets/js/editorjs-integration.js',
            array('editorjs-core'),
            filemtime(CSF_PLUGIN_DIR . 'assets/js/editorjs-integration.js'),
            true
        );
        
        wp_enqueue_script(
            'csf-editorjs-upload',
            CSF_PLUGIN_URL . 'assets/js/editorjs-upload.js',
            array(),
            filemtime(CSF_PLUGIN_DIR . 'assets/js/editorjs-upload.js'),
            true
        );
        
        // Localize for AJAX upload
        wp_localize_script('csf-editorjs-upload', 'csfEditorJS', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'uploadNonce' => wp_create_nonce('csf_editorjs_upload'),
            'uploadUrl' => admin_url('admin-ajax.php?action=csf_editorjs_upload'),
        ));
    }
}
