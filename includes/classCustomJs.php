<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class ConvertoCustomJs {

    public function __construct() {
        // Adiciona controles de JS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 10, 3 );

        // Adiciona controle de JS nas configurações da página
        add_action( 'elementor/documents/register_controls', [ $this, 'registerPageJsControl' ] );

        // Marca elementos com JS antes do render (corrigido para rodar no frontend publicado também)
        add_action( 'elementor/frontend/element/before_render', [ $this, 'beforeRender' ], 10, 1 );

        // Injeta executor no footer
        add_action( 'wp_footer', [ $this, 'printExecutor' ], 99 );
    }

    /**
     * Cria a aba "JS Personalizado" nos elementos
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
                'custom_js',
                [
                    'type'        => Controls_Manager::CODE,
                    'label'       => __( 'JavaScript', 'converto-modelos' ),
                    'language'    => 'javascript',
                    'render_type' => 'ui',
                    'show_label'  => false,
                ]
            );

            $element->end_controls_section();
        }
    }

    /**
     * Adiciona controle de JS nas configurações da página
     */
    public function registerPageJsControl( $document ) {
        $document->start_controls_section(
            'section_page_custom_js',
            [
                'label' => __( 'JS Personalizado', 'converto-modelos' ),
                'tab'   => Controls_Manager::TAB_SETTINGS,
            ]
        );

        $document->add_control(
            'custom_js',
            [
                'type'        => Controls_Manager::CODE,
                'label'       => __( 'JavaScript da Página', 'converto-modelos' ),
                'language'    => 'javascript',
                'render_type' => 'none',
                'show_label'  => false,
            ]
        );

        $document->end_controls_section();
    }

    /**
     * Antes de renderizar, marca o elemento com atributo data-custom-js
     */
    public function beforeRender( $element ) {
        $settings = $element->get_settings_for_display();
        if ( empty( $settings['custom_js'] ) ) return;

        $code = trim( $settings['custom_js'] );
        if ( ! $code ) return;

        // Codifica para evitar conflitos
        $encoded = base64_encode( $code );
        $element->add_render_attribute( '_wrapper', 'data-custom-js', $encoded );
    }

    /**
     * Imprime executor no footer do site
     */
    public function printExecutor() {
        // Coleta JS da página atual
        $pageJs = '';
        if ( function_exists( 'elementor_theme_do_location' ) ) {
            $doc = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
            if ( $doc ) {
                // Usar get_meta() para garantir que o campo custom_js da página seja recuperado no frontend
                $pageJs = trim( $doc->get_meta( 'custom_js' ) );
            }
        }
        ?>
        <script>
        (function($){
            $(function(){
                // JS por elemento
                $('[data-custom-js]').each(function(){
                    const $el = $(this);
                    if ($el.data('js-ran')) return;
                    $el.data('js-ran', true);

                    try {
                        const code = atob($el.data('custom-js'));
                        (function(selector,$){
                            eval(code);
                        })($el, jQuery);
                    } catch(e){
                        console.error("Erro no Custom JS:", e);
                    }
                });

                // JS de página inteira
                <?php if ( ! empty( $pageJs ) ) : ?>
                try {
                    (function($){
                        <?php echo $pageJs; ?>
                    })(jQuery);
                } catch(e){
                    console.error("Erro no Custom JS da Página:", e);
                }
                <?php endif; ?>
            });
        })(jQuery);
        </script>
        <?php
    }
}