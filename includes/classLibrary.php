<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ConvertoLibrary {

    public function getLibraryPayload() {
        return [
            'library' => [
                'templates'  => $this->getTemplates(),
                'types_data' => [
                    'page' => [
                        'categories' => $this->getPageCategories(),
                    ],
                    'section' => [
                        'categories' => $this->getSectionCategories(),
                    ]
                ]
            ]
        ];
    }

    private function getTemplates() {
        $posts = get_posts([
            'post_type'      => [ 'convertoPage', 'convertoSection' ],
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ]);

        $templates = [];
        foreach ( $posts as $post ) {
            $type = $post->post_type === 'convertoPage' ? 'page' : 'section';

            // Thumbnail individual de cada post
            $thumbnail = get_the_post_thumbnail_url( $post->ID, 'medium' );
            if ( ! $thumbnail ) {
                $thumbnail = CONVERTO_MODELOS_URL . 'assets/thumbnail.png';
            }

            // Termos
            $categories = wp_get_post_terms( 
                $post->ID, 
                $type === 'page' ? 'pageCategory' : 'sectionCategory', 
                [ 'fields' => 'slugs' ] 
            );
            $tags = wp_get_post_terms( $post->ID, 'convertoTag', [ 'fields' => 'names' ] );

            $templates[] = [
                'template_id' => $post->ID,
                'title'       => $post->post_title,
                'source'      => 'lgrTemplates',
                'type'        => $type,
                'subtype'     => $categories ? $categories[0] : null,
                'author'      => get_the_author_meta( 'display_name', $post->post_author ),
                'thumbnail'   => $thumbnail,
                'url'         => get_permalink( $post->ID ),
                'preview_url' => home_url( '/preview/' . $post->ID ),
                'file' => rest_url( 'converto-modelos/v1/getTemplateData/' . $post->ID ),
                'tags'        => $tags,
                'favorite'    => false
            ];
        }

        return $templates;
    }

    private function getPageCategories() {
        $terms = get_terms([
            'taxonomy'   => 'pageCategory',
            'hide_empty' => false,
        ]);

        return array_map( function( $term ) {
            return [
                'slug'  => $term->slug,
                'label' => $term->name,
            ];
        }, $terms );
    }

    private function getSectionCategories() {
        $terms = get_terms([
            'taxonomy'   => 'sectionCategory',
            'hide_empty' => false,
        ]);

        return array_map( function( $term ) {
            return [
                'slug'  => $term->slug,
                'label' => $term->name,
            ];
        }, $terms );
    }
}