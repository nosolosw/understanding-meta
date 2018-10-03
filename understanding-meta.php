<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin name: Understanding meta plugin
 */
class UnderstandingMeta {

    const TAXONOMY = 'meta';
    const META_REACH = 'reach';
    const META_TYPE = 'type';

    static function register_taxonomy() {
        register_taxonomy(
            self::TAXONOMY,
            array( 'post', 'page' ),
            array( 
                'label' => 'Meta',
                'hierarchical' => true,
            )
        );
    }

    static function insert_terms() {
        UnderstandingMeta::register_taxonomy();

        // component-announcements
        $component_announcements = wp_insert_term( 'component-announcements', self::TAXONOMY );
        if( ! is_wp_error( $component_announcements ) ){
            add_term_meta( $component_announcements[ 'term_id' ], self::META_REACH, 'all', true );
            add_term_meta( $component_announcements[ 'term_id' ], self::META_TYPE, 'project', true );
        }

        // component
        $component = wp_insert_term( 'component', self::TAXONOMY );
        if( ! is_wp_error( $component ) ){
            add_term_meta( $component[ 'term_id' ], self::META_REACH, 'team', true );
            add_term_meta( $component[ 'term_id' ], self::META_TYPE, 'project', true );
        }

        // books
        $books = wp_insert_term( 'books', self::TAXONOMY );
        if( ! is_wp_error( $books ) ){
            add_term_meta( $books[ 'term_id' ], self::META_REACH, 'personal', true );
            add_term_meta( $books[ 'term_id' ], self::META_TYPE, 'watercooler', true );
        }
    }

    static function delete_terms() {
        UnderstandingMeta::register_taxonomy();

        // component-announcements
        $component_announcements = term_exists( 'component-announcements', self::TAXONOMY );
        if ( $component_announcements[ 'term_id' ] > 0  ) {
            wp_delete_term( $component_announcements[ 'term_id' ], self::TAXONOMY );
            delete_term_meta( $component_announcements[ 'term_id' ], self::META_REACH );
            delete_term_meta( $component_announcements[ 'term_id' ], self::META_TYPE );
        }

        // component
        $component = term_exists( 'component', self::TAXONOMY );
        if ( $component[ 'term_id' ] > 0  ) {
            wp_delete_term( $component[ 'term_id' ], self::TAXONOMY );
            delete_term_meta( $component[ 'term_id' ], self::META_REACH );
            delete_term_meta( $component[ 'term_id' ], self::META_TYPE );
        }

        // books
        $books = term_exists( 'books', self::TAXONOMY );
        if ( $books[ 'term_id' ] > 0  ) {
            wp_delete_term( $books[ 'term_id' ], self::TAXONOMY );
            delete_term_meta( $books[ 'term_id' ], self::META_REACH );
            delete_term_meta( $books[ 'term_id' ], self::META_TYPE );
        }
    }

    static function contains_post_data( ) {
        return isset( $_POST[ self::META_REACH ] ) && isset( $_POST[ self::META_TYPE ] );
    }

    static function add_term_meta( $term_id, $tt_id, $taxonomy ) {
        // $_POST type and name may be undefined, for example, when adding the terms programatically
        if ( $taxonomy === self::TAXONOMY && UnderstandingMeta::contains_post_data() ) {
            add_term_meta( $term_id, self::META_REACH, $_POST[ self::META_REACH ], true );
            add_term_meta( $term_id, self::META_TYPE, $_POST[ self::META_TYPE ], true );   
        }
    }

    static function update_term_meta( $term_id, $tt_id, $taxonomy ) {
        // $_POST type and name may be undefined, for example, when adding the terms programatically
        if ( $taxonomy === self::TAXONOMY && UnderstandingMeta::contains_post_data() ) {
            update_term_meta( $term_id, self::META_REACH, $_POST[ self::META_REACH ] );
            update_term_meta( $term_id, self::META_TYPE, $_POST[ self::META_TYPE ] );   
        }
    }

    static function make_dropdown( $name, $items, $selected ) {
        ?>
        <select class='postform' id='<?php echo $name ?>' name='<?php echo $name ?>'>
        <?php
            $options = '';
            foreach( $items as $item ) {
                $is_selected = '';
                if ( $item === $selected ) {
                    $is_selected = 'selected="selected"';
                }
                $options .= '<option value=' . $item . ' ' . $is_selected . '>' . $item . '</option>';
            }
            echo $options;
        ?>
        </select>
        <?php
    }

    static function get_terms_meta( $meta_key ) {
        global $wpdb;
        return array_keys( $wpdb->get_results( "select distinct( meta_value ) as value from wp_termmeta where meta_key = '" . $meta_key . "'", 'OBJECT_K' ) );
    }

    static function edit_form_fields( $tag, $taxonomy ) {
        $all_types = UnderstandingMeta::get_terms_meta( self::META_TYPE );
        $all_reaches = UnderstandingMeta::get_terms_meta( self::META_REACH );
        $selected_type = get_term_meta( $tag->term_id, self::META_TYPE, true );
        $selected_reach = get_term_meta( $tag->term_id, self::META_REACH, true );

        ?>
        <tr class='form-field'>
            <th>Level</th>
            <td>
            <?php
		        UnderstandingMeta::make_dropdown( self::META_REACH, $all_reaches, $selected_reach );
            ?>
            </td>
        </tr>
        <tr class='form-field'>
            <th>Type</th>
            <td>
            <?php
                UnderstandingMeta::make_dropdown( self::META_TYPE, $all_types, $selected_type );
            ?>
            </td>
        </tr>
        <?php
    }

    static function add_form_fields( $taxonomy ) {
        $types = UnderstandingMeta::get_terms_meta( self::META_TYPE );
        $levels = UnderstandingMeta::get_terms_meta( self::META_REACH );

        ?>
        <div class='form-field'>
            <label>Level</label>
            <?php
                UnderstandingMeta::make_dropdown( self::META_REACH, $levels, null );
            ?>
        </div>

        <div class='form-field'>
            <label>Type</label>
            <?php
                UnderstandingMeta::make_dropdown( self::META_TYPE, $types, null );
            ?>
        </div>
        <?php
    }

    static function get_posts( $query ) {
        // TODO: get these values from elsewhere
        // like an user subscription, GET query params, etc
        $reach_value = 'personal';
        $type_value = 'watercooler';

        $terms = get_terms( array(
            'taxonomy' => self::TAXONOMY,
            'fields' => 'names',
            'meta_query' => array(
                array(
                    'key' => self::META_REACH,
                    'value' => $reach_value
                ),
                array(
                    'key' => self::META_TYPE,
                    'value' => $type_value
                )
            )
        ) );
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => self::TAXONOMY,
                'field' => 'name',
                'terms' => $terms
             )
        ) );
    }
}

// Take care of registering the new taxonomy and adding/removing the terms
add_action( 'init', 'UnderstandingMeta::register_taxonomy', 0, 1 );
register_activation_hook( __FILE__, 'UnderstandingMeta::insert_terms' );
register_deactivation_hook( __FILE__, 'UnderstandingMeta::delete_terms' );

// Let user edit meta data for terms
add_action( 'meta_edit_form_fields', 'UnderstandingMeta::edit_form_fields', 10, 2 );
add_action( 'meta_add_form_fields', 'UnderstandingMeta::add_form_fields', 10 );
add_action( 'created_term', 'UnderstandingMeta::add_term_meta', 10, 3 );
add_action( 'edited_term', 'UnderstandingMeta::update_term_meta', 10, 3 );

add_action( 'pre_get_posts', 'UnderstandingMeta::get_posts' );
?>
