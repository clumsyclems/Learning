<?php

/**
 * Custom Gutenberg function
 */

function crodriguez_gutenberg_default_color()
{
    add_theme_support(
        'editor-color-palette', 
        array(
            array(
                'name' => 'White',
                'slug' => 'white',
                'color' => '#ffffff'
            ),
            array(
                'name' => 'Black',
                'slug' => 'black',
                'color' => '#000000'
            ),
            array(
                'name' => 'Pink',
                'slug' => 'pink',
                'color' => '#ff4444'
            )
        )
    );

    add_theme_support(
        'editor-font-sizes', 
        array(
            array(
                'name' => 'Normal',
                'slug' => 'normal',
                'size' => '16'
            ),
            array(
                'name' => 'Large',
                'slug' => 'large',
                'size' => '24'
            )
        )

    );

 }

add_action('init', 'crodriguez_gutenberg_default_color' ); 

function crodriguez_gutenberg_blocks()
{

    wp_register_script('custom-cta-js', 
    array( 'wp-blocks', "wp-components", 'wp-editor' ),
        get_template_directory_uri() . '/build/index.js', 
    );
    
    register_block_type('crodriguez/custom-cta', 
        array(
            'editor_script' => 'custom-cta-js'
        )
    );
     
}

add_action('init', 'crodriguez_gutenberg_blocks' ); 
