<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoAjax {
    public function registerAjaxActions( $ajax ) {
        $ajax->register_ajax_action( 'customLibraryData', function( $data ) {
            $lib = new ConvertoLibrary();
            return $lib->getLibraryPayload();
        } );

        $ajax->register_ajax_action( 'get_template_data', function( $data ) {
            $id = isset( $data['template_id'] ) ? intval( $data['template_id'] ) : 0;
            return $this->getTemplateData( $id );
        } );

        $ajax->register_ajax_action( 'mark_template_as_favorite', function( $data ) {
            $templateId = isset( $data['template_id'] ) ? sanitize_text_field( $data['template_id'] ) : '';
            $favorite = isset( $data['favorite'] ) ? filter_var( $data['favorite'], FILTER_VALIDATE_BOOLEAN ) : false;
            return $this->markTemplateAsFavorite( $templateId, $favorite );
        } );
    }

    private function getTemplateData( $id ) {
        if ( ! $id ) return new WP_Error( 'no_id', 'ID inválido', [ 'status' => 400 ] );

        $elementorData = get_post_meta( $id, '_elementor_data', true );
        if ( ! $elementorData ) return new WP_Error( 'no_data', 'Template não encontrado', [ 'status' => 404 ] );

        return json_decode( $elementorData, true );
    }

    private function markTemplateAsFavorite( $templateId, $favorite ) {
        $userId = get_current_user_id();
        if ( ! $userId ) return new WP_Error( 'not_logged', 'Usuário não logado', [ 'status' => 401 ] );

        $favoritesUtil = new ConvertoFavorites();
        $favorites = $favoritesUtil->setFavorite( $userId, $templateId, $favorite );

        return [ 'success' => true, 'favorites' => $favorites ];
    }
}
