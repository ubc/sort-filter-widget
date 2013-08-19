<?php
/*
Plugin Name: Sort-Filter Widget
Plugin URI: https://github.com/ubc/sort-filter-widget
Description: Adds a widget that allows you to filter and sort search results.
Author: Devindra Payment, CTLT
Version: 1.0
Author URI: http://ctlt.ubc.ca
*/

/**
 * Search_Sort_Filter class.
 */
class Sort_Filter_Widget extends WP_Widget {
	static $plugins = array();
	static $setting_defaults = array(
		'autorefresh'       => true,
		'enable_sort'       => true,
		'enable_orderby'    => true,
		'enable_evaluate'   => true,
		'enable_alpha'      => true,
		'enable_time'       => true,
		'enable_modified'   => false,
		'enable_relevanssi' => true,
		'enable_order'      => true,
		'enable_filter'     => true,
		'enable_authors'    => true,
		'enable_categories' => true,
		'metrics'           => array(),
		'authors_mode'      => 'exclude',
		'authors'           => array(),
		'categories_mode'   => 'exclude',
		'categories'        => array(),
		'allow_multiple_authors'    => true,
		'allow_multiple_categories' => true,
	);
	
	static $search_defaults = array(
		'sersf_orderby'    => 'date',
		'sersf_order'      => 'DESC',
		'sersf_authors'    => array(),
		'sersf_categories' => array(),
	);
	
	static $search = array();
	
