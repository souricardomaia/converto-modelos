<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Classe responsável por adicionar a seção "CSS Personalizado"
 * aos documentos do Elementor (mesmo no Elementor Free).
 *
 * O valor é salvo no meta `_elementor_page_settings`,
 * que já é exportado/importado pelo plugin ConvertoModelos
 * e interpretado pelo tema Converto.
 */
class ConvertoCustomCss {

    public function __construct() {
        // Registra o hook no Elementor
        add_action( 'elementor/documents/register_controls', [ $this, 'registerCustomCssSection' ] );
    }

    /**
     * Adiciona a seção "CSS Personalizado" no painel do Elementor
     *
     * @param \Elementor\Core\Base\Document $document
     */
    public function registerCustomCssSection( $document ) {

        $document->start_controls_section(
            'converto_custom_css_section',
            [
                'label' => __( 'CSS Personalizado', 'converto-modelos' ),
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]
        );

        $document->add_control(
            'custom_css',
            [
                'label'       => __( 'Código CSS', 'converto-modelos' ),
                'type'        => \Elementor\Controls_Manager::CODE,
                'language'    => 'css',
                'rows'        => 20,
                'render_type' => 'none',
                'description' => __( 'Digite aqui o CSS customizado para este template.', 'converto-modelos' ),
            ]
        );

        $document->end_controls_section();
    }
}