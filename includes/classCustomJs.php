<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Dynamic_CSS;

class ConvertoCustomJs {

    public function __construct() {
        // Adiciona controles de JS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 20, 3 );

        // Processa JS dos elementos individuais
        add_action( 'elementor/element/parse_css', [ $this, 'customJsAddElement' ], 20, 2 );

        // Processa JS da página
        add_action( 'elementor/css-file/post/parse', [ $this, 'customJsAddPageSettings' ] );

        // Executa JS no frontend
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueueFrontend' ] );
    }

    /**
     * Cria aba "JS Personalizado"
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
     * Salva JS personalizado nos widgets/seções
     */
    public function customJsAddElement( $post_css, $element ) {
        $settings = $element->get_settings();
        if ( empty( $settings['custom_js'] ) ) return;

        $custom_js = trim( $settings['custom_js'] );
        if ( empty( $custom_js ) ) return;

        // injeta como atributo para o frontend rodar
        $element->add_render_attribute( '_wrapper', 'data-custom-js', base64_encode( $custom_js ) );
    }

    /**
     * Salva JS personalizado nas configs da página
     */
    public function customJsAddPageSettings( $post_css ) {
        $document   = \Elementor\Plugin::$instance->documents->get( $post_css->get_post_id() );
        $custom_js  = trim( $document->get_settings( 'custom_js' ) );

        if ( empty( $custom_js ) ) return;

        add_filter( 'elementor/frontend/the_content', function( $content ) use ( $custom_js ) {
            $encoded = base64_encode( $custom_js );
            return '<div data-custom-js="' . esc_attr( $encoded ) . '">' . $content . '</div>';
        });
    }

    /**
     * Executor no frontend
     */
    public function enqueueFrontend() {
        ?>
        <script>
        (function($){
            "use strict";
            $(function(){
                $('[data-custom-js]').each(function(){
                    const $el = $(this);
                    const encoded = $el.data('custom-js');
                    if (!encoded) return;
                    try {
                        const code = atob(encoded);
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