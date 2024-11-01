<?php

/**
 * Plugin Name: Unlist My Post
 * Plugin URI: https://wordpress.org/plugins/unlist-my-post/
 * Description: Unlist your posts so you'll need a link to read them.
 * Version: 3.1
 * Author: Daniel James
 * Author URI: https://danieltj.uk/
 * Text Domain: unlist-my-post
 */

/**
 * (c) Copyright 2019, Daniel James
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

if ( ! defined( 'ABSPATH' ) ) {

	die();

}

new Unlist_My_Post;

class Unlist_My_Post {

	/**
	 * Hook into WordPress.
	 * 
	 * @return void
	 */
	public function __construct() {

		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_box' ), 10, 0 );
		add_action( 'save_post', array( __CLASS__, 'save_meta_value' ), 10, 1 );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_unlisted_posts' ), 10, 1 );
		add_action( 'manage_posts_custom_column', array( __CLASS__, 'show_unlisted_column' ), 10, 2 );
		add_action( 'manage_pages_custom_column', array( __CLASS__, 'show_unlisted_column' ), 10, 2 );

		add_filter( 'manage_posts_columns', array( __CLASS__, 'add_unlisted_column' ), 10, 1 );
		add_filter( 'manage_pages_columns', array( __CLASS__, 'add_unlisted_column' ), 10, 1 );
		add_filter( 'the_title', array( __CLASS__, 'filter_post_title' ), 10, 2 );
		add_filter( 'widget_posts_args', array( __CLASS__, 'filter_post_widget' ), 10, 1 );
		add_filter( 'widget_pages_args', array( __CLASS__, 'filter_page_widget' ), 10, 1 );
		add_filter( 'widget_comments_args', array( __CLASS__, 'filter_comment_widget' ), 10, 1 );
		add_filter( 'rest_post_query', array( __CLASS__, 'filter_rest_api_posts' ), 10, 2 );
		add_filter( 'rest_page_query', array( __CLASS__, 'filter_rest_api_posts' ), 10, 2 );

	}

	/**
	 * Gets the list of unlisted posts from the database.
	 * 
	 * This will get a list of post (and page) IDs from the database
	 * to then pass onto other functions. Doing it this way is much more
	 * elegant than doing database queries in each function.
	 * 
	 * @return array $unlisted An array of unlisted posts
	 */
	public static function get_unlisted_posts() {

		global $wpdb;

		// Get all unlisted posts.
		$get_posts = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . 'postmeta WHERE meta_key = %s AND meta_value = %s',
				'post_list_status',
				'on'
			)
		);

		$unlisted_posts = array();

		foreach ( $get_posts as $post ) {

			array_push( $unlisted_posts, $post->post_id );

		}

		return $unlisted_posts;

	}

	/**
	 * Checks if a post is unlisted.
	 * 
	 * @param string $post_id The post id to check.
	 * 
	 * @return boolean
	 */
	public static function is_post_unlisted( $post_id ) {

		$get_post_meta = get_post_meta( $post_id, 'post_list_status', true );

		// Is this unlisted?
		if ( 'on' == $get_post_meta ) {

			return true;

		} else {

			return false;

		}

	}

	/**
	 * Get all the public post types.
	 * 
	 * @return array $post_types An array of post types.
	 */
	public static function get_post_types() {

		// Fetch all custom post types.
		$get_custom_types = get_post_types(
			array(
				'public' => true,
				'show_ui' => true,
				'_builtin' => false,
			),
			'names',
			'and'
		);

		$post_types = array();

		$post_types[] = 'post';
		$post_types[] = 'page';

		foreach ( $get_custom_types as $key => $value ) {

			$post_types[] = $value;

		}

		/**
		 * Filter the array of post types.
		 * 
		 * @since 2.4
		 * 
		 * @param array $post_types The array of post types.
		 * 
		 * @return array $post_types The filtered post types.
		 */
		$post_types = apply_filters( 'unlistable_post_types', $post_types );

		return $post_types;

	}

	/**
	 * Add meta box to post screen.
	 * 
	 * @return void
	 */
	public static function add_meta_box() {

		$post_types = self::get_post_types();

		add_meta_box(
			'unlist_my_post',
			esc_html__('Listings', 'unlist-my-post'),
			array( __CLASS__, 'meta_box_content' ),
			$post_types,
			'side',
			'default',
			array( '__block_editor_compatible_meta_box' => true )
		);

	}

	/**
	 * Print the meta box HTML.
	 * 
	 * @param object $post WP_Post object of the current post.
	 * 
	 * @return mixed
	 */
	public static function meta_box_content( $post ) {

		$unlist_my_post_nonce = wp_create_nonce('unlist_my_post_nonce');

		$post_type = get_post_type_object( $post->post_type );

		?>
			<p class="unlist_my_post-meta-box">
				<label for="unlist_my_post_option" class="selectit">
					<input type="checkbox" name="unlist_my_post_option" id="unlist_my_post_option"<?php if ( true === self::is_post_unlisted( $post->ID ) ) : ?> checked="checked"<?php endif; ?> /> <?php printf( esc_html__('Unlist this %s from all listings.', 'unlist-my-post'), $post_type->labels->singular_name ); ?>
					<input type="hidden" name="unlist_my_post_nonce" id="unlist_my_post_nonce" value="<?php echo esc_attr( $unlist_my_post_nonce ); ?>" />
				</label>
			</p>
			<p>
				<?php printf( esc_html__('Even when unlisted, anyone can still read this %s if they know the permalink.', 'unlist-my-post'), $post_type->labels->singular_name ); ?>
			</p>
		<?php 

	}

	/**
	 * Save the meta box form data.
	 * 
	 * @param string $post_id Current post ID that is being saved.
	 * 
	 * @return void
	 */
	public static function save_meta_value( $post_id ) {

		// Fetch the nonce value.
		$unlist_my_post_nonce = isset( $_POST['unlist_my_post_nonce'] ) ? sanitize_text_field( $_POST['unlist_my_post_nonce'] ) : '';

		// Verify nonce is valid.
		if ( wp_verify_nonce( $unlist_my_post_nonce, 'unlist_my_post_nonce' ) ) {

			$post_list_status = isset ( $_POST['unlist_my_post_option'] ) ? 'on' : 'off';

			update_post_meta( $post_id, 'post_list_status', $post_list_status );

		}

	}

	/**
	 * Print the value in the post table column.
	 * 
	 * @param string $column  The current column.
	 * @param string $post_id The post current post ID.
	 * 
	 * @return string
	 */
	public static function show_unlisted_column( $column, $post_id ) {

		// Check the current column.
		if ( 'unlisted' == $column ) {

			if ( true === self::is_post_unlisted( $post_id ) ) {

				echo '<span class="dashicons dashicons-yes"></span>';
				echo '<span class="screen-reader-text">' . esc_html__('Post is unlisted', 'unlist-my-post') . '</span>';

			} else {

				echo '<span class="dashicons dashicons-no-alt"></span>';
				echo '<span class="screen-reader-text">' . esc_html__('Post is not unlisted', 'unlist-my-post') . '</span>';

			}

		}

	}

	/**
	 * Add the unlisted column to the post table.
	 * 
	 * @param array $columns The array of registered columns.
	 * 
	 * @return array $columns
	 */
	public static function add_unlisted_column( $columns ) {

		return array_merge( $columns, array( 'unlisted' => esc_html__('Unlisted', 'unlist-my-post') ) );

	}

	/**
	 * Update the query to exclude unlisted posts.
	 * 
	 * This function will check if any posts are actually unlisted
	 * and if they are, it'll also check if we're using the main query
	 * because we don't want to filter people's custom queries.
	 * 
	 * @param object $query The query object for fetching posts.
	 * 
	 * @return void
	 */
	public static function filter_unlisted_posts( $query ) {

		$unlisted_posts = self::get_unlisted_posts();

		/**
		 * Make sure that none of the below conditions are met:
		 * 
		 * 1. The array of unlisted posts isn't empty.
		 * 2. We're altering the main query (first loop).
		 * 3. We're not in the admin dashboard.
		 * 4. We're not viewing a single post/page.
		 */
		if ( false !== $unlisted_posts && $query->is_main_query() && ! is_admin() && ! is_single() && ! is_page() ) {

			$query->set( 'post__not_in', $unlisted_posts );

		}

		/**
		 * Allow people to filter the query.
		 * 
		 * This filter will let people change `$query` to their liking in 
		 * case they have custom query loop for the main query, or in case
		 * they want to copy the query for a secondary loop. This filter has
		 * been placed outside the if statement above so it's always accessible.
		 * 
		 * @since 1.0
		 * @since 1.1 Added array of unlisted posts (that is empty when no posts are unlisted).
		 * 
		 * @param object $query Query arguments to fetch post data.
		 * 
		 * @return object $query
		 */
		$query = apply_filters( 'unlisted_post_query', $query, $unlisted_posts );

	}

	/**
	 * Filter the post title.
	 * 
	 * Filter the post title to add a prefix to it like how prefixes are
	 * added to private and protected posts. This function includes the
	 * `unlisted_title_format()` filter to remove this if it's not wanted.
	 * 
	 * @param string $post_title The post title.
	 * @param string $post_id    The post id.
	 * 
	 * @return string $post_title
	 */
	public static function filter_post_title( $post_title, $post_id ) {

		if ( ! is_admin() && false !== self::is_post_unlisted( $post_id ) ) {

			$get_post = get_post( $post_id );
			$real_title = $get_post->post_title;

			$new_title = sprintf( esc_html__('Unlisted: %s', 'unlist-my-post'), $real_title );

			/**
			 * Filter the post title with the prefix.
			 * 
			 * @since 2.0
			 * @since 2.5.2 Added proper docs to this filter.
			 * @since 2.6   Rewrote to include new title and added post id.
			 * 
			 * @param string $new_title  The new title with a prefix.
			 * @param string $real_title The original title of the post.
			 * @param int    $post_id    The current post id.
			 */
			$post_title = apply_filters( 'unlisted_title_format', $new_title, $real_title, $post_id );

		}

		return $post_title;

	}

	/**
	 * Filter the recent posts widget.
	 * 
	 * @see filter_unlisted_posts()
	 * 
	 * @param array $args An array of widget arguments.
	 * 
	 * @return array $args
	 */
	public static function filter_post_widget( $args ) {

		$unlisted_posts = self::get_unlisted_posts();

		if ( false !== $unlisted_posts ) {

			if ( ! isset( $args['post__not_in'] ) ) {

				$args['post__not_in'] = array();

			}

			$args['post__not_in'] = array_merge( $args['post__not_in'], $unlisted_posts );

		}

		/**
		 * Filter the arguments for the 'recent posts' widget.
		 * 
		 * @since 1.1
		 * 
		 * @param array $args The array of widget arguments.
		 * 
		 * @return array $args
		 */
		return apply_filters( 'unlisted_post_widget_args', $args );

	}

	/**
	 * Filter the pages widget.
	 * 
	 * @see wp_list_pages()
	 * 
	 * @param array $args An array of widget arguments.
	 * 
	 * @return array $args
	 */
	public static function filter_page_widget( $args ) {

		$unlisted_posts = self::get_unlisted_posts();

		if ( false !== $unlisted_posts ) {

			$unlisted_posts = implode( ', ', $unlisted_posts );

			$args['exclude'] .= ',' . $unlisted_posts;

		}

		/**
		 * Filter the arguments for the 'pages' widget.
		 * 
		 * @since 1.1
		 * 
		 * @param array $args The array of widget arguments.
		 * 
		 * @return array $args
		 */
		return apply_filters( 'unlisted_page_widget_args', $args );

	}

	/**
	 * Filter the recent comments widget.
	 * 
	 * @see filter_unlisted_posts()
	 * 
	 * @param array $args An array of widget arguments.
	 * 
	 * @return array $args
	 */
	public static function filter_comment_widget( $args ) {

		$unlisted_posts = self::get_unlisted_posts();

		if ( false !== $unlisted_posts ) {

			if ( ! isset( $args['post__not_in'] ) ) {

				$args['post__not_in'] = array();

			}

			$args['post__not_in'] = array_merge( $args['post__not_in'], $unlisted_posts );

		}

		/**
		 * Filter the arguments for the 'recent comments' widget.
		 * 
		 * @since 3.1
		 * 
		 * @param array $args The array of widget arguments.
		 * 
		 * @return array $args
		 */
		return apply_filters( 'unlisted_comment_widget_args', $args );

	}

	/**
	 * Filter out unlisted posts from the REST API.
	 * 
	 * @param array  $args    The post query arguments.
	 * @param object $request A WP_REST_Request object.
	 * 
	 * @return array $args
	 */
	public static function filter_rest_api_posts( $args, $request ) {

		$unlisted_posts = self::get_unlisted_posts();

		if ( false !== $unlisted_posts ) {

			if ( ! isset( $args['post__not_in'] ) ) {

				$args['post__not_in'] = array();

			}

			$args['post__not_in'] = array_merge( $args['post__not_in'], $unlisted_posts );

		}

		/**
		 * Filter the unlisted REST API arguments.
		 * 
		 * @since 2.5
		 * 
		 * @param array $args An array of post query arguments.
		 * 
		 * @return array $args
		 */
		return apply_filters( 'unlisted_rest_api_posts', $args );

	}

}
