<?php
/**
 * Wrapper for a bunch of functions to use with DustPress.
 */

namespace DustPress;

use WP_Query;

class Query {

	/**
	 * Post functions
	 *
	 * Simplify post queries for getting meta
	 * data and ACF fields with single function call.
	 *
	 */

	private static $post;
	private static $posts;
	private static $query;

	/**
	* get_post
	*
	* This function will query single post and its meta.
	* The wanted meta keys should be in an array as strings.
	* A string 'all' returns all the meta keys and values in an associative array.
	* If 'single' is set to true then the functions returns only the first value of the specified meta_key.
	*
	* @type	function
	* @date	20/3/2015
	* @since	0.0.1
	*
	* @param	$id (int)
	* @param	$args (array)
	*
	* @return  post object as an associative array with meta data
	*/
	public static function get_post( $id, $args = array() ) {
		global $post;

		$defaults = [
			'meta_keys' => null,
			'single' 	=> false,
			'meta_type' => 'post',
			'output' 	=> 'OBJECT'
		];

		$options = array_merge( $defaults, $args );

		extract( $options );

		$post = get_post( $id, 'ARRAY_A' );

		if ( is_array( $post ) ) {
			get_post_meta( $post, $id, $meta_keys, $meta_type, $single );
		}

		$post['permalink'] = get_permalink($id);

		return self::cast_post_to_type( $post, $output );
	}

	/**
	* get_acf_post
	*
	* This function will query a single post and its meta.
	*
	* If the args has a key 'recursive' with the value 'true', relational
	* post objects are loaded recursively to get the full object.
	* Meta data is handled the same way as in get_post.
	*
	* @type	function
	* @date	20/3/2015
	* @since	0.0.1
	*
	* @param	$id (int)
	* @param	$args (array)
	*
	* $return  post object as an associative array with acf fields and meta data
	*/
	public static function get_acf_post( $id, $args = array() ) {

		$defaults = [
			'meta_keys' 				=> null,
			'single' 					=> false,
			'meta_type' 				=> 'post',
			'whole_fields' 				=> false,
			'max_recursion_level' 		=> 0,
			'current_recursion_level'	=> 0,
			'output' 					=> 'OBJECT'
		];

		$options = array_merge( $defaults, $args );

		extract( $options );

		$acfpost = get_post( $id, 'ARRAY_A' );

		if ( is_array( $acfpost ) ) {
			$acfpost['fields'] = get_fields( $id );

			// Get fields with relational post data as a whole acf object
			if ( $current_recursion_level < $max_recursion_level ) {

				// Let's avoid infinite loops by default by stopping recursion after one level. You may dig deeper in your view model.
				$options['current_recursion_level'] = apply_filters( 'dustpress/query/current_recursion_level', ++$current_recursion_level );

				if ( is_array( $acfpost['fields'] ) && count( $acfpost['fields'] ) > 0 ) {
					foreach ( $acfpost['fields'] as &$field ) {
						$field = self::handle_field( $field, $options );
					}
				}
			}
			elseif ( true == $whole_fields ) {
				if ( is_array( $acfpost['fields'] ) && count( $acfpost['fields'] ) > 0 ) {
					foreach( $acfpost['fields'] as $name => &$field ) {
						$field = get_field_object($name, $id, true);
					}
				}
			}
			self::get_post_meta( $acfpost, $id, $meta_keys, $meta_type, $single );
		}

		$acfpost['permalink'] = get_permalink( $id );

		if ( function_exists("acf_get_attachment") ) {
			$attachment_id = get_post_thumbnail_id( $id );

			if ( $attachment_id ) {
				$acfpost['image'] = acf_get_attachment( $attachment_id );
			}
		}

		return self::cast_post_to_type( $acfpost, $output );
	}

