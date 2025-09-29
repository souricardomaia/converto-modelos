<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

class ConvertoCustomJs {

    public function __construct() {
        // Controles de JS em widgets/seções
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 10, 3 );

        // Controle de JS nas configurações da página
        add_action( 'elementor/documents/register_controls', [ $this, 'registerPageJsControl' ] );

        // Marca elementos com data-custom-js (frontend + editor)
        add_action( 'elementor/frontend/element/before_render', [ $this, 'beforeRender' ], 10, 1 );

        // Executor no footer (frontend)
        add_action( 'wp_footer', [ $this, 'printExecutor' ], 99 );
    }

    /**
     * Aba "JS" nos elementos
     */
    public function customJsControlSection( $element, $section_id, $args ) {
        if ( $section_id !== 'section_custom_css_pro' ) return;

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
                'type'                => Controls_Manager::CODE,
                'label'               => __( 'JavaScript', 'converto-modelos' ),
                'language'            => 'javascript',
                'render_type'         => 'none',          // ← não apenas UI
                'frontend_available'  => true,            // ← acessível no frontend
                'show_label'          => false,
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Controle de JS nas configurações da página
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
     * Antes de renderizar, marca o elemento com data-custom-js (base64)
     */
    public function beforeRender( $element ) {
        // use get_settings() (e não get_settings_for_display) para garantir o valor
        $settings = $element->get_settings();
        $code     = isset( $settings['custom_js'] ) ? trim( (string) $settings['custom_js'] ) : '';

        if ( $code === '' ) return;

        $encoded = base64_encode( $code );
        $element->add_render_attribute( '_wrapper', 'data-custom-js', esc_attr( $encoded ) );
    }

    /**
     * Imprime executor no footer:
     * - roda data-custom-js por elemento
     * - roda o JS de página (se existir)
     * - reexecuta em elementos carregados dinamicamente via Elementor
     */
    public function printExecutor() {
        $pageJs = '';

        if ( did_action( 'elementor/loaded' ) ) {
            $doc = \Elementor\Plugin::$instance->documents->get( get_the_ID() );
            if ( $doc ) {
                // tenta meta; se vier vazio, tenta settings
                $pageJs = trim( (string) $doc->get_meta( 'custom_js' ) );
                if ( $pageJs === '' ) {
                    $pageJs = trim( (string) $doc->get_settings( 'custom_js' ) );
                }
            }
        }
        ?>
        <script>
        (function($){
            function runCustomJsIn($root){
                $root.find('[data-custom-js]').each(function(){
                    var $el = $(this);
                    if ($el.data('js-ran')) return;
                    $el.data('js-ran', true);
                    try {
                        var encoded = $el.attr('data-custom-js');
                        if (!encoded) return;
                        var code = atob(encoded);
                        (function(selector, $){
                            eval(code);
                        })($el, jQuery);
                    } catch(e){
                        console.error('Erro no Custom JS:', e);
                    }
                });
            }

            $(function(){
                // Passo inicial
                runCustomJsIn($(document));

                // Suporte ao ciclo de vida do Elementor (widgets que entram depois)
                if (window.elementorFrontend && elementorFrontend.hooks) {
                    elementorFrontend.hooks.addAction('frontend/element_ready/global', function(scope){
                        runCustomJsIn($(scope));
                    });
                }

                // JS da página (inteiro)
                <?php if ( $pageJs !== '' ) : ?>
                try {
                    (function($){
                        <?php echo $pageJs; // autor controla este conteúdo ?>
                    })(jQuery);
                } catch(e){
                    console.error('Erro no Custom JS da Página:', e);
                }
                <?php endif; ?>
            });
        })(jQuery);
        </script>
        <?php
    }
}