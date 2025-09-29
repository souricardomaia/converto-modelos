<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

/**
 * Classe responsável por adicionar suporte a JS personalizado
 * em widgets, seções e configurações de página dentro do Elementor Free.
 *
 * O JS é salvo em:
 * - _elementor_data (widgets e seções)
 * - _elementor_page_settings (configurações da página)
 *
 * E já é exportado/importado pelo Converto Modelos.
 */
class ConvertoCustomJs {

    public function __construct() {
        // Adiciona controles de JS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 10, 3 );

        // Adiciona controle de JS nas configurações da página
        add_action( 'elementor/documents/register_controls', [ $this, 'addPageSettingsControl' ] );

        // Injeta o JS coletado no frontend
        add_action( 'wp_footer', [ $this, 'printCollectedJs' ], 100 );
    }

    /**
     * Cria a aba de "JS Personalizado" em widgets e seções
     */
    public function customJsControlSection( $element, $section_id, $args ) {
        if ( $section_id === 'section_custom_css_pro' ) {
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
                    'raw'  => __( 'Insira seu código JS personalizado', 'converto-modelos' ),
                    'type' => Controls_Manager::RAW_HTML,
                ]
            );

            $element->add_control(
                'custom_js',
                [
                    'type'        => Controls_Manager::CODE,
                    'label'       => __( 'JS', 'converto-modelos' ),
                    'language'    => 'javascript',
                    'render_type' => 'ui',
                    'show_label'  => false,
                    'separator'   => 'none',
                ]
            );

            $element->add_control(
                'custom_js_description',
                [
                    'raw'             => __( 'Use apenas código JS válido. Não inclua tags &lt;script&gt;. O código será executado no frontend.', 'converto-modelos' ),
                    'type'            => Controls_Manager::RAW_HTML,
                    'content_classes' => 'elementor-descriptor',
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Cria o campo "JS Personalizado" nas configurações da página
     */
    public function addPageSettingsControl( $document ) {
        if ( ! $document::get_property( 'has_elements' ) ) {
            return;
        }

        $document->start_controls_section(
            'section_custom_js',
            [
                'label' => __( 'JS Personalizado', 'converto-modelos' ),
                'tab'   => Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'custom_js',
            [
                'type'        => Controls_Manager::CODE,
                'label'       => 'JS',
                'language'    => 'javascript',
                'rows'        => 12,
                'show_label'  => false,
                'render_type' => 'ui',
            ]
        );

        $document->end_controls_section();
    }

    /**
     * Injeta no rodapé o JS coletado de:
     * - Widgets e seções
     * - Configurações da página
     */
    public function printCollectedJs() {
        if ( is_admin() && ! defined( 'ELEMENTOR_PREVIEW_DOING_AJAX' ) ) {
            return; // não roda no admin normal
        }

        $document_id = get_the_ID();
        if ( ! $document_id ) {
            return;
        }

        $elementor_data = get_post_meta( $document_id, '_elementor_data', true );
        $page_settings  = get_post_meta( $document_id, '_elementor_page_settings', true );

        $scripts = [];

        // 🔹 Coleta JS dos elementos
        if ( $elementor_data ) {
            $data = json_decode( $elementor_data, true );
            $this->walkElementsForJs( $data, $scripts );
        }

        // 🔹 Coleta JS da página
        if ( ! empty( $page_settings['custom_js'] ) ) {
            $scripts[] = $page_settings['custom_js'];
        }

        // 🔹 Imprime no rodapé
        if ( ! empty( $scripts ) ) {
            echo "<script id='converto-custom-js'>\n";
            foreach ( $scripts as $script ) {
                echo $script . "\n";
            }
            echo "</script>";
        }
    }

    /**
     * Walker recursivo para coletar custom_js de todos os elementos
     */
    private function walkElementsForJs( $elements, &$scripts ) {
        foreach ( $elements as $el ) {
            if ( ! empty( $el['settings']['custom_js'] ) ) {
                $scripts[] = $el['settings']['custom_js'];
            }
            if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
                $this->walkElementsForJs( $el['elements'], $scripts );
            }
        }
    }
}