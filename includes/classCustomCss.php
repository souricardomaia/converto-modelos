<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;
use Elementor\Core\DynamicTags\Dynamic_CSS;

/**
 * Classe responsável por adicionar suporte a CSS personalizado
 * em widgets, seções e configurações de página dentro do Elementor Free.
 *
 * O CSS é salvo em:
 * - _elementor_data (widgets e seções)
 * - _elementor_page_settings (configurações da página)
 *
 * E já é exportado/importado pelo Converto Modelos.
 */
class ConvertoCustomCss {

    public function __construct() {
        // Adiciona controles de CSS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customCssControlSection' ], 10, 3 );

        // Processa CSS personalizado de elementos
        add_action( 'elementor/element/parse_css', [ $this, 'customCssAddPost' ], 10, 2 );

        // Processa CSS personalizado da página
        add_action( 'elementor/css-file/post/parse', [ $this, 'customCssAddPageSettings' ] );

        // Injetar script para live preview no editor
        add_action( 'elementor/preview/enqueue_scripts', [ $this, 'enqueuePreviewScripts' ] );
    }

    /**
     * Cria a aba de "CSS Personalizado" em widgets e seções
     */
    public function customCssControlSection( $element, $section_id, $args ) {
        if ( $section_id === 'section_custom_css_pro' ) {

            $element->remove_control( 'section_custom_css_pro' );

            $element->start_controls_section(
                'section_custom_css',
                [
                    'label' => __( 'CSS', 'converto-modelos' ),
                    'tab'   => Controls_Manager::TAB_ADVANCED,
                ]
            );

            $element->add_control(
                'custom_css_title',
                [
                    'raw'  => __( 'Insira seu código CSS personalizado', 'converto-modelos' ),
                    'type' => Controls_Manager::RAW_HTML,
                ]
            );

            $element->add_control(
                'custom_css',
                [
                    'type'        => Controls_Manager::CODE,
                    'label'       => __( 'CSS', 'converto-modelos' ),
                    'language'    => 'css',
                    'render_type' => 'ui',
                    'show_label'  => false,
                    'separator'   => 'none',
                ]
            );

            $element->add_control(
                'custom_css_description',
                [
                    'raw'             => __( 'Use "selector" para se referir ao bloco como um todo. Ex: "selector a{color:red;}"', 'converto-modelos' ),
                    'type'            => Controls_Manager::RAW_HTML,
                    'content_classes' => 'elementor-descriptor',
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Aplica CSS personalizado salvo nos widgets/seções
     */
    public function customCssAddPost( $post_css, $element ) {
        if ( $post_css instanceof Dynamic_CSS ) {
            return;
        }

        $element_settings = $element->get_settings();

        if ( empty( $element_settings['custom_css'] ) ) {
            return;
        }

        $css = trim( $element_settings['custom_css'] );
        if ( empty( $css ) ) {
            return;
        }

        $css = str_replace( 'selector', $post_css->get_element_unique_selector( $element ), $css );
        $post_css->get_stylesheet()->add_raw_css( $css );
    }

    /**
     * Aplica CSS personalizado salvo nas configurações da página
     */
    public function customCssAddPageSettings( $post_css ) {
        $document   = \Elementor\Plugin::$instance->documents->get( $post_css->get_post_id() );
        $custom_css = $document->get_settings( 'custom_css' );
        $custom_css = trim( $custom_css );

        if ( empty( $custom_css ) ) {
            return;
        }

        $custom_css = str_replace( 'selector', $document->get_css_wrapper_selector(), $custom_css );
        $post_css->get_stylesheet()->add_raw_css( $custom_css );
    }

    /**
     * Injeta script para atualizar CSS em tempo real no preview
     */
    public function enqueuePreviewScripts() {
        ?>
        <script>
        jQuery(window).on('elementor:init', function() {
            // Atualização em tempo real para elementos (widgets/seções)
            elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view){
                model.on('change:settings', function() {
                    if (model.changed && model.changed.custom_css !== undefined) {
                        var css = model.get('settings').get('custom_css');
                        var id  = model.get('id');
                        var styleId = 'converto-custom-css-' + id;

                        jQuery('#' + styleId).remove();

                        if (css && css.length > 0) {
                            css = css.replace(/selector/g, '.elementor-element.elementor-element-' + id);
                            jQuery('<style>', { id: styleId, text: css }).appendTo('head');
                        }
                    }
                });
            });

            // Atualização em tempo real para CSS da página
            elementor.channels.editor.on('change:document:settings', function(model) {
                if (model.changed && model.changed.custom_css !== undefined) {
                    var css = model.get('custom_css') || '';
                    var styleId = 'converto-custom-css-page';

                    jQuery('#' + styleId).remove();

                    if (css && css.length > 0) {
                        var wrapper = elementor.settings.page.model.get('cssWrapperSelector') || 'body';
                        css = css.replace(/selector/g, wrapper);
                        jQuery('<style>', { id: styleId, text: css }).appendTo('head');
                    }
                }
            });
        });
        </script>
        <?php
    }
}