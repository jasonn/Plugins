<?php
/**
 * @package News_Link
 * @version 1.0
 */
/*
Plugin Name: In The News
Plugin URI: http://jason.newlin.me/
Description: Plugin to create a news link section - custom post type and widget.
Author: Jason Newlin
Version: 1.0
Author URI: http://jason.newlin.me/
*/

/**
 * Extends the WP Widget function to create a new widget.
 */
class InTheNews extends WP_Widget {
  function InTheNews() {
    // Instantiate the parent object
    parent::WP_Widget(false, 'In The News');
    $widget_ops = array(
      'classname' => 'widget-in-the-news',
      'description' => __('Displays recent posts with optional excerpt.', 'in-the-news')
    );
		$control_ops = array( 'id_base' => 'in-the-news' );
		$this->WP_Widget( 'in-the-news', __('In The News', 'in-the-news'), $widget_ops, $control_ops );
  }

	function widget($args, $instance) {
	  extract( $args );
    $title = apply_filters('widget_title', $instance['title']);
    $post_count = $instance['post_count'];
    $show_date = isset( $instance['show_date'] ) ? $instance['show_date'] : false;
    $date_format = $instance['date_format'];

    // Save the post object.
    global $post;
  	$post_old = $post;

  ?>
    <?php echo $before_widget; ?>
      <?php if ( $title )
        echo $before_title . $title . $after_title; ?>

      <?php

        // Build query args.
        $query_args = array(
          'post_type' => 'news',
          'post_status' => 'publish',
          'showposts' => $post_count
        );

        // Get array of posts.
      	$cat_posts = new WP_Query($query_args);

      	if ( $cat_posts->have_posts() ) : ?>
          <ul>
          <?php while ( $cat_posts->have_posts() ) : $cat_posts->the_post(); ?>
            <?php
          		$prefix = get_the_content('');
          		$prefix = apply_filters('the_content', $prefix);
              $prefix = strip_tags($prefix);
    			  ?>

        		<li>
        			<h4><?php echo $prefix; ?> <a href="<?php news_link(); ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h4>
        			<?php if ( $instance['show_date'] ) : ?>
        			  <span class="post-date"><?php the_time($instance['date_format']); ?></span>
        			<?php endif; ?>
        		</li>

        	<?php endwhile; ?>
          </ul>
          <a href="<?php bloginfo('url'); ?>/news/">View all</a>
      	<?php endif; ?>

      	<?php
        	// Restore the post object.
        	$post = $post_old; 
        ?>

      <?php echo $after_widget; ?>
    <?php
	}

	function update($new_instance, $old_instance) {
	  $instance = $old_instance;
	  $instance['title'] = strip_tags( $new_instance['title'] );
	  $instance['post_count'] = strip_tags( $new_instance['post_count'] );
	  $instance['show_date'] = $new_instance['show_date'];
	  $instance['date_format'] = strip_tags( $new_instance['date_format'] );

	  return $instance;
	}

	function form($instance) {
		$defaults = array( 
		  'title' => __('In The News', 'recent-posts'),
		  'post_count' => __('5', 'recent-posts'),
		  'show_date' => true,
		  'date_format' => __('M j', 'recent-posts')
		);
    $instance = wp_parse_args( (array) $instance, $defaults );
  ?>
    <p>
      <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $instance['title']; ?>" />
    </p>

    <p>
      <label for="<?php echo $this->get_field_id('post_count'); ?>"><?php _e('How many post to show:'); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id('post_count'); ?>" name="<?php echo $this->get_field_name('post_count'); ?>" type="text" value="<?php echo $instance['post_count']; ?>" />
    </p>

		<p>
			<input class="checkbox" type="checkbox" <?php echo $show_date; ?> id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" /> 
			<label for="<?php echo $this->get_field_id( 'show_date' ); ?>"><?php _e('Show Post Date'); ?></label>
		</p>

		<p>
		  <label for="<?php echo $this->get_field_id('date_format'); ?>"><?php _e('Date Format:'); ?></label>
		  <input class="widefat" id="<?php echo $this->get_field_id('date_format'); ?>" name="<?php echo $this->get_field_name('date_format'); ?>" type="text" value="<?php echo $instance['date_format']; ?>" />
		  <small>PHP Date Format</small>
		</p>
    <?php
	}
}

/**
 * Register widget.
 */