	/**
	 * init function.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'sort_filter_form', array( __CLASS__, 'shortcode' ) );
		
		add_action( 'widgets_init', array( __CLASS__, 'register' ) );
		add_action( 'init', array( __CLASS__, 'load' ) );
	}
	
	public static function register() {
		register_widget( __CLASS__ );
		self::register_scripts_and_styles();
	}
	
	public static function load() {
		self::$plugins['relevanssi'] = defined( "RELEVANSSI_PREMIUM" );
		self::$plugins['evaluate'] = defined( "EVAL_BASENAME" );
		
		if ( self::$plugins['relevanssi'] ) {
			self::$search_defaults['sersf_orderby'] = "relevanssi";
			add_action( 'relevanssi_hits_filter', array( __CLASS__, 'modify_results_relevanssi' ) );
		} else {
			add_action( 'pre_get_posts', array( __CLASS__, 'modify_results' ) );
		}
		
		self::$search = wp_parse_args( $_GET, self::$search_defaults );
	}
	
	/**
	 * register_script function.
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function register_scripts_and_styles() {
		wp_register_script( 'search-sort-filter' , plugins_url( 'sort-filter-widget.js', __FILE__ ), array( 'jquery' ), '1.0', true );
		wp_register_style( 'sersf-admin' , plugins_url( 'css/admin.css', __FILE__ ) );
		wp_register_style( 'sersf-view'  , plugins_url( 'css/view.css', __FILE__ ) );
	}
	
	public function __construct() {
		parent::__construct( 'sersf', 'Search / Sort / Filter', array(
			'description' => __( 'For sorting and filtering search results.', 'sersf' ),
		) );
	}
	
	public static function shortcode( $atts, $content ) {
		ob_start();
		self::widget( null, self::parse( $atts, false ) );
		return ob_get_clean();
	}

	public function widget( $args, $instance ) {
		global $wpdb;
		wp_enqueue_style( "sersf-view" );
		
		$instance = wp_parse_args( (array) $instance, self::$setting_defaults );
		$search = self::$search;
		
		// TEMPORARY CODE
		// Currently the autorefresh feature hasn't been implemented
		$instance['autorefresh'] = false;
		// END TEMPORARY CODE
		
		?>
		<!--<form class="sersf" method="POST">-->
		<form role="search" class="sersf" method="get" action="<?php echo trailingslashit( home_url() ); ?>">
			<div class="sersf-section">
				<div class="sersf-search">
					<label class="screen-reader-text" for="s">Search for:</label>
					<input type="text" value="<?php echo $_GET['s']; ?>" name="s" id="s">
				</div>
				<?php if ( $instance['enable_sort'] ): ?>
					<div class="sersf-sort">
						Sort By
						<br />
						<?php if ( $instance['enable_orderby'] ): ?>
							<select name="sersf_orderby" class="sersf-orderby">
								<?php if ( $instance['enable_relevanssi'] && self::$plugins['relevanssi'] ): ?>
									<option value="relevanssi" <?php selected( $search['sersf_orderby'] == "relevanssi" ); ?>>
										Relevance
									</option>
								<?php endif; ?>
								<?php if ( $instance['enable_time'] ): ?>
									<option value="date" <?php selected( $search['sersf_orderby'] == "date" ); ?>>
										Date Posted
									</option>
								<?php endif; ?>
								<?php if ( $instance['enable_alpha'] ): ?>
									<option value="name" <?php selected( $search['sersf_orderby'] == "name" ); ?>>
										Alphabetical
									</option>
								<?php endif; ?>
								<?php if ( $instance['enable_modified'] ): ?>
									<option value="modified" <?php selected( $search['sersf_orderby'] == "modified" ); ?>>
										Date Updated
									</option>
								<?php endif; ?>
								<?php
									if ( $instance['enable_evaluate'] && self::$plugins['evaluate'] ) {
										$metric_ids = '"' . implode( '", "', $instance['metrics'] ) . '"';
										$metrics = $wpdb->get_results( 'SELECT id, nicename FROM '.EVAL_DB_METRICS.' WHERE id IN ('.$metric_ids.') AND type != "poll"' );
										
										foreach ( $metrics as $index => $metric ) {
											?>
											<option value="evaluate/<?php echo $metric->id; ?>" <?php selected( $search['sersf_orderby'] == "evaluate/".$metric->id ); ?>>
												Rating (<?php echo $metric->nicename; ?>)
											</option>
											<?php
										}
									}
								?>
							</select>
						<?php endif; ?>
						<?php if ( $instance['enable_order'] ): ?>
							<select name="sersf_order" class="sersf-order">
								<option value="ASC" <?php selected( $search['sersf_order'] == "ASC" ); ?>>
									Ascending
								</option>
								<option value="DESC" <?php selected( $search['sersf_order'] == "DESC" ); ?>>
									Descending
								</option>
							</select>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
			<div class="sersf-section">
				<?php if ( $instance['enable_filter'] ): ?>
					<div class="sersf-filter">
						<?php
							if ( $instance['enable_authors'] ) {
								$args = array();
								
								if ( $instance['authors_mode'] == 'include' ) {
									$args['include'] = implode( ", ", $instance['authors'] );
								} else {
									$args['exclude'] = implode( ", ", $instance['authors'] );
								}
								
								if ( $instance['allow_multiple_authors'] ) {
									?>
									<span class="sersf-desktop">
										Show only these authors:
										<ul>
											<?php
											foreach ( get_users( $args ) as $index => $user ) {
												?>
												<li>
													<label>
														<input type="checkbox" name="sersf_authors[]" value="<?php echo $user->ID; ?>" <?php checked( in_array( $user->ID, $search['sersf_authors'] ) ); ?> />
														 <?php echo $user->display_name; ?>
													</label>
												</li>
												<?php
											}
											?>
										</ul>
									</span>
									<?php
									$multiple = true;
								} else {
									$multiple = false;
								}
								?>
								<span class="<?php echo ( $multiple ? "sersf-mobile" : "" ); ?>">
									Show only this author:
									<select name="sersf_authors[]">
										<option value="">All</option>
										<?php
										foreach ( get_users( $args ) as $index => $user ) {
											?>
											<option value="<?php echo $user->ID; ?>" <?php selected( $user->ID == $search['sersf_authors'][0] ); ?>>
												<?php echo $user->display_name; ?>
											</option>
											<?php
										}
										?>
									</select>
								</span>
								<br />
								<?php
							}
						?>
						<?php
							if ( $instance['enable_categories'] ) {
								$args = array();
								
								if ( $instance['categories_mode'] == 'include' ) {
									$args['include'] = implode( ",", $instance['categories'] );
								} else {
									$args['exclude'] = implode( ",", $instance['categories'] );
								}
								
								if ( $instance['allow_multiple_categories'] ) {
									?>
									<span class="sersf-desktop">
										Show only these categories:
										<ul>
											<?php
											foreach ( get_categories( $args ) as $index => $category ) {
												?>
												<li>
													<label>
														<input type="checkbox" name="sersf_categories[]" value="<?php echo $category->term_id; ?>" <?php checked( in_array( $category->term_id, $search['sersf_categories'] ) ); ?> />
														 <?php echo $category->name; ?>
													</label>
												</li>
												<?php
											}
											?>
										</ul>
									</span>
									<?php
									$multiple = true;
								} else {
									$multiple = false;
								}
								?>
								<span class="<?php echo ( $multiple ? "sersf-mobile" : "" ); ?>">
									Show only this category:
									<select name="sersf_categories[]">
										<option value="">All</option>
										<?php
										foreach ( get_categories( $args ) as $index => $category ) {
											?>
											<option value="<?php echo $category->term_id; ?>" <?php selected( $category->term_id == $search['sersf_categories'][0] ); ?>>
												<?php echo $category->name; ?>
											</option>
											<?php
										}
										?>
									</select>
								</span>
								<br />
								<?php
							}
						?>
					</div>
				<?php endif; ?>
			</div>
			<div class="clearfix"></div>
			<?php if ( ! $instance['autorefresh'] ): ?>
				<input type="submit" class="btn" value="Search" />
			<?php endif; ?>
		</form>
		<?php
	}

 	public function form( $instance ) {
		wp_enqueue_style( "sersf-admin" );
		
		$instance = wp_parse_args( (array) $instance, self::$setting_defaults );
		
		?>
		<!-- Not Implemented
		<div>
			<?php self::checkbox( 'autorefresh', "Auto Refresh", $instance ); ?>
		</div>
		<hr />
		-->
		<div>
			<?php self::checkbox( 'enable_sort', "Enable Sorting", $instance ); ?>
			<div class="sersf-indent">
				<?php self::checkbox( 'enable_orderby', "Enable Order", $instance ); ?>
				<ul class="sersf-indent">
					<li>
						<?php self::checkbox( 'enable_time', "By Date Posted", $instance ); ?>
					</li>
					<li>
						<?php self::checkbox( 'enable_alpha', "By Alphabetical", $instance ); ?>
					</li>
					<li>
						<?php self::checkbox( 'enable_modified', "By Date Updated", $instance ); ?>
					</li>
					<li>
						<?php self::checkbox( 'enable_evaluate', "By Rating", $instance, ! self::$plugins['evaluate'] ); ?>
						<br />
						<label for="<?php echo $this->get_field_id('metrics'); ?>">
							Allowed Metrics: 
							<input id="<?php echo $this->get_field_id('metrics'); ?>" name="<?php echo $this->get_field_name('metrics'); ?>" type="text" value="<?php echo implode( ", ", $instance['metrics'] ); ?>" />
						</label>
						<br />
						<small>
							A comma seperated list of metric ids. Does not accept polls.
						</small>
					</li>
					<li>
						<?php self::checkbox( 'enable_relevanssi', "By Relevance", $instance, ! self::$plugins['relevanssi'] ); ?>
					</li>
				</ul>
				<?php self::checkbox( 'enable_order', "Enable Ascending/Descending", $instance ); ?>
			</div>
		</div>
		<hr />
		<div>
			<?php self::checkbox( 'enable_filter', "Enable Filtering", $instance ); ?>
			<ul class="sersf-indent">
				<li>
					<?php self::checkbox( 'enable_authors', "By Author", $instance ); ?>
					<div class="sersf-indent">
						<select id="<?php echo $this->get_field_id('authors_mode'); ?>" name="<?php echo $this->get_field_name('authors_mode'); ?>">
							<option value="exclude" <?php selected( $instance['authors_mode'] != "include" ); ?>>
								Exclude These Authors
							</option>
							<option value="include" <?php selected( $instance['authors_mode'] == "include" ); ?>>
								Include These Authors
							</option>
						</select>
						<input id="<?php echo $this->get_field_id('authors'); ?>" name="<?php echo $this->get_field_name('authors'); ?>" type="text" value="<?php echo implode( ", ", $instance['authors'] ); ?>" />
						<br />
						<small>
							A comma seperated list of author ids.
						</small>
						<br />
						<?php self::checkbox( 'allow_multiple_authors', "Allow multiple selections", $instance ); ?>
					</div>
				</li>
				<li>
					<?php self::checkbox( 'enable_categories', "By Category", $instance ); ?>
					<div class="sersf-indent">
						<select id="<?php echo $this->get_field_id('categories_mode'); ?>" name="<?php echo $this->get_field_name('categories_mode'); ?>">
							<option value="exclude" <?php selected( $instance['categories_mode'] != "include" ); ?>>
								Exclude These Categories
							</option>
							<option value="include" <?php selected( $instance['categories_mode'] == "include" ); ?>>
								Include These Categories
							</option>
						</select>
						<input id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>" type="text" value="<?php echo implode( ", ", $instance['categories'] ); ?>" />
						<br />
						<small>
							A comma seperated list of category ids.
						</small>
						<br />
						<?php self::checkbox( 'allow_multiple_categories', "Allow multiple selections", $instance ); ?>
					</div>
				</li>
			</ul>
		</div>
		<?php
	}
	
	private function checkbox( $slug, $text, $instance, $disabled = false ) {
		?>
		<label for="<?php echo $this->get_field_id( $slug ); ?>">
			<input id="<?php echo $this->get_field_id( $slug ); ?>" name="<?php echo $this->get_field_name( $slug ); ?>" type="checkbox" <?php checked( $instance[$slug] && ! $disabled ); ?> <?php disabled( $disabled ); ?> />
			 <?php echo $text; ?>
		</label>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array_merge( $old_instance, self::parse( $new_instance ) );
	}
	
	private function parse( $args, $all = true ) {
		if ( $all ) {
			$list = self::$setting_defaults;
		} else {
			$list = $args;
		}
		
		foreach ( $list as $key => $value ) {
			if ( is_array( self::$setting_defaults[$key] ) ) {
				$args[$key] = self::parse_csv( $args[$key] );
			} elseif ( is_bool( self::$setting_defaults[$key] ) ) {
				$args[$key] = self::parse_bool( $args[$key] );
			} elseif ( self::$setting_defaults[$key] == 'include' || self::$setting_defaults[$key] == 'exclude' ) {
				$args[$key] = self::parse_include( $args[$key] );
			}
		}
		
		return $args;
	}
	
	private function parse_bool( $string ) {
		return $string === "true" || $string === true || $string === "on";
	}
	
	private function parse_include( $string ) {
		return $string === 'include' ? 'include' : 'exclude';
	}
	
	private function parse_csv( $string ) {
		$array = array();
		foreach ( explode( ",", $string ) as $index => $value ) {
			$array[] = trim( $value );
		}
		
		return $array;
	}
	
	public static function modify_results() {
		global $wp_query;
		
		$search = self::$search;
		
		if ( ! empty( $search['sersf_categories'] ) ) {
			$wp_query->set( 'cat', implode( ",", $search['sersf_categories'] ) );
		}
		
		if ( ! empty( $search['sersf_authors'] ) ) {
			$wp_query->set( 'author', implode( ",", $search['sersf_authors'] ) );
		}
		
		if ( ! empty( $search['sersf_order'] ) ) {
			$wp_query->set( 'order',  $search['sersf_order'] );
		}
		
		$split = explode( "/", $search['sersf_orderby'] );
		$orderby = $split[0];
		$param = $split[1];
		
		switch ( $orderby ) {
			case 'evaluate':
				$wp_query->set( 'orderby', 'meta_value_num' );
				$wp_query->set( 'meta_key', 'metric-'.$param.'-score' );
				
				break;
			default:
				$wp_query->set( 'orderby', $search['sersf_orderby'] );
				break;
		}
	}
	
	private static $param;
	private static $order;
	
	public static function modify_results_relevanssi( $args ) {
		$hits = $args[0];
		$search = self::$search;
		
		$authors = array_filter( $search['sersf_authors'] );
		$categories = array_filter( $search['sersf_categories'] );
		
		foreach ( $hits as $index => $post ) {
			$accept = true;
			
			if ( ! empty( $authors ) ) {
				$accept &= in_array( $post->post_author, $authors );
			}
			
			if ( ! empty( $categories ) ) {
				$accept &= count( array_intersect( wp_get_post_categories( $post->ID ), $categories ) ) > 0;
			}
			
			if ( ! $accept ) {
				unset( $hits[$index] );
			}
		}
		
		$split = explode( "/", $search['sersf_orderby'] );
		$orderby = $split[0];
		self::$param = $split[1];
		self::$order = ( $search['sersf_order'] == 'DESC' ? 1 : -1 );
		
		switch ( $orderby ) {
			case 'evaluate':
				$func = array( __CLASS__, 'filter_evaluate' );
				break;
			case 'date':
				$func = array( __CLASS__, 'filter_date' );
				break;
			case 'modified':
				$func = array( __CLASS__, 'filter_modified' );
				break;
			case 'name':
				$func = array( __CLASS__, 'filter_name' );
				break;
			default:
				$func = null;
				break;
		}
		
		if ( $func != null ) {
			usort( $hits, $func );
		}
		
		return array( $hits );
	}
	
	private static function sort_evaluate( $a, $b ) {
		$rating_a = get_post_meta( $a->ID, 'metric-'.self::$param.'-score', true );
		$rating_b = get_post_meta( $b->ID, 'metric-'.self::$param.'-score', true );
		
		if ( $rating_a == $rating_b ) {
			$return = 0;
		} else {
			$return = $rating_a < $rating_b ? 1 : -1;
		}
		
		return self::$order * $return;
	}
	
	private static function sort_date( $a, $b ) {
		$return = strnatcmp( $b->post_date, $a->post_date );
		return self::$order * $return;
	}
	
	private static function sort_modified( $a, $b ) {
		$return = strnatcmp( $b->post_modified, $a->post_modified );
		return self::$order * $return;
	}
	
	private static function sort_name( $a, $b ) {
		$return = strnatcmp( $a->post_title, $b->post_title );
		return self::$order * $return;
	}
}

Sort_Filter_Widget::init();