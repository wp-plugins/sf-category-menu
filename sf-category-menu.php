<?php 

/**
 * Plugin Name: SF Category Menu Widget
 * Plugin URI: http://studiofreya.com/sf-category-menu/
 * Description: Easy treeview menu for WordPress categories.
 * Version: 1.2
 * Author: Studiofreya AS
 * Author URI: http://studiofreya.com
 * License: GPL3
 */

function sf_getPostCount($catid)
{
$args = array(
	'posts_per_page'   => -1,
	'offset'           => 0,
	'category'         => $catid,
	'orderby'          => 'post_date',
	'order'            => 'DESC',
	'include'          => '',
	'exclude'          => '',
	'meta_key'         => '',
	'meta_value'       => '',
	'post_type'        => 'post',
	'post_mime_type'   => '',
	'post_parent'      => '',
	'post_status'      => 'publish',
	'suppress_filters' => true );

	$myposts = get_posts( $args );

	$num = count($myposts);

	return $num;
}

function getSubCategoryPostCount( $catid )
{
	$num = 0;

    $args = array(
        'type'                     => 'post',
        'parent'                   => "$catid",
        'orderby'                  => 'name',
        'order'                    => 'ASC',
        'hide_empty'               => 1,
        'hierarchical'             => 0,
        'exclude'                  => '',
        'include'                  => '',
        'number'                   => '',
        'taxonomy'                 => 'category',
        'pad_counts'               => true
    );

    $categories = get_categories( $args );

	foreach($categories as $cat)
	{
		$num += $cat->count;

		$num += getSubCategoryPostCount($cat->cat_ID);
	}

	return $num;
}

function sf_doCategories( $categories, $select_style, $parent = 0 )
{
	$num = count( $categories );
	
	if ( $num == 0 )
	{
		return;
	}
	
	if ( $parent == 0 )
	{
		echo "<ul id='catnavigation' class=$select_style>";
	}
	else
	{
		echo "<ul>";
	}
	
	foreach($categories as $category) 
	{		
		$ID = $category->cat_ID;
		$subcatcount = sf_getPostCount($ID);

		$category_link = esc_url( get_category_link( $category->term_id ) );
		$link_title = sprintf( __( 'View all posts in %s (%s)', 'sf-category' ), $category->name, $subcatcount );
		$catname = $category->name;
		
		echo "
		<li>
			<a href='$category_link' title='$link_title'>
				<div class='category_name'>
				$catname <span class='category_name_count'>($subcatcount)</span>
				</div>
			</a>
		";
		
		$childargs = array(
			'parent'            => $ID,
			'hide_empty'        => 1,
			'hierarchical'      => 0,
			'pad_counts'        => true
		);
		
		$childcats = get_categories( $childargs );
		
		sf_doCategories( $childcats, $select_style, $ID );
		
		echo "
		</li>
		";	
	}	
	
	echo "</ul>";
}

class SFCategoryMenuWidget extends WP_Widget {

	function SFCategoryMenuWidget() {
		// Instantiate the parent object
		parent::__construct( false, 'SF Category Menu Widget' );
	}

	function widget( $args, $instance ) 
	{
		$exclude_categories = $instance['exclude_cat'];
		$exclude_categories_arr = explode(",", $exclude_categories);
		
		$args = array(
			'type'                     => 'post',
			'parent'                   => '0',
			'orderby'                  => 'name',
			'order'                    => 'ASC',
			'hide_empty'               => 1,
			'hierarchical'             => 0,
			'exclude'                  => $exclude_categories,
			'include'                  => '',
			'number'                   => '',
			'taxonomy'                 => 'category',
			'pad_counts'               => true
		); 
			
		$categories = get_categories( $args);
		$select_style = $instance['select_style'];
		
		echo '<div class="dynamic_sidemenu">';
		
		sf_doCategories( $categories, $select_style );
		
		echo '</div>';
		
		?>
		<script>
		jQuery(document).ready(function($) {
			$('#catnavigation').treeview({
				 collapsed: true,
				 unique: false,
				 persist: "location",
			});
		});
		</script>
		<?php

	}

	function update( $new_instance, $old_instance ) {
		// Save widget options
		$instance = $old_instance;
		$instance['exclude_cat'] = strip_tags($new_instance['exclude_cat']);
		$instance['select_style'] = strip_tags($new_instance['select_style']);

		return $instance;
	}

	function form( $instance ) {
		// Output admin widget options form
		if( $instance) {
			 $exclude_cat = esc_attr($instance['exclude_cat']);
			 $select = esc_attr($instance['select_style']);
		} else {
			 $exclude_cat = '';
			 $select = '';
		}
		?>
	
		<p>
		<label for="<?php echo $this->get_field_id('select_style'); ?>"><?php _e('Style:', 'sf-category'); ?></label>
		<select name="<?php echo $this->get_field_name('select_style'); ?>" id="<?php echo $this->get_field_id('select_style'); ?>">
		<?php
		$options = array('treeview', 'treeview-red', 'treeview-black', 'treeview-grey', 'treeview-famfamfam');
		foreach ($options as $option) {
			echo '<option value="' . $option . '" id="' . $option . '"', $select == $option ? ' selected="selected"' : '', '>', $option, '</option>';
		}
		?>
		</select>
		</p>
		
		<p>
		<label for="<?php echo $this->get_field_id('exclude_cat'); ?>"><?php _e('Exclude ID:', 'sf-category'); ?></label>
		<input id="<?php echo $this->get_field_id('exclude_cat'); ?>" name="<?php echo $this->get_field_name('exclude_cat'); ?>" type="text" value="<?php echo $exclude_cat; ?>" />
		</p>
		<?php		
	}
}

function sf_category_menu_widget_register_widgets() {
	register_widget( 'SFCategoryMenuWidget' );
}

add_action( 'widgets_init', 'sf_category_menu_widget_register_widgets' );

function sf_category_load() {
    wp_enqueue_script( 'jquery' );
	
	wp_enqueue_script('treeview-cookie', plugins_url() . '/sf-category-menu/tree-view/lib/jquery.cookie.js', 'jquery');
	wp_enqueue_script('treeview', plugins_url() . '/sf-category-menu/tree-view/jquery.treeview.js', 'treeview-cookie');
	wp_enqueue_style( 'treeview-style', plugins_url() . '/sf-category-menu/tree-view/jquery.treeview.css');
}

add_action( 'wp_enqueue_scripts', 'sf_category_load' );

function sf_category_init() {
	load_textdomain( 'sf-category', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' ); 
}
add_action( 'plugins_loaded', 'sf_category_init' );


?>