	/**
	* get_acf_post
	*
	* This is a recursive function that handles nested repeaters etc. in co-operation
	* with the get_acf_post function.
	*
	* @type	function
	* @date	16/8/2016
	* @since	1.1.5
	*
	* @param	$field   (mixed)
	* @param	$options (array)
	*
	* $return   same type it is given, possibly extended
	*/
	private static function handle_field( $field, $options ) {
		// No recursion for these post types
		$ignored_types = [
			'attachment',
			'nav_menu_item',
			'acf-field-group',
			'acf-field',
		];

		$ignored_types = apply_filters( 'dustpress/query/ignore_on_recursion', $ignored_types );

		// A direct relation field
		if ( is_object( $field ) && isset( $field->post_type ) && ! in_array( $field->post_type, $ignored_types ) ) {
			$field = self::get_acf_post( $field->ID, $options );
		}

		// A repeater field has relational posts
		if ( is_array( $field ) && count( $field ) > 0 ) {

			// Follows the nested structure of a repeater
			foreach ( $field as $idx => &$row ) {
				// Post in a repeater
				if ( is_object( $row ) && isset( $row->post_type ) && isset( $row->post_type ) && ! in_array( $row->post_type, $ignored_types ) ) {
					$row = self::get_acf_post( $row->ID, $options );
				}
				else if ( is_object( $row ) ) {
					$row = $self::handle_field( $row, $options );
				}
				else if ( is_array ( $row ) ) {
					$row = self::handle_field( $row, $options );
				}
			}
		}

		return $field;
	}

	/**
	* get_posts
	*
	* This function will query all posts and its meta based on given arguments.
	* If you want the whole query object, set 'query_object' to 'true'.
	* If you are using pagination, set 'no_found_rows' to 'false'. This also makes the function to return the whole query object.
	* The wanted meta keys should be in an array as strings.
	* A string 'all' returns all the meta keys and values in an associative array.
	*
	* @type	function
	* @date	20/3/2015
	* @since	0.0.1
	*
	* @param	[array]					$args 	Arguments to override the defaults defined in get_wp_query_defaults.
	* @return	[array/WP_Query object] 		Array of posts as an associative array with meta data or
	*/
	public static function get_posts( $args ) {

        $defaults = self::get_wp_query_defaults();

        $options = array_merge( $defaults, $args );

        extract( $options );

		self::$query = new WP_Query( $options );

		// Get the permalink of the post
		if ( is_array( self::$query->posts ) && count( self::$query->posts ) > 0 ) {
			foreach ( self::$query->posts as &$p ) {
				$p->permalink = get_permalink( $p->ID );
			}
		}

		// Get meta for posts
		if ( count( self::$query->posts ) ) {
			self::get_meta_for_posts( self::$query->posts, $meta_keys, $meta_type, $single );

			// Reset data just in case
			wp_reset_postdata();

			// Maybe return the whole query object
			if ( $query_object || false === $no_found_rows ) {
				return self::$query;
			}
			// Return only the posts
			else {
				return self::$query->posts;
			}
		}
		else
			return false;
	}

	/**
	* get_acf_posts
	*
	* This function queries multiple posts and returns also all the Advanced Custom Fields data set saved in the posts meta.
	* Meta data is handled the same way as in the get_posts-function.
	*
	* @type	function
	* @date	20/3/2015
	* @since	0.0.1
	*
	* @param	[array]		$args 	Arguments to override the defaults defined in get_wp_query_defaults.
	* @return	[array]				array of posts as an associative array with acf fields and meta data
	*/
	public static function get_acf_posts( $args ) {

		// Some redundancy, but we need these
		$defaults = self::get_wp_query_defaults();

		$defaults['max_recursion_level'] = 0;
		$defaults['current_recursion_level'] = 0;

        $options = array_merge( $defaults, $args );

        extract( $options );

        // Perform the basic query first
        self::get_posts( $options );

		// Temporarily set 'query_object' to 'true'
		$args['query_object'] = true;

        self::get_posts( $args );

        // Extend the posts with acf data
		if ( is_array( self::$query->posts ) ) {
			// loop through posts and get all acf fields
			foreach ( self::$query->posts as &$p ) {

				$p->fields = get_fields( $p->ID );

				// Get fields with relational post data as a whole acf object
				if ( $current_recursion_level < $max_recursion_level ) {

					// Let's avoid infinite loops by default by stopping recursion after one level. You may dig deeper in your view model.
					$options['current_recursion_level'] = apply_filters( 'dustpress/query/current_recursion_level', ++$current_recursion_level );

					if ( is_array( $p->fields ) && count( $p->fields ) > 0 ) {
						foreach ( $p->fields as &$field ) {
							$field = self::handle_field( $field, $options );
						}
					}
				}
				elseif ( true == $whole_fields ) {
					if ( is_array( $p->fields ) && count( $p->fields ) > 0 ) {
						foreach( $p->fields as $name => &$field ) {
							$field = get_field_object($name, $id, true);
						}
					}
				}

				// Add attachment image to post
                if ( function_exists("acf_get_attachment") ) {
                    $attachment_id = get_post_thumbnail_id( $p->ID );

                    if ( $attachment_id ) {
                        $p->image = acf_get_attachment( $attachment_id );
                    }
                }
			}

			// Maybe return the whole query object
			if ( $query_object || false === $no_found_rows ) {
				return self::$query;
			}
			// Return only the posts
			else {
				return self::$query->posts;
			}
		}
		else {
			return false;
		}
	}


