<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CSF_Render {

    public function __construct() {
        add_shortcode( 'csf_form', array( $this, 'render_form_shortcode' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
    }

    public function enqueue_frontend_scripts() {
        // Enqueue Select2 for searchable dropdowns
        wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
        wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true );

        $google_key = get_option( 'csf_google_places_api_key' );
        $deps = array( 'jquery', 'select2' );

        if ( $google_key ) {
            $google_url = 'https://maps.googleapis.com/maps/api/js?key=' . urlencode( $google_key ) . '&libraries=places';
            wp_register_script( 'csf-google-places', $google_url, array(), null, true );
            $deps[] = 'csf-google-places';
        }

        wp_register_script( 'csf-frontend', CSF_PLUGIN_URL . 'assets/js/frontend.js', $deps, '1.0.0', true );
        // Removed the dependency 'select2' from css because styles usually don't have dependencies in this context 
        // and it might cause issues if select2 handle is for a script. 
        // wp_enqueue_style handles dependencies on other styles.
        wp_register_style( 'csf-frontend-css', CSF_PLUGIN_URL . 'assets/css/frontend.css', array( 'select2' ), '1.0.0' );

        // Localize
        wp_localize_script( 'csf-frontend', 'csf_vars', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'csf_submit_nonce' ),
            'google_places_enabled' => $google_key ? 1 : 0,
        ) );
    }

    public function render_form_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'id'    => 0,
            'class' => '',
        ), $atts );

        if ( ! $atts['id'] ) {
            return '';
        }

        $post = get_post( $atts['id'] );
        if ( ! $post || $post->post_type !== 'csf_form' ) {
            return 'Form not found.';
        }

        $GLOBALS['csf_current_form_id'] = (int) $atts['id'];

        wp_enqueue_script( 'csf-frontend' );
        wp_enqueue_style( 'csf-frontend-css' );
        
        // Cloudflare Turnstile
        $site_key = get_option( 'csf_turnstile_site_key' );
        $form_turnstile = get_post_meta( $post->ID, 'csf_form_turnstile_enable', true );
        if ( $site_key && $form_turnstile ) {
            wp_enqueue_script( 'csf-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true );
        }

        $content = parse_blocks( $post->post_content );
        
        $has_block_editor = false;
        
        $check_blocks = function( $blocks ) use ( &$check_blocks, &$has_block_editor ) {
            foreach ( $blocks as $block ) {
                if ( $block['blockName'] === 'csf/field' && ! empty( $block['attrs']['useTinyMCE'] ) ) {
                    $has_block_editor = true;
                    return;
                }
                if ( ! empty( $block['innerBlocks'] ) ) {
                    $check_blocks( $block['innerBlocks'] );
                }
            }
        };
        $check_blocks( $content );
        
        if ( $has_block_editor ) {
            wp_enqueue_script( 'wp-edit-post' );
            wp_enqueue_style( 'wp-edit-post' );
            wp_enqueue_style( 'wp-format-library' );
            wp_enqueue_style( 'wp-block-library' );
            wp_enqueue_style( 'wp-components' );
        }

        
        $inline_css = $this->collect_custom_css_from_blocks( $content );

        $steps = array();
        $current_step_blocks = array();
        $step_titles = array();
        $has_steps = false;
        $current_step_index = 1;

        foreach ( $content as $block ) {
            if ( empty( $block['blockName'] ) ) {
                continue;
            }

            if ( $block['blockName'] === 'csf/page-heading' ) {
                if ( ! empty( $block['attrs']['content'] ) ) {
                    $step_titles[ $current_step_index ] = wp_strip_all_tags( $block['attrs']['content'] );
                }
                continue;
            }

            if ( $block['blockName'] === 'csf/step' ) {
                $has_steps = true;

                if ( ! empty( $current_step_blocks ) ) {
                    $steps[] = $current_step_blocks;
                    $current_step_blocks = array(); // Reset for next step
                }

                $current_step_index++;
                continue;
            }

            if ( ! empty( trim( render_block( $block ) ) ) ) {
                $current_step_blocks[] = $block;
            }
        }

        if ( ! empty( $current_step_blocks ) ) {
            $steps[] = $current_step_blocks;
        }

        if ( count( $steps ) <= 1 ) {
            $has_steps = false;
        }

        $template = get_post_meta( $post->ID, 'csf_form_template', true );
        $form_type = get_post_meta( $post->ID, 'csf_form_type', true );
        if ( ! $form_type ) {
            $form_type = 'normal';
        }
        $keep_session_data = get_post_meta( $post->ID, 'csf_form_keep_session_data', true );
        $show_page_heading = get_post_meta( $post->ID, 'csf_form_show_page_heading', true );
        $show_pagination = get_post_meta( $post->ID, 'csf_form_show_pagination', true );
        $conversational = get_post_meta( $post->ID, 'csf_form_conversational', true );
        $template_class = 'csf-template-default';
        if ( $template === 'boxed' ) {
            $template_class = 'csf-template-boxed';
        } elseif ( $template === 'underline' ) {
            $template_class = 'csf-template-underline';
        }

        if ( $conversational === '1' ) {
            $template_class .= ' csf-template-conversational';
        }

        ob_start();
        ?>
        <div class="csf-form-wrapper <?php echo esc_attr( trim( $template_class . ' ' . $atts['class'] . ' ' . get_post_meta( $post->ID, 'csf_form_class', true ) ) ); ?>">
            <?php if ( ! empty( $inline_css ) ) : ?>
                <style class="csf-inline-css"><?php echo wp_kses_post( $inline_css ); ?></style>
            <?php endif; ?>
            <form class="csf-form" data-id="<?php echo esc_attr( $atts['id'] ); ?>" enctype="multipart/form-data" data-keep-session="<?php echo esc_attr( $keep_session_data === '1' ? '1' : '' ); ?>" data-form-type="<?php echo esc_attr( $form_type ); ?>">
                <input type="hidden" name="form_id" value="<?php echo esc_attr( $atts['id'] ); ?>">
                <input type="hidden" name="action" value="csf_submit_form">
                <input type="hidden" name="page_url" value="<?php echo esc_attr( get_permalink() ); ?>">
                <input type="hidden" name="page_title" value="<?php echo esc_attr( get_the_title() ); ?>">
                <input type="hidden" name="csf_submit_nonce" value="<?php echo wp_create_nonce( 'csf_submit_nonce' ); ?>">
                
                <?php if ( $has_steps ) : ?>
                    <?php $total_steps = count( $steps ); ?>
                    <?php if ( $show_pagination === '1' || $show_page_heading === '1' ) : ?>
                        <div class="csf-progress-header">
                            <?php if ( $show_pagination === '1' ) : ?>
                                <div class="csf-progress-count" data-total="<?php echo esc_attr( $total_steps ); ?>">
                                    <span class="csf-progress-current">1</span>
                                    <span class="csf-progress-separator">/</span>
                                    <span class="csf-progress-total"><?php echo esc_html( $total_steps ); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ( $show_page_heading === '1' ) : ?>
                                <div class="csf-progress-title">
                                    <?php echo ! empty( $step_titles[1] ) ? esc_html( $step_titles[1] ) : ''; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $show_pagination === '1' ) : ?>
                                <div class="csf-progress-bar">
                                    <div class="csf-progress-line">
                                        <div class="csf-progress-line-fill"></div>
                                    </div>
                                    <?php foreach ( $steps as $i => $s ) : ?>
                                        <div class="csf-progress-step <?php echo $i === 0 ? 'active' : ''; ?>"></div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="csf-steps-container" style="width: 100%;">
                        <?php foreach ( $steps as $index => $step_blocks ) : ?>
                            <?php
                            $submit_block = null;
                            ?>
                            <div class="csf-step <?php echo $index === 0 ? 'active' : ''; ?>" data-step="<?php echo $index + 1; ?>" data-title="<?php echo isset( $step_titles[ $index + 1 ] ) ? esc_attr( $step_titles[ $index + 1 ] ) : ''; ?>">
                                <?php if ( $conversational === '1' ) : ?>
                                    <div class="csf-step-number-row">
                                        <span class="csf-step-number-pill"><?php echo esc_html( $index + 1 ); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php 
                                foreach ( $step_blocks as $block ) {
                                    if ( $block['blockName'] === 'csf/submit' ) {
                                        $submit_block = $block;
                                        continue;
                                    }
                                    echo render_block( $block );
                                }
                                ?>
                                <div class="csf-step-nav">
                                    <div class="csf-step-nav-left">
                                        <?php if ( $index > 0 ) : ?>
                                            <button type="button" class="csf-prev-step"><?php _e( 'Back', 'cotlas-simple-forms' ); ?></button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="csf-step-nav-right">
                                        <?php if ( $index < count( $steps ) - 1 ) : ?>
                                            <button type="button" class="csf-next-step">
                                                <?php
                                                if ( $conversational === '1' ) {
                                                    _e( 'OK', 'cotlas-simple-forms' );
                                                } else {
                                                    _e( 'Next', 'cotlas-simple-forms' );
                                                }
                                                ?>
                                            </button>
                                        <?php elseif ( $submit_block ) : ?>
                                            <?php echo render_block( $submit_block ); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <?php echo do_blocks( $post->post_content ); ?>
                <?php endif; ?>

                <?php if ( $site_key && $form_turnstile ) : ?>
                    <div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
                <?php endif; ?>

                <div class="csf-response-message"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function collect_custom_css_from_blocks( $blocks ) {
        $css = '';
        foreach ( $blocks as $block ) {
            if ( empty( $block['blockName'] ) ) {
                continue;
            }
            if ( in_array( $block['blockName'], array( 'csf/field', 'csf/heading', 'csf/text', 'csf/image', 'csf/html' ), true ) ) {
                if ( ! empty( $block['attrs']['customCss'] ) ) {
                    $css .= $block['attrs']['customCss'] . "\n";
                }
            }
            if ( ! empty( $block['innerBlocks'] ) ) {
                $css .= $this->collect_custom_css_from_blocks( $block['innerBlocks'] );
            }
        }
        return $css;
    }
}
