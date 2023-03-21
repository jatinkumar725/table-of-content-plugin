<?php
/**
 * Plugin Name: Table of Contents
 * Plugin URI: https://example.com/
 * Description: Generates a table of contents for WordPress posts.
**/

// Step 1: Register the plugin
function toc_register_plugin() {
    add_action( 'plugins_loaded', 'toc_load_textdomain' );
}
register_activation_hook( __FILE__, 'toc_register_plugin' );

// Step 2: Add the necessary CSS file to the plugin
function toc_enqueue_styles() {
    wp_enqueue_style( 'toc-style', plugin_dir_url( __FILE__ ) . 'assets/css/style.css' );
}
add_action( 'wp_enqueue_scripts', 'toc_enqueue_styles' );

// Step 3: Create a shortcode function that generates the table of contents
function toc_generate_table_of_contents() {
    global $post;

    $content = $post->post_content;

    if ( preg_match_all( '/<h([1-6]).*?>(.*?)<\/h\1>/', $content, $matches, PREG_SET_ORDER ) ) {
        $output = '<div class="toc-wrapper">
                   <div class="toc-header">';
        $output .= '<h4 class="toc-heading">' . __( 'Table of Contents', 'toc' ) . '</h4>
                    </div>';
        $output .= '<div class="toc-list-wrapper">
                    <ul class="toc-list">';

        $current_level = 0;
        $counter = array(0,0,0,0,0,0);
        $sublist_open = false;

        foreach ( $matches as $match ) {
            $level = $match[1];
            $title = $match[2];
            $slug = sanitize_title_with_dashes( $title );
            $counter[$level-1]++;

            if ($level > $current_level) {
                if ($sublist_open) {
                    $output .= '<ul class="toc-sublist">';
                }
            } elseif ($level < $current_level) {
                for ($i = $current_level; $i > $level; $i--) {
                    $output .= '</ul>';
                    $sublist_open = false;
                }
            }
            $current_level = $level;

            if ($level == 1) {
                $counter_output = implode('.', array_slice($counter, 0, $level));
            } elseif ($level == 2) {
                $counter_output = strtoupper(chr(96 + $counter[$level-1])) . '.';
            } elseif ($level == 3) {
                $counter_output = toc_get_roman_numeral($counter[$level-1]) . '.';
            } else {
                $counter_output = implode('.', array_slice($counter, 0, $level));
            }

            $output .= '<li class="toc-list-item mt-2 d-flex align-items-start level-' . $level . '"><span>'. $counter_output .'</span><a href="#' . $slug . '" class="pt-0">' . $title . '</a></li>';

            if ($level > 1) {
                $sublist_open = true;
            }
        }

        while ($current_level > 0) {
            $output .= '</ul>';
            $current_level--;
        }

        $output .= '</div>';
        $output .= '</div>';

        return $output;
    }
}

function toc_get_roman_numeral($integer) {
    $table = array('M'=>1000, 'CM'=>900, 'D'=>500, 'CD'=>400, 'C'=>100, 'XC'=>90, 'L'=>50, 'XL'=>40, 'X'=>10, 'IX'=>9, 'V'=>5, 'IV'=>4, 'I'=>1);
    $result = '';
    foreach ($table as $roman=>$num) {
        $div = floor($integer / $num);
        $result .= str_repeat($roman, $div);
        $integer = $integer % $num;
    }
    return $result;
}

add_shortcode( 'toc', 'toc_generate_table_of_contents' );

// Step 4: Use the_content filter to modify the post content and add the table of contents
function toc_add_to_content( $content ) {
    global $post;

    if ( has_shortcode( $post->post_content, 'toc' ) ) {
        $toc = toc_generate_table_of_contents();
        $content = $toc . $content;
    }

    return $content;
}
add_filter( 'the_content', 'toc_add_to_content' );

// Step 5: Use JavaScript to add smooth scrolling to the table of contents
function toc_enqueue_scripts() {
    wp_enqueue_script( 'toc-script', plugin_dir_url( __FILE__ ) . 'assets/js/script.js', array( 'jquery' ), false, true );
}
add_action( 'wp_enqueue_scripts', 'toc_enqueue_scripts' );