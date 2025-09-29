<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class ConvertoCustomJs {

    public function __construct() {
        // Aba "JS Personalizado"
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 20, 3 );

        // Injetar atributo nos elementos no frontend
        add_action( 'elementor/frontend/element/before_render', [ $this, 'injectElementJsAttr' ] );

        // Injetar atributo para page settings
        add_action( 'elementor/frontend/before_render', [ $this, 'injectPageJsAttr' ] );

        // Script executor no frontend
        add_action( 'wp_footer', [ $this, 'enqueueFrontend' ], 99 );
    }

    /**
     * Adiciona a aba "JS" no painel do Elementor
     */
    public function customJsControlSection( $element, $section_id, $args ) {
        if ( $section_id === 'section_custom_css' ) {
            $element->start_controls_section(
                'section_custom_js',
                [
                    'label' => __( 'JS', 'converto-modelos' ),
                    'tab'   => Controls_Manager::TAB_ADVANCED,
                ]
            );

            $element->add_control(
                'custom_js',
                [
                    'type'        => Controls_Manager::CODE,
                    'label'       => __( 'JavaScript', 'converto-modelos' ),
                    'language'    => 'javascript',
                    'render_type' => 'none',
                    'show_label'  => false,
                    'separator'   => 'none',
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Injeta o atributo data-custom-js nos elementos individuais
     */
    public function injectElementJsAttr( $element ) {
        $settings = $element->get_settings_for_display();
        if ( empty( $settings['custom_js'] ) ) return;

        $custom_js = trim( $settings['custom_js'] );
        if ( empty( $custom_js ) ) return;

        $element->add_render_attribute( '_wrapper', 'data-custom-js', base64_encode( $custom_js ) );
    }

    /**
     * Injeta para page settings
     */
    public function injectPageJsAttr( $element ) {
        if ( ! method_exists( $element, 'get_settings' ) ) return;

        $custom_js = trim( $element->get_settings( 'custom_js' ) );
        if ( empty( $custom_js ) ) return;

        echo '<div data-custom-js="' . esc_attr( base64_encode( $custom_js ) ) . '"></div>';
    }

    /**
     * Script executor no frontend
     */
    public function enqueueFrontend() {
        ?>
        <script>
        (function($){
            "use strict";
            $(function(){
                $('[data-custom-js]').each(function(){
                    var $el = $(this);
                    var encoded = $el.data('custom-js');
                    if (!encoded) return;
                    try {
                        var code = atob(encoded);
                        (function(selector,$){
                            eval(code);
                        })($el, jQuery);
                    } catch (e) {
                        console.error("Erro no Custom JS:", e);
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}