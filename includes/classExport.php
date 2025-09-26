<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoExport {
    public function boot() {
        add_filter( 'post_row_actions', [ $this, 'addExportLink' ], 10, 2 );
        add_action( 'init', [ $this, 'handleDirectDownload' ] );
    }

    /**
     * Adiciona o link "Exportar JSON" na listagem de posts (templates Converto).
     */
    public function addExportLink( $actions, $post ) {
        if ( in_array( $post->post_type, [ 'convertoPage', 'convertoSection' ], true ) ) {
            $url = add_query_arg( [
                'convertoExport' => $post->ID,
                '_wpnonce'       => wp_create_nonce( 'convertoExport_' . $post->ID ),
            ], admin_url( 'edit.php?post_type=' . $post->post_type ) );

            $actions['convertoExport'] = '<a href="' . esc_url( $url ) . '">Exportar JSON</a>';
        }
        return $actions;
    }

    /**
     * Gera o arquivo JSON com os dados do template exportado.
     * Inclui tanto o _elementor_data (conteúdo) quanto o _elementor_page_settings (CSS personalizado).
     */
    public function handleDirectDownload() {
        if ( empty( $_GET['convertoExport'] ) ) return;

        $id = intval( $_GET['convertoExport'] );
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'convertoExport_' . $id ) ) {
            wp_die( 'Ação não autorizada.' );
        }

        // Dados principais do Elementor
        $elementorData = get_post_meta( $id, '_elementor_data', true );
        $pageSettings  = get_post_meta( $id, '_elementor_page_settings', true );

        if ( ! $elementorData ) {
            wp_die( 'Template não encontrado.' );
        }

        // Monta estrutura de exportação
        $exportData = [
            'content'  => json_decode( $elementorData, true ), // conteúdo em array
            'settings' => $pageSettings ?: new \stdClass(),   // CSS personalizado
        ];

        // Saída JSON
        $filename = sanitize_file_name( get_post_field( 'post_name', $id ) . '.json' );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo wp_json_encode( $exportData );
        exit;
    }
}