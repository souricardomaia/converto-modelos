<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Converto Modelos – Metabox para Disponibilidade (is_pro)
 */
class ConvertoMeta {

    public function __construct() {
        // Adiciona metabox
        add_action( 'add_meta_boxes', [ $this, 'registerMetabox' ] );

        // Salva o campo
        add_action( 'save_post', [ $this, 'saveMetabox' ] );
    }

    /**
     * Registra o metabox nos CPTs de modelos
     */
    public function registerMetabox() {
        $screens = ['convertoPage', 'convertoSection']; // seus CPTs de modelos
        foreach ( $screens as $screen ) {
            add_meta_box(
                'converto_is_pro',
                __( 'Exclusivo', 'converto-modelos' ),
                [ $this, 'renderMetabox' ],
                $screen,
                'side',
                'default'
            );
        }
    }

    /**
     * Renderiza o campo no metabox
     */
    public function renderMetabox( $post ) {
        $value = get_post_meta( $post->ID, '_converto_is_pro', true );
        $checked = checked( $value, '1', false );

        wp_nonce_field( 'converto_is_pro_nonce', 'converto_is_pro_nonce_field' );
        ?>
        <p>
            <label>
                <input type="checkbox" name="converto_is_pro" value="1" <?php echo $checked; ?> />
                <?php _e( 'Modelo exclusivo para membros', 'converto-modelos' ); ?>
            </label>
        </p>
        <p class="description">
            <?php _e( 'Somente clientes ativos podem baixar este modelo', 'converto-modelos' ); ?>
        </p>
        <?php
    }

    /**
     * Salva o campo
     */
    public function saveMetabox( $post_id ) {
        if ( ! isset( $_POST['converto_is_pro_nonce_field'] ) ||
             ! wp_verify_nonce( $_POST['converto_is_pro_nonce_field'], 'converto_is_pro_nonce' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        if ( isset( $_POST['converto_is_pro'] ) ) {
            update_post_meta( $post_id, '_converto_is_pro', '1' );
        } else {
            update_post_meta( $post_id, '_converto_is_pro', '0' );
        }
    }

    /**
     * Retorna o valor booleano (helper)
     */
    public static function getIsPro( $post_id ) {
        return get_post_meta( $post_id, '_converto_is_pro', true ) === '1';
    }
}