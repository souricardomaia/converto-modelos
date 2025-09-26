<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoCpt {

    public function register() {
        $this->registerTaxonomies();
        $this->registerPostTypes();
    }

    private function registerPostTypes() {
        register_post_type( 'convertoPage', [
            'label' => 'Modelos de Páginas',
            'public' => true,
            'show_in_rest' => true,
            'supports' => [ 'title', 'thumbnail', 'author' ],
            'menu_icon' => 'dashicons-align-left',
            'has_archive' => false,
            'rewrite' => [ 'slug' => 'converto-page' ],
            'taxonomies' => [ 'pageCategory', 'convertoTag' ],
        ] );

        register_post_type( 'convertoSection', [
            'label' => 'Modelos de Seções',
            'public' => true,
            'show_in_rest' => true,
            'supports' => [ 'title', 'thumbnail', 'author' ],
            'menu_icon' => 'dashicons-align-center',
            'has_archive' => false,
            'rewrite' => [ 'slug' => 'converto-section' ],
            'taxonomies' => [ 'sectionCategory', 'convertoTag' ],
        ] );
    }

    private function registerTaxonomies() {
        register_taxonomy( 'pageCategory', [ 'convertoPage' ], [
            'labels' => [
                'name' => 'Categorias de Página',
                'singular_name' => 'Categoria de Página',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'show_in_quick_edit' => true,
            'rewrite' => [ 'slug' => 'page-category' ],
        ] );

        register_taxonomy( 'sectionCategory', [ 'convertoSection' ], [
            'labels' => [
                'name' => 'Categorias de Seção',
                'singular_name' => 'Categoria de Seção',
            ],
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'show_in_quick_edit' => true,
            'rewrite' => [ 'slug' => 'section-category' ],
        ] );

        register_taxonomy( 'convertoTag', [ 'convertoPage', 'convertoSection' ], [
            'labels' => [
                'name' => 'Tags',
                'singular_name' => 'Tag',
            ],
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
            'show_in_quick_edit' => true,
            'rewrite' => [ 'slug' => 'converto-tag' ],
        ] );
    }
}
