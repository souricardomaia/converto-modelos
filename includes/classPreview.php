<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoPreview {
    public function boot() {
        add_action( 'init', [ $this, 'registerRewriteRules' ] );
        add_filter( 'query_vars', [ $this, 'addQueryVar' ] );
        add_action( 'template_redirect', [ $this, 'maybeRenderPreview' ] );
    }

    public function registerRewriteRules() {
        add_rewrite_rule( '^preview/([0-9]+)/?$', 'index.php?convertoPreview=$matches[1]', 'top' );
    }

    public function addQueryVar( $vars ) {
        $vars[] = 'convertoPreview';
        return $vars;
    }

    public function maybeRenderPreview() {
        $id = get_query_var( 'convertoPreview' );
        if ( ! $id ) return;

        status_header(200);
        nocache_headers();

        // 🔑 Libera uso dentro de iframe no Elementor
        header_remove('X-Frame-Options');
        header("Content-Security-Policy: frame-ancestors *");

        $this->renderMinimalTemplate( intval( $id ) );
        exit;
    }

    private function renderMinimalTemplate( $id ) {
        $html = '';
        if ( class_exists( '\\Elementor\\Plugin' ) ) {
            $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $id );
        } else {
            $post = get_post( $id );
            $html = apply_filters( 'the_content', $post ? $post->post_content : '' );
        }
        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo esc_html( get_the_title( $id ) ); ?></title>
<?php wp_head(); ?>
<style>html,body{margin:0;padding:0;background:#fff}</style>
</head>
<body class="converto-preview">
    <?php echo $html; ?>
    <?php wp_footer(); ?>
</body>
</html><?php
    }
}