	/**
	 * get_post_meta
	 *
	 * Get meta data for a single post.
	 *
	 * @type	function
	 * @date	20/3/2015
	 * @since	0.0.1
	 *
	 * @param  [array]			&$post    	The queried post.
	 * @param  [int] 			$id        	Id for the post.
	 * @param  [array/string] 	$meta_keys 	Wanted meta keys or string 'ALL' to fetch all.
	 * @param  [string] 		$meta_type	Type of object metadata is for (e.g. comment, post, or user).
	 * @param  [boolean] 		$single 	If true, return only the first value of the specified meta_key.
	 * @return [array]
	 */
    private static function get_post_meta( &$post, $id, $meta_keys = NULL, $meta_type, $single ) {
		$meta = array();

		if ( $meta_keys === 'all' ) {
			$meta = get_metadata( $meta_type, $id );
		}
		elseif ( is_array( $meta_keys ) ) {
			foreach ( $meta_keys as $key ) {
				$meta[$key] = get_metadata( $meta_type, $id, $key, $single );
			}
		}

		$post['meta'] = $meta;
	}

	/**
	 * get_meta_for_posts
	 *
	 * Get meta data for multiple posts.
	 *
	 * @type	function
	 * @date	20/3/2015
	 * @since	0.0.1
	 *
	 * @param  [array]			&$posts    	The queried post.
	 * @param  [array/string] 	$meta_keys 	Wanted meta keys or string 'ALL' to fetch all.
	 * @param  [string] 		$meta_type	Type of object metadata is for (e.g. comment, post, or user).
	 * @param  [boolean] 		$single 	If true, return only the first value of the specified meta_key.
	 * @return [array]
	 */
	private static function get_meta_for_posts( &$posts, $meta_keys = NULL, $meta_type, $single ) {
		if ( $meta_keys === 'all' ) {
			// Loop through posts and get the meta values
			foreach ( $posts as &$post ) {
				$post->meta = get_metadata( $meta_type, $post->ID, '', $single );
			}
		}
		elseif ( is_array( $meta_keys ) ) {
			// Loop through selected meta keys
			foreach ( $meta_keys as $key ) {
				// Loop through posts and get the meta values
				foreach ( $posts as &$post ) {
					// Maybe init meta
					if ( ! isset( $post->meta ) ) $post->meta = [];
					// Get meta by key and options
					$post->meta[$key] = get_metadata( $meta_type, $post->ID, $key, $single );
				}
			}

		}
	}

	/**
	 * cast_post_to_type
	 *
	 * Used to cast posts to a desired type.
	 *
	 * @type	function
	 * @date	26/1/2016
	 * @since	0.3.0
	 * @param  [array] 			$post 	WP post object as an array.
	 * @param  [string] 		$type 	The desired type.
	 * @return [array/object]
	 */
	private static function cast_post_to_type( $post, $type ) {

		if ( 'ARRAY_A' !== $type ) {

			if ( 'OBJECT' === $type ) {
				return (object) $post;
			}
			elseif ( 'ARRAY_N' === $type ) {
				return array_values( $post );
			}

		}

		return $post;
	}

	/**
	 * Wrapper for wp queries' defaults.
	 *
	 * @return array
	 */
	private static function get_wp_query_defaults() {
		return [
            'meta_keys' 				=> null,	// desired keys in an array or 'all' to fetch all
            'single'					=> false,	// return only the first value for a meta key
            'meta_type' 				=> 'post',	// which type of meta to fetch
            'whole_fields' 				=> false,	// return the entire field object for ACF fields
            'query_object'				=> false,	// do not return the whole WP_Query object
            'no_found_rows' 			=> true, 	// no pagination needed
            'update_post_meta_cache' 	=> false, 	// no post meta utilized
            'update_post_term_cache' 	=> false, 	// no taxonomy terms utilized
        ];
    }
}