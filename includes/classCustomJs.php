<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

/**
 * Classe responsável por adicionar suporte a JS personalizado
 * em widgets, seções e configurações de página dentro do Elementor Free.
 *
 * O JS é salvo em:
 * - _elementor_data (widgets/seções)
 * - _elementor_page_settings (configurações da página)
 *
 * E já é exportado/importado pelo Converto Modelos.
 */
class ConvertoCustomJs {

    public function __construct() {
        // Adiciona controles de JS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 20, 3 );

        // Processa JS personalizado da página
        add_action( 'elementor/css-file/post/parse', [ $this, 'customJsAddPageSettings' ] );

        // Executa JS no frontend
        add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueueFrontend' ] );
    }

    /**
     * Cria a aba de "JS Personalizado" em widgets e seções
     */
    public function customJsControlSection( $element, $section_id, $args ) {
        if ( $section_id === 'section_custom_css' ) { // insere logo depois do CSS
            $element->start_controls_section(
                'section_custom_js',
                [
                    'label' => __( 'JS', 'converto-modelos' ),
                    'tab'   => Controls_Manager::TAB_ADVANCED,
                ]
            );

            $element->add_control(
                'custom_js_title',
                [
                    'raw'  => __( 'Insira seu código JavaScript personalizado', 'converto-modelos' ),
                    'type' => Controls_Manager::RAW_HTML,
                ]
            );

            $element->add_control(
                'custom_js',
                [
                    'type'        => Controls_Manager::CODE,
                    'label'       => __( 'JS', 'converto-modelos' ),
                    'language'    => 'javascript',
                    'render_type' => 'none',
                    'show_label'  => false,
                    'separator'   => 'none',
                ]
            );

            $element->add_control(
                'custom_js_description',
                [
                    'raw'             => __( 'O código será executado dentro de uma função com acesso ao elemento atual como "selector".', 'converto-modelos' ),
                    'type'            => Controls_Manager::RAW_HTML,
                    'content_classes' => 'elementor-descriptor',
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Aplica JS personalizado salvo nas configurações da página
     * (injeção no HTML como atributo data-custom-js para o preview rodar)
     */
    public function customJsAddPageSettings( $post_css ) {
        $document   = \Elementor\Plugin::$instance->documents->get( $post_css->get_post_id() );
        if ( ! $document ) return;

        $custom_js = trim( $document->get_settings( 'custom_js' ) );
        if ( empty( $custom_js ) ) return;

        add_filter( 'elementor/frontend/the_content', function( $content ) use ( $custom_js ) {
            $encoded = base64_encode( $custom_js );
            return '<div data-custom-js="' . esc_attr( $encoded ) . '">' . $content . '</div>';
        });
    }

    /**
     * Injeta suporte no frontend (widgets/seções)
     */
    public function enqueueFrontend() {
        ?>
        <script>
        (function($){
            "use strict";

            // roda para elementos com atributo data-custom-js
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