add_action('widgets_init', create_function('', 'return register_widget("InTheNews");'));


/**
 * Initiate the custom post type, and template file.
 */
add_action( 'init', 'NewsLinksInit' );
function NewsLinksInit() { global $news_links; $news_links = new NewsLinks(); }

class NewsLinks {

	function NewsLinks() {
		register_post_type( 'news',
			array(
				'label' => __( 'In The News' ),
				'singular_label' => __( 'News Item' ),
				'public' => true,
				'menu_position' => 5,
				'query_var' => true,
				'supports' => array('title', 'editor'),
				'rewrite' => array('slug'=>'news'),
				'has_archive' => true,
				'taxonomies' => array('post_tag')
			)
		);

		add_action("admin_init", array(&$this, "admin_init"));
		add_action('save_post', array(&$this, 'save_post_data'));

		// Add custom post navigation columns
		add_filter("manage_edit-news_columns", array(&$this, "nav_columns"));
		add_action("manage_posts_custom_column", array(&$this, "custom_nav_columns"));

		// If you want to use a custom template name
		add_action("template_redirect", array(&$this, 'template_redirect'));	
	}

	function admin_init(){
		add_meta_box("link-url-meta", "Link URL", array(&$this, "link_url_meta_box"), "news", "normal", "high");
	}

	function link_url_meta_box() {
		global $post;
		$meta = get_post_meta($post->ID, 'news_link_url', true);

		// Verify
		echo'<input type="hidden" name="news_link_url_noncename" id="news_link_url_noncename" value="'.wp_create_nonce( plugin_basename(__FILE__) ).'" />';

		// Fields for data entry
	  	echo '<label for="news_link_url" style="margin-right: 10px;">Link URL:</label>';
	  	echo '<input type="text" name="news_link_url" value="'.$meta.'" size="50" />';
	}

	function save_post_data( $post_id ) {
		global $post;

		// Verify
		if ( !wp_verify_nonce( $_POST["news_link_url_noncename"], plugin_basename(__FILE__) )) {
			return $post_id;
		}
		if ( 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ))
				return $post_id;
		} else {
			if ( !current_user_can( 'edit_post', $post_id ))
				return $post_id;
		}

		$data = $_POST['news_link_url'];

		// New, Update, and Delete
		if(get_post_meta($post_id, 'news_link_url') == "") 
			add_post_meta($post_id, 'news_link_url', $data, true);
		elseif($data != get_post_meta($post_id, 'news_link_url', true))
			update_post_meta($post_id, 'news_link_url', $data); 
		elseif($data == "")
			delete_post_meta($post_id, 'news_link_url', get_post_meta($post_id, 'news_link_url', true));
	}

	function nav_columns($columns) {
		$columns = array(
			"cb" => "<input type=\"checkbox\" />",
			"title" => "Link Title",
			"link_description" => "Prefix",
			"link_url" => "Link URL",
		);

		return $columns;
	}

	function custom_nav_columns($column) {
		global $post;
		switch ($column) {
  			case "link_description":
  				the_excerpt();
  				break;
  			case "link_url":
  				$meta = get_post_custom();
  				echo $meta["news_link_url"][0];
  				break;
		}
	}

	function template_redirect() {
		global $wp;
		
		if ($wp->query_vars["post_type"] == "news") {
		  define( 'MYTEMPLATEPATH', dirname(__FILE__) . '/' );
			include(MYTEMPLATEPATH . "/news-link.php");
			die();
		}
	}
}

/**
 * Flush the WP permalinks to add the new custom post type.
 */
function flush_rewrite() {
  global $wp_rewrite;
  $wp_rewrite->flush_rules();
}
add_action('init', 'flush_rewrite');

/**
 * Adds the custom field as the URL 
 */
function news_link() { 
	global $post; 
	$permalink = get_permalink(get_post($post->ID));
	$newslink_keys = get_post_custom_keys($post->ID); 
	if ($newslink_keys) {
  		foreach ($newslink_keys as $newslink_key) {
    			if ($newslink_key == 'news_link_url') {
      				$newslink_vals = get_post_custom_values($newslink_key);
    			}
  		}
  		if ($newslink_vals) {
			echo $newslink_vals[0];
  		} else {
    			echo $permalink;
  		}
	} else {
  		echo $permalink;
	}
}

?>
