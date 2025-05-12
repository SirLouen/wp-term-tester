<?php
/*
Plugin Name: WP Term Tester
Description: A plugin to test dynamic properties and behaviors of the WordPress core WP_Term class.
Version: 1.0.0
Author: SirLouen <sir.louen@gmail.com> & hellofromTonya (OG Code)
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit;
}

class Tester {
    private $term;
    private $dynamic_properties = array('data', 'link', 'unknown', 'custom_prop1', 'custom_prop2');

    public function __construct($term) {
        $this->term = $term;
    }

    public function test() {
        ob_start();
        if (is_wp_error($this->term) || is_null($this->term)) {
            echo "❌ Error: No valid term found for testing. Please ensure there are terms in your database.\n";
            return ob_get_clean();
        }
        echo "Testing WP_Term object for Term ID: {$this->term->term_id} (Name: {$this->term->name})\n";
        echo "----------------------------------------\n";
        $this->test_isset('before any modifications');
        $this->test_get();
        $this->test_set_dynamic_properties();
        $this->test_isset('after setting dynamic properties');
        $this->test_unset_dynamic_properties();
        $this->test_isset('after unsetting dynamic properties');
        $this->dump_to_array();
        return ob_get_clean();
    }

    private function test_get() {
        $term_data = (array) $this->term->data;
        echo "\nTesting get: Successfully accessed 'data' property as array.\n";
		echo 'Term data: ' . print_r($term_data, true);
    }    

    private function test_isset($message) {
        echo "\nTesting isset() - {$message}:\n";
        echo "----------------------------------------\n";
        foreach ($this->dynamic_properties as $prop) {
            printf("\tWP_Term::\$%s: %s\n", $prop, isset($this->term->$prop) ? 'True' : 'False');
        }
    }

    private function test_set_dynamic_properties() {
        echo "\nTesting setting dynamic properties:\n";
        echo "----------------------------------------\n";
        $this->term->unknown = 'unknown dynamic property';
        printf("\tSet WP_Term::\$unknown = 'unknown dynamic property'\n");
        
        $this->term->link = 'https://example.com/';
        printf("\tSet WP_Term::\$link = 'https://example.com/'\n");
        
        $this->term->array_prop = array('key' => 'value');
        printf("\tSet WP_Term::\$array_prop = array('key' => 'value')\n");
        
        $this->term->number_prop = 12345;
        printf("\tSet WP_Term::\$number_prop = 12345\n");
    }    

    private function test_unset_dynamic_properties() {
        echo "\nTesting unsetting dynamic properties:\n";
        echo "----------------------------------------\n";

        unset($this->term->link);
        printf("\tUnset WP_Term::\$link: %s\n", isset($this->term->link) ? '❌ Failed (still set)' : '✅ Success (unset)');
        
        unset($this->term->array_prop);
        printf("\tUnset WP_Term::\$array_prop: %s\n", isset($this->term->array_prop) ? '❌ Failed (still set)' : '✅ Success (unset)');
        
        unset($this->term->number_prop);
        printf("\tUnset WP_Term::\$number_prop: %s\n", isset($this->term->number_prop) ? '❌ Failed (still set)' : '✅ Success (unset)');
        
        unset($this->term->non_existent_prop);
        printf("\tUnset WP_Term::\$non_existent_prop (never set): %s\n", isset($this->term->non_existent_prop) ? '❌ Failed (still set)' : '✅ Success (never existed)');
    }    

    private function dump_to_array() {
		echo 'Term: ' . print_r($this->term, true);
        $via_to_array = $this->term->to_array();
        $via_to_array_props = array_keys($via_to_array);
		echo 'Via to array props: ' . print_r($via_to_array_props, true);
        $via_typecast = (array) $this->term;
        $via_typecast_props = array_keys($via_typecast);
		echo 'Via typecast props: ' . print_r($via_typecast_props, true);
        
        $diff1 = array_diff($via_to_array_props, $via_typecast_props);
        $diff2 = array_diff($via_typecast_props, $via_to_array_props);
        
        echo "\n\nComparing results between WP_Term::to_array() and (array) \$term:\n";
        echo "----------------------------------------\n";
        if (empty($diff1) && empty($diff2)) {
            echo "✅ Results are identical.\n";
            printf("Additional properties: %s\n", implode(', ', $this->get_additional_props_from_object_as_array($via_to_array_props)));
        } else {
            echo "❌ Results differ.\n";
            if (!empty($diff1)) {          
                echo "\nProperties unique to WP_Term::to_array():\n";
                var_dump($diff1);
            }
            if (!empty($diff2)) {
                echo "\nProperties unique to (array) \$term:\n";
                var_dump($diff2);
            }
            printf(
                "Additional properties via WP_Term::to_array(): %s\n",
                implode(', ', $this->get_additional_props_from_object_as_array($via_to_array_props))
            );            
            printf(
                "Additional properties via (array) \$term: %s\n",
                implode(', ', $this->get_additional_props_from_object_as_array($via_typecast_props))
            );            
        }
    }

    private function get_additional_props_from_object_as_array($object_as_array) {
        $base_declared_props = array('term_id', 'name', 'slug', 'term_group', 'term_taxonomy_id', 'taxonomy', 'description', 'parent', 'count', 'filter');
        $additional_props = array();
        foreach ($object_as_array as $prop) {
            if (in_array($prop, $base_declared_props, true)) {
                continue;
            }
            $additional_props[] = $prop;
        }
        return $additional_props;
    }
}

add_action('admin_menu', 'wp_term_tester_menu');
function wp_term_tester_menu() {
    add_menu_page(
        'WP Term Tester',
        'WP Term Tester',
        'manage_options',
        'wp-term-tester',
        'wp_term_tester_page',
        'dashicons-admin-tools',
        99
    );
}

function wp_term_tester_page() {
    echo '<div class="wrap">';
    echo '<h1>WP Term Tester Results</h1>';
    echo '<p>This tool tests the behavior of dynamic properties in the WP_Term class.</p>';
    echo '<pre>';
	
    $terms = get_terms(array(
        'taxonomy' => 'category',
        'number' => 1,
        'hide_empty' => false,
    ));

    if (!empty($terms) && !is_wp_error($terms)) {
        echo '✅ Fetched Category Name: ' . esc_html($terms[0]->name) . "\n";
        $term = get_term($terms[0]->term_id, 'category');
    } else {
        echo "❌ Error: No terms found or an error occurred while fetching terms.\n";
        $term = null;
    }
    
    $tester = new Tester($term);
    echo $tester->test();
    echo '</pre>';
    echo '</div>';
}
