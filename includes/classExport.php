<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoExport {
    public function boot() {
        add_filter( 'post_row_actions', [ $this, 'addExportLink' ], 10, 2 );
        add_action( 'init', [ $this, 'handleDirectDownload' ] );
    }

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

    public function handleDirectDownload() {
        if ( empty( $_GET['convertoExport'] ) ) return;

        $id = intval( $_GET['convertoExport'] );
        if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'convertoExport_' . $id ) ) {
            wp_die( 'Ação não autorizada.' );
        }

        $elementorData = get_post_meta( $id, '_elementor_data', true );
        if ( ! $elementorData ) {
            wp_die( 'Template não encontrado.' );
        }

        $filename = sanitize_file_name( get_post_field( 'post_name', $id ) . '.json' );
        nocache_headers();
        header( 'Content-Type: application/json; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        echo $elementorData;
        exit;
    }
}
