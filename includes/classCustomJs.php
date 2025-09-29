<?php
if ( ! defined( 'ABSPATH' ) ) exit;

use Elementor\Controls_Manager;

/**
 * Converto Modelos – JS personalizado (compatível com Converto Theme)
 * - Live preview no editor (via data-custom-js + JS do editor)
 * - Execução no frontend (wp_footer) varrendo os elementos do documento
 * - Suporte a JS de página (Settings do Documento)
 */
class ConvertoCustomJs {

    public function __construct() {
        // 1) Adiciona controle "JS" nos elementos (Advanced)
        add_action( 'elementor/element/after_section_end', [ $this, 'customJsControlSection' ], 20, 3 );

        // 2) Marca elementos com data-custom-js para o preview/DOM
        add_action( 'elementor/frontend/element/before_render', [ $this, 'customJsAddDataAttr' ], 10, 1 );

        // 3) Injeta JS definido nas configurações da página no handle elementor-frontend
        add_action( 'elementor/css-file/post/parse', [ $this, 'customJsAddPageSettings' ] );

        // 4) No footer, imprime JS de todos os elementos com custom_js
        add_action( 'wp_footer', [ $this, 'customJsRenderAll' ], 999 );

        add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueueEditorScripts' ] );
    }

    /**
     * Cria a aba "JS" após a seção de Custom CSS (free e pro)
     */
    public function customJsControlSection( $element, $section_id, $args ) {
        if ( $section_id !== 'section_custom_css' && $section_id !== 'section_custom_css_pro' ) {
            return;
        }

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
                'render_type' => 'ui',    // live preview
                'show_label'  => false,
                'separator'   => 'none',
            ]
        );

        $element->end_controls_section();
    }

    /**
     * Marca o wrapper do elemento com data-custom-js (base64) para o editor/preview.
     */
    public function customJsAddDataAttr( $element ) {
        $settings = $element->get_settings_for_display();
        if ( empty( $settings['custom_js'] ) ) {
            return;
        }

        $js = trim( (string) $settings['custom_js'] );
        if ( $js === '' ) {
            return;
        }

        $wrapper = ".elementor-element.elementor-element-" . $element->get_id();

        // Substitui "selector" pelo wrapper jQuery deste elemento
        $js = preg_replace( '/\bselector\b/', "jQuery('{$wrapper}')", $js );

        $element->add_render_attribute(
            '_wrapper',
            [ 'data-custom-js' => base64_encode( $js ) ]
        );
    }

    /**
     * Adiciona o JS das configurações da página (documento) no handle elementor-frontend.
     */
    public function customJsAddPageSettings( $post ) {
        $document = \Elementor\Plugin::$instance->documents->get( $post->get_post_id() );
        if ( ! $document ) {
            return;
        }

        $custom_js = trim( (string) $document->get_settings( 'custom_js' ) );
        if ( $custom_js === '' ) {
            return;
        }

        // Substitui "selector" pelo wrapper CSS da página
        $custom_js = preg_replace(
            '/\bselector\b/',
            "jQuery('".$document->get_css_wrapper_selector()."')",
            $custom_js
        );

        wp_add_inline_script(
            'elementor-frontend',
            "(function($){ try { {$custom_js} } catch(e){ console.error('Custom JS Page Error:', e); } })(jQuery);"
        );
    }

    /**
     * Percorre a estrutura do documento e imprime, no footer, o JS de cada elemento com custom_js.
     */
    public function customJsRenderAll() {
        if ( is_admin() ) {
            return; // evita rodar no admin
        }

        global $post;
        if ( ! $post ) {
            return;
        }

        $document = \Elementor\Plugin::$instance->documents->get( $post->ID );
        if ( ! $document ) {
            return;
        }

        $elements = $document->get_elements_data();
        if ( empty( $elements ) || ! is_array( $elements ) ) {
            return;
        }

        $output = $this->collectElementsJs( $elements );

        if ( $output === '' ) {
            return;
        }

        echo "<script>(function($){\ntry{\n{$output}\n}catch(e){console.error('Custom JS Error:',e);} \n})(jQuery);</script>\n";
    }



    public function enqueueEditorScripts() {
        wp_enqueue_script(
            'converto-modelos-livepreview',
            CONVERTO_MODELOS_URL . 'assets/livePreview.js',
            ['jquery','elementor-editor'],
            '1.0.0',
            true
        );
    }


    /**
     * Coleta o JS dos elementos (recursivo).
     */
    protected function collectElementsJs( array $elements ) {
        $out = '';

        foreach ( $elements as $element ) {
            if ( empty( $element['elType'] ) ) {
                continue;
            }

            // Se o elemento tem settings e custom_js, renderiza
            if ( ! empty( $element['settings']['custom_js'] ) ) {
                $id   = isset( $element['id'] ) ? $element['id'] : '';
                $code = trim( (string) $element['settings']['custom_js'] );

                if ( $id !== '' && $code !== '' ) {
                    $wrapper = ".elementor-element.elementor-element-{$id}";
                    $code    = preg_replace( '/\bselector\b/', "jQuery('{$wrapper}')", $code );

                    // Minificação básica para reduzir tamanho
                    $code = preg_replace( '/\s+/', ' ', $code );
                    $code = preg_replace( '/\s*([{}();,:])\s*/', '$1', $code );

                    $out .= "(function($){try{{$code}}catch(e){console.error('Custom JS Error:',e);}})(jQuery);\n";
                }
            }

            // Filhos (content-structures)
            if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
                $out .= $this->collectElementsJs( $element['elements'] );
            }
        }

        return $out;
    }
}