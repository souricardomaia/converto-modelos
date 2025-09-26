<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoRest {
    public function registerRoutes() {
        register_rest_route( 'converto-modelos/v1', '/customLibraryData', [
            'methods'  => 'GET',
            'callback' => [ $this, 'getLibraryData' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'converto-modelos/v1', '/getTemplateData/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [ $this, 'getTemplateData' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'converto-modelos/v1', '/markTemplateAsFavorite', [
            'methods'  => 'POST',
            'callback' => [ $this, 'markTemplateAsFavorite' ],
            'permission_callback' => function() { return is_user_logged_in(); },
        ] );
    }

    public function getLibraryData( $request ) {
        $lib = new ConvertoLibrary();
        return $lib->getLibraryPayload();
    }

    public function getTemplateData( $request ) {
        $id = (int) $request['id'];
        $elementorData = get_post_meta( $id, '_elementor_data', true );
    
        if ( ! $elementorData ) {
            return new WP_Error( 'no_data', 'Template não encontrado', [ 'status' => 404 ] );
        }
    
        $content = json_decode( $elementorData, true );
        if ( ! is_array( $content ) ) {
            $content = [];
        }
    
        $post_type = get_post_type( $id );
        $type = ($post_type === 'converto_page') ? 'page' : 'section';
    
        return [
            'template_id'   => $id,
            'title'         => get_the_title( $id ),
            'type'          => $type,
            'content_type'  => $type,
            'subtype'       => '',
            'version'       => ELEMENTOR_VERSION,
            'content'       => $content,         // ✅ direto, igual no lgr.builder.php
            'page_settings' => new \stdClass(),
            'metadata'      => new \stdClass(),
        ];
    }


    public function markTemplateAsFavorite( $request ) {
        $userId     = get_current_user_id();
        $templateId = sanitize_text_field( $request['template_id'] );
        $favorite   = filter_var( $request['favorite'], FILTER_VALIDATE_BOOLEAN );

        if ( ! $userId ) {
            return new WP_Error( 'not_logged', 'Usuário não logado', [ 'status' => 401 ] );
        }

        $favoritesUtil = new ConvertoFavorites();
        $favorites     = $favoritesUtil->setFavorite( $userId, $templateId, $favorite );

        return [
            'success'   => true,
            'favorites' => $favorites,
        ];
    }
}
