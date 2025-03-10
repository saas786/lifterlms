<?php
/**
 * LifterLMS Access Plan Model
 *
 * @package LifterLMS/Models/Classes
 *
 * @since 3.0.0
 * @version 7.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * LLMS_Access_Plan Model.
 *
 * @property  $access_expiration  (string)  Expiration type [lifetime|limited-period|limited-date]
 * @property  $access_expires  (string)  Date access expires in m/d/Y format. Only applicable when $access_expiration is "limited-date"
 * @property  $access_length  (int)  Length of access from time of purchase, combine with $access_period. Only applicable when $access_expiration is "limited-period"
 * @property  $access_period  (string)  Time period of access from time of purchase, combine with $access_length. Only applicable when $access_expiration is "limited-period" [year|month|week|day]
 * @property  $availability  (string)  Determine if this access plan is available to anyone or to members only. Use with $availability_restrictions to determine if the member can use the access plan. [open|members]
 * @property  $availability_restrictions (array)  Indexed array of LifterLMS Membership IDs a user must belong to to use the access plan. Only applicable if $availability is "members".
 * @property  $content  (string)  Plan description (post_content)
 * @property  $checkout_redirect_forced (string) On a members' only access plan, whether to force redirect users back to course after checking out the membership.
 * @property  $checkout_redirect_type (string) Type of checkout redirection [self|page|url]
 * @property  $checkout_redirect_page (int) Page to redirect to after checkout
 * @property  $checkout_redirect_url (string) URL to redirect to after checkout
 * @property  $enroll_text  (string)  Text to display on buy buttons
 * @property  $frequency  (int)  Frequency of billing. 0 = a one-time payment [0-6]
 * @property  $id  (int)  Post ID
 * @property  $is_free  (string)  Whether or not the plan requires payment [yes|no]
 * @property  $length  (int)  Number of intervals to run payment for, combine with $period & $frequency. 0 = forever / until cancelled. Only applicable if $frequency is not 0.
 * @property  $menu_order  (int)  Order to display access plans in when listing them. Displayed in ascending order.
 * @property  $on_sale  (string)  Enable or disable plan sale pricing [yes|no]
 * @property  $period  (string)  Interval period, combine with $length. Only applicable if $frequency is not 0.  [year|month|week|day]
 * @property  $price  (float)  Price per charge
 * @property  $product_id  (int)  WP Post ID of the related LifterLMS Product (course or membership)
 * @property  $sale_end  (string)  Date when the sale pricing ends
 * @property  $sale_start (string)  Date when the sale pricing begins
 * @property  $sale_price (float)  Sale price
 * @property  $sku  (string)  Short user-created plan identifier
 * @property  $title  (string)  Plan title
 * @property  $trial_length  (int)  length of the trial period. Only applicable if $trial_offer is "yes"
 * @property  $trial_offer  (string)  Enable or disable a plan trial period. [yes|no]
 * @property  $trial_period  (string)  Period for the trial period. Only applicable if $trial_offer is "yes". [year|month|week|day]
 * @property  $trial_price  (float)  Price for the trial period. Can be 0 for a free trial period
 *
 * @since 3.0.0
 * @since 3.30.0 Added checkout redirect properties and methods
 * @since 3.30.1 Added method to get the initial price due on checkout.
 * @since 3.31.0 The `$check_availability` parameter was added to the `llms_plan_get_checkout_url` filter.
 */
class LLMS_Access_Plan extends LLMS_Post_Model {

	/**
	 * Map of meta properties => type.
	 *
	 * @var array
	 */
	protected $properties = array(
		'access_expiration'         => 'string',
		'access_expires'            => 'string',
		'access_length'             => 'absint',
		'access_period'             => 'string',
		'availability'              => 'string',
		'availability_restrictions' => 'array',
		'content'                   => 'html',
		'checkout_redirect_forced'  => 'yesno',
		'checkout_redirect_type'    => 'string',
		'checkout_redirect_page'    => 'absint',
		'checkout_redirect_url'     => 'string',
		'enroll_text'               => 'string',
		'frequency'                 => 'absint',
		'is_free'                   => 'yesno',
		'length'                    => 'absint',
		'menu_order'                => 'absint',
		'on_sale'                   => 'yesno',
		'period'                    => 'string',
		'price'                     => 'float',
		'product_id'                => 'absint',
		'sale_end'                  => 'string',
		'sale_start'                => 'string',
		'sale_price'                => 'float',
		'sku'                       => 'string',
		'title'                     => 'string',
		'trial_length'              => 'absint',
		'trial_offer'               => 'yesno',
		'trial_period'              => 'string',
		'trial_price'               => 'float',
	);

	/**
	 * Post Type name
	 *
	 * @var string
	 */
	protected $db_post_type = 'llms_access_plan';

	/**
	 * Name of the model.
	 *
	 * @var string
	 */
	protected $model_post_type = 'access_plan';

	/**
	 * Determine if the access plan has expiration settings
	 *
	 * @since   3.0.0
	 * @version 3.0.0
	 * @return  boolean     true if it can expire, false if it's for lifetime access
	 */
	public function can_expire() {
		return ( 'lifetime' !== $this->get( 'access_expiration' ) );
	}

	/**
	 * Calculate redirection url from settings
	 *
	 * @param    string The redirection type: self, page or url.
	 * @return   string
	 * @since    3.30.0
	 * @version  3.30.0
	 */
	private function calculate_redirection_url( $redirect_type ) {

		$available = $this->is_available_to_user( get_current_user_id() );

		if ( ! $available && 'no' === $this->get( 'checkout_redirect_forced' ) ) {
			$redirect_type = 'membership';
		}

		// by default, no special redirection is needed.
		$redirection = '';

		switch ( $redirect_type ) {

			// redirect to itself.
			case 'self':
				/**
				 * Only set up when it is a member's only access plan with forced redirection to course.
				 * This will ensure that on a regular access plan, no special parameter is added to querystring.
				 * At the same time, if it is a members' only access plan,
				 * after membership checkout we'd like to force redirect to course
				 */
				if ( ! $available && llms_parse_bool( $this->get( 'checkout_redirect_forced' ) ) ) {
					$redirection = get_permalink( $this->get( 'product_id' ) );
				}
				break;

			case 'page':
				$redirection = get_permalink( $this->get( 'checkout_redirect_page' ) );
				break;

			case 'url':
				$redirection = $this->get( 'checkout_redirect_url' );
				break;

		}

		return $redirection;

	}

	/**
	 * Get the translated and pluralized name of the plan's access period
	 *
	 * @since 3.4.6
	 * @since 3.23.0 Unknown.
	 * @since 5.3.0 Use llms_get_time_period_l10n().
	 *
	 * @param string $period Untranslated access period, if not supplied uses stored value for the plan.
	 * @param int    $length Access length (for pluralization), if not supplied uses stored value for the plan.
	 * @return string
	 */
	public function get_access_period_name( $period = null, $length = null ) {

		$period = $period ? $period : $this->get( 'access_period' );
		$length = $length ? $length : $this->get( 'access_length' );

		$period = llms_get_time_period_l10n( $period, $length );

		/**
		 * Filter the translated name of an access plan's billing period.
		 *
		 * @since 3.4.6
		 * @version 3.4.6
		 *
		 * @param string $period Translated period name.
		 * @param int $length Access length, used for pluralization.
		 * @param LLMS_Access_Plan $this Access plan instance.
		 */
		return apply_filters( 'llms_plan_get_access_period_name', $period, $length, $this );

	}


	/**
	 * Default arguments for creating a new post
	 *
	 * @since  3.0.0
	 * @version  3.0.0
	 *
	 * @param  string $title   Title to create the post with
	 * @return array
	 */
	protected function get_creation_args( $title = '' ) {

		return array_merge(
			parent::get_creation_args( $title ),
			array(
				'post_status' => 'publish',
			)
		);

	}

	/**
	 * Retrieve the full URL to redirect to after successful checkout.
	 *
	 * @since 3.30.0
	 * @since 7.0.0 Addeded `$encode` and `$querystring_only` parameters.
	 *
	 * @param bool $encode           Whether or not encoding the URL.
	 * @param bool $querystring_only Only return the redirect URL bassed by the querystring.
	 * @return string
	 */
	public function get_redirection_url( $encode = true, $querystring_only = false ) {

		// What type of redirection is set up by user?
		$redirect_type = $this->get( 'checkout_redirect_type' );

		$query_redirection = llms_filter_input( INPUT_GET, 'redirect', FILTER_VALIDATE_URL );

		// Force redirect querystring parameter over all else.
		$redirection = $query_redirection;
		if ( ! $querystring_only ) {
			$redirection = $query_redirection ? $query_redirection : $this->calculate_redirection_url( $redirect_type );
		}

		/**
		 * Filter the checkout redirection parameter.
		 *
		 * @since 3.30.0
		 * @since 7.0.0 Added `$querystring_only` parameter.
		 *
		 * @param string            $redirection      The calculated url to redirect to.
		 * @param string            $redirection_type Available redirection types 'self', 'membership', 'page', 'url' or a custom type.
		 * @param LLMS_Acccess_Plan $access_plan      Current Access Plan object.
		 * @param bool              $querystring_only Whether or not it was requested to only return the redirect URL passed by querystring.
		 */
		$redirection = apply_filters( 'llms_plan_get_checkout_redirection', $redirection, $redirect_type, $this, $querystring_only );

		return $encode ? urlencode( $redirection ) : $redirection;

	}

	/**
	 * Retrieve the full URL to the checkout screen for the plan.
	 *
	 * @since 3.0.0
	 * @since 3.30.0 Added access plan redirection settings.
	 * @since 3.31.0 The `$check_availability` parameter was added to the filter `llms_plan_get_checkout_url`
	 * @since 7.0.0 No need to add the redirect querystring parameter if not already set, except for unavailable members only plans.
	 *
	 * @param bool $check_availability Determine if availability checks should be made (allows retrieving plans on admin panel).
	 * @return string
	 */
	public function get_checkout_url( $check_availability = true ) {

		$ret       = '#llms-plan-locked';
		$available = $this->is_available_to_user( get_current_user_id() );

		// if bypassing availability checks OR plan is available to user.
		if ( ! $check_availability || $available ) {

			$ret_params  = array(
				'plan' => $this->get( 'id' ),
			);
			$redirection = $this->get_redirection_url( true, true );
			if ( $redirection ) {
				$ret_params['redirect'] = $redirection;
			}

			$ret = llms_get_page_url( 'checkout', $ret_params );

			// not available to user -- this is a member's only plan.
		} elseif ( ! $available ) {

			$memberships = $this->get_array( 'availability_restrictions' );

			// if there's only 1 plan associated with the membership return that url.
			if ( 1 === count( $memberships ) ) {
				$ret         = get_permalink( $memberships[0] );
				$redirection = $this->get_redirection_url();

				if ( $redirection ) {
					$ret = add_query_arg(
						array(
							'redirect' => $redirection,
						),
						$ret
					);
				}
			}
		}

		/**
		 * Filter the checkout URL for an access plan.
		 *
		 * @since Unknown
		 * @since 3.31.0 The `$check_availability` parameter was added.
		 *
		 * @param string $ret      The checkout URL.
		 * @param LLMS_Access_Plan $this Access plan object.
		 * @param bool             $check_availability Determine if availability checks should be made.
		 *                                             (allows retrieving plans on admin panel)
		 */
		return apply_filters( 'llms_plan_get_checkout_url', $ret, $this, $check_availability );

	}

	/**
	 * Get the initial price due on checkout.
	 *
	 * Automatically accounts for Trials, sales, and coupon discounts.
	 *
	 * @since 3.30.1
	 * @since 3.40.0 Simplify logic by using new 4th argument ($coupon) of the `get_price()` method.
	 *
	 * @param array                $price_args Arguments passed to the price getter function to generate the price.
	 * @param LLMS_Coupon|int|null $coupon     Coupon ID, object, or `null` if no coupon is being used.
	 * @param string               $format     Format the price to be returned. Options: html, raw, float (default).
	 * @return mixed
	 */
	public function get_initial_price( $price_args = array(), $coupon = null, $format = 'float' ) {

		// If it's free it's a bit simpler.
		if ( $this->is_free() ) {

			$ret = $this->get_free_pricing_text( $format );

		} else {

			$price_key = 'price';

			// Setup the price key name based on the presence of a trial or sale.
			if ( $this->has_trial() ) {
				$price_key = 'trial_price';
			} elseif ( $this->is_on_sale() ) {
				$price_key = 'sale_price';
			}

			$ret = $this->get_price( $price_key, $price_args, $format, $coupon );

		}

		/**
		 * Filter an access plan's initial price due on checkout.
		 *
		 * @since 3.30.1
		 *
		 * @param mixed                $ret        Price due on checkout.
		 * @param array                $price_args Arguments passed to the price getter function to generate the price.
		 * @param LLMS_Coupon|int|null $coupon     Coupon ID, object, or `null` if no coupon is being used.
		 * @param string               $format     Format the price to be returned. Options: html, raw, float (default).
		 * @param LLMS_Access_Plan     $this       Access Plan object.
		 */
		return apply_filters( 'llms_access_plan_get_initial_price', $ret, $price_args, $coupon, $format, $this );

	}

	/**
	 * Get a string to use for 0 dollar amount prices rather than 0
	 *
	 * @param   string $format format to display the price in
	 * @return  string
	 * @since   3.0.0
	 * @version 3.0.0
	 */
	public function get_free_pricing_text( $format = 'html' ) {
		$text = __( 'FREE', 'lifterlms' );

		if ( 'html' === $format ) {
			$text = '<span class="lifterlms-price">' . $text . '</span>';
		} elseif ( 'float' === $format ) {
			$text = 0.00;
		}

		/**
		 * Filter the text displayed when a plan has no price.
		 *
		 * @since   3.0.0
		 * @version 3.0.0
		 *
		 * @param string $text Displayed text.
		 * @param LLMS_Access_Plan $this The access plan instance.
		 */
		return apply_filters( "llms_get_free_{$this->model_post_type}_pricing_text", $text, $this );
	}

	/**
	 * Getter for price strings with optional formatting options
	 *
	 * @since 3.0.0
	 * @since 3.23.0 Unknown.
	 * @since 3.40.0 Added `$coupon` parameter.
	 *
	 * @param string               $key        Property key.
	 * @param array                $price_args Optional array of arguments that can be passed to `llms_price()`.
	 * @param string               $format     Optional format conversion method [html|raw|float].
	 * @param LLMS_Coupon|int|null $coupon     Coupon ID, object, or `null` if no coupon is being used.
	 * @return mixed
	 */
	public function get_price( $key, $price_args = array(), $format = 'html', $coupon = null ) {

		if ( $coupon ) {
			return $this->get_price_with_coupon( $key, $coupon, $price_args, $format );
		}

		$price = $this->get( $key );

		if ( $price <= 0 ) {

			$ret = $this->get_free_pricing_text( $format );

		} else {

			$ret = parent::get_price( $key, $price_args, $format );

		}

		/**
		 * Filter the access plan's price.
		 *
		 * @since 3.40.0
		 *
		 * @param mixed            $ret        Returned price.
		 * @param string           $key        The key of the price property.
		 * @param array            $price_args Price arguments.
		 * @param string           $format     Price format string.
		 * @param LLMS_Access_Plan $this       Instance of the access plan.
		 */
		return apply_filters( 'llms_plan_get_price', $ret, $key, $price_args, $format, $this );
	}

	/**
	 * Apply a coupon to a price
	 *
	 * @since 3.0.0
	 * @since 3.7.0 Unknown.
	 * @since 3.40.0 Use `wp_strip_all_tags()` in favor of `strip_tags()`.
	 *
	 * @param string          $key        Price to retrieve, "price", "sale_price", or "trial_price".
	 * @param LLMS_Coupon|int $coupon_id  Coupon object or post id.
	 * @param array           $price_args Optional arguments to be passed to `llms_price()`.
	 * @param string          $format     Optional return format as passed to `llms_price()`.
	 * @return mixed
	 */
	public function get_price_with_coupon( $key, $coupon_id, $price_args = array(), $format = 'html' ) {

		// Allow id or instance to be passed for $coupon_id.
		if ( $coupon_id instanceof LLMS_Coupon ) {
			$coupon = $coupon_id;
		} else {
			$coupon = new LLMS_Coupon( $coupon_id );
		}

		$price = $this->get( $key );

		// Ensure the coupon *can* be applied to this plan.
		if ( ! $coupon->is_valid( $this ) ) {
			return $price;
		}

		$discount_type = $coupon->get( 'discount_type' );

		// Price and sale price are calculated of coupon amount.
		if ( 'price' === $key || 'sale_price' === $key ) {

			$coupon_amount = $coupon->get( 'coupon_amount' );

		} elseif ( 'trial_price' === $key && $coupon->has_trial_discount() && $this->has_trial() ) {

			$coupon_amount = $coupon->get( 'trial_amount' );

		} else {

			$coupon_amount = 0;

		}

		if ( $coupon_amount ) {

			// Simple subtraction.
			if ( 'dollar' === $discount_type ) {
				$price = $price - $coupon_amount;
			} elseif ( 'percent' === $discount_type ) {
				$price = $price - ( $price * ( $coupon_amount / 100 ) );
			}
		}

		// If price is less than 0 return the pricing text.
		if ( $price <= 0 ) {

			$price = $this->get_free_pricing_text( $format );

		} else {

			if ( 'html' === $format || 'raw' === $format ) {
				$price = llms_price( $price, $price_args );
				if ( 'raw' === $format ) {
					$price = wp_strip_all_tags( $price );
				}
			} elseif ( 'float' === $format ) {
				$price = floatval( number_format( $price, get_lifterlms_decimals(), '.', '' ) );
			} else {
				$price = apply_filters( "llms_get_{$this->model_post_type}_{$key}_{$format}_with_coupon", $price, $key, $price_args, $format, $this );
			}
		}

		return apply_filters( "llms_get_{$this->model_post_type}_{$key}_price_with_coupon", $price, $key, $price_args, $format, $this );

	}

	/**
	 * Retrieve an instance of the associated LLMS_Product
	 *
	 * @return   obj
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_product() {
		return new LLMS_Product( $this->get( 'product_id' ) );
	}

	/**
	 * Retrieve the product type (course or membership) for the associated product
	 *
	 * @return   string
	 * @since    3.0.0
	 * @version  3.0.0
	 */
	public function get_product_type() {
		$product = $this->get_product();
		return str_replace( 'llms_', '', $product->get( 'type' ) );
	}

	/**
	 * Retrieve the text displayed on "Buy" buttons
	 * Uses optional user submitted text and falls back to LifterLMS defaults if none is supplied
	 *
	 * @return   string
	 * @since    3.0.0
	 * @version  3.23.0
	 */
	public function get_enroll_text() {

		// User custom text option.
		$text = $this->get( 'enroll_text' );

		if ( ! $text ) {

			switch ( $this->get_product_type() ) {

				case 'course':
					$text = apply_filters( 'llms_course_enroll_button_text', __( 'Enroll', 'lifterlms' ), $this );
					break;

				case 'membership':
					$text = apply_filters( 'llms_membership_enroll_button_text', __( 'Join', 'lifterlms' ), $this );
					break;

			}
		}

		return apply_filters( 'llms_plan_get_enroll_text', $text, $this );
	}

	/**
	 * Get a sentence explaining plan expiration details
	 *
	 * @return   string
	 * @since    3.0.0
	 * @version  3.28.2
	 */
	public function get_expiration_details() {

		$ret = '';

		$expiration = $this->get( 'access_expiration' );
		if ( 'limited-date' === $expiration ) {
			$ret = sprintf( _x( 'access until %s', 'Access expiration date', 'lifterlms' ), $this->get_date( 'access_expires' ) );
		} elseif ( 'limited-period' === $expiration ) {
			$ret = sprintf( _x( '%1$d %2$s of access', 'Access period description', 'lifterlms' ), $this->get( 'access_length' ), $this->get_access_period_name() );
		}

		return apply_filters( 'llms_get_product_expiration_details', $ret, $this );

	}

	/**
	 * Get a sentence explaining the plan's payment schedule
	 *
	 * @return   string
	 * @since    3.0.0
	 * @version  3.23.0
	 */
	public function get_schedule_details() {

		$ret = '';

		$period    = $this->get( 'period' );
		$frequency = $this->get( 'frequency' );
		$length    = $this->get( 'length' );

		// One-time payments don't display anything here unless filtered.
		if ( $frequency > 0 ) {

			if ( 1 === $frequency ) {
				$ret = sprintf( _x( 'per %s', 'subscription schedule', 'lifterlms' ), $this->get_access_period_name( $period, $frequency ) );
			} else {
				$ret = sprintf( _x( 'every %1$d %2$s', 'subscription schedule', 'lifterlms' ), $frequency, $this->get_access_period_name( $period, $frequency ) );
			}

			// Add length sentence if applicable.
			if ( $length > 0 ) {

				$ret .= ' ' . sprintf( _x( 'for %1$d total payments', 'subscription # of payments', 'lifterlms' ), $length );

			}
		}

		return apply_filters( 'llms_get_product_schedule_details', sprintf( $ret, $this->get( 'period' ), $frequency, $length ), $this );

	}

	/**
	 * Get a sentence explaining the plan's trial offer
	 *
	 * @return   string
	 * @since    3.0.0
	 * @version  3.4.8
	 */
	public function get_trial_details() {

		$details = '';

		if ( $this->has_trial() ) {

			$length  = $this->get( 'trial_length' );
			$period  = $this->get( 'trial_period' );
			$details = sprintf( _x( 'for %1$d %2$s', 'trial offer description', 'lifterlms' ), $length, $this->get_access_period_name( $period, $length ) );

		}

		return apply_filters( 'llms_get_product_trial_details', $details, $this );
	}

	/**
	 * Get the access plans visibility setting
	 *
	 * @return   string
	 * @since    3.8.0
	 * @version  3.8.0
	 */
	public function get_visibility() {
		$term = $this->get_terms( 'llms_access_plan_visibility', true );
		$ret  = ( $term && $term->name ) ? $term->name : 'visible';
		return apply_filters( 'llms_get_access_plan_visibility', $ret, $this );
	}

	/**
	 * Determine if the plan has availability restrictions
	 *
	 * Related product must be a COURSE.
	 * Availability must be set to "members" and at least one membership must be selected.
	 *
	 * @since 3.0.0
	 *
	 * @return boolean
	 */
	public function has_availability_restrictions() {
		return ( 'course' === $this->get_product_type() && 'members' === $this->get( 'availability' ) && $this->get_array( 'availability_restrictions' ) );
	}

	/**
	 * Determine if the free checkout process & interface should be used for this access plan
	 *
	 * @return   boolean
	 * @since    3.4.0
	 * @version  3.4.0
	 */
	public function has_free_checkout() {
		return ( $this->is_free() && apply_filters( 'llms_has_free_checkout', true, $this ) );
	}

	/**
	 * Determine if the plan has a trial offer
	 * One-time payments can't have a trial, so the plan must have a frequency greater than 0
	 *
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.23.0
	 */
	public function has_trial() {
		$ret = false;
		if ( $this->get( 'frequency' ) > 0 ) {
			$ret = llms_parse_bool( $this->get( 'trial_offer' ) );
		}
		return apply_filters( 'llms_plan_has_trial', $ret, $this );
	}

	/**
	 * Determine if the plan is available to a user based on configured availability restrictions
	 *
	 * @param    int $user_id  (optional) WP User ID, if not supplied get_current_user_id() will be used
	 * @return   boolean
	 * @since    3.4.4
	 * @version  3.23.0
	 */
	public function is_available_to_user( $user_id = null ) {

		$user_id = empty( $user_id ) ? get_current_user_id() : $user_id;

		$access = true;

		// If there are membership restrictions, check the user is in at least one membership.
		if ( $this->has_availability_restrictions() ) {
			$access = false;
			foreach ( $this->get_array( 'availability_restrictions' ) as $mid ) {

				// Once we find a membership, exit.
				if ( llms_is_user_enrolled( $user_id, $mid ) ) {
					$access = true;
					break;
				}
			}
		}

		return apply_filters( 'llms_plan_is_available_to_user', $access, $user_id, $this );

	}

	/**
	 * Determine if the plan is marked as "featured"
	 *
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.8.0
	 */
	public function is_featured() {
		return ( 'featured' === $this->get_visibility() );
	}

	/**
	 * Determines if a plan is marked ar free
	 * This only returns the value of the setting and should not
	 * be used to check if payment is required (when using a coupon for example)
	 *
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.23.0
	 */
	public function is_free() {
		return llms_parse_bool( $this->get( 'is_free' ) );
	}

	/**
	 * Determine if a plan is *currently* on sale
	 *
	 * @return   boolean
	 * @since    3.0.0
	 * @version  3.24.3
	 */
	public function is_on_sale() {

		$ret = false;

		if ( llms_parse_bool( $this->get( 'on_sale' ) ) ) {

			$now = llms_current_time( 'timestamp' );

			$start = $this->get( 'sale_start' );
			$end   = $this->get( 'sale_end' );

			// Add times if the values exist (start of day & end of day).
			$start = ( $start ) ? strtotime( $start . ' 00:00:00' ) : $start;
			$end   = ( $end ) ? strtotime( '+1 day', strtotime( $end . ' 00:00:00' ) ) : $end;

			// No dates, the product is indefinitely on sale.
			if ( ! $start && ! $end ) {

				$ret = true;

				// Start and end.
			} elseif ( $start && $end ) {

				$ret = ( $now < $end && $now > $start );

				// Only start.
			} elseif ( $start && ! $end ) {

				$ret = ( $now > $start );

				// Only end.
			} elseif ( ! $start && $end ) {

				$ret = ( $now < $end );

			}
		}

		return apply_filters( 'llms_plan_is_on_sale', $ret, $this );

	}

	/**
	 * Determine if the plan is visible
	 * Both featured and visible access plans are considered visible
	 *
	 * @return   boolean
	 * @since    3.8.0
	 * @version  3.8.0
	 */
	public function is_visible() {
		return ( 'hidden' !== $this->get_visibility() );
	}

	/**
	 * Determine if the Access Plan has recurring payments
	 *
	 * @return  boolean   true if it is recurring, false otherwise
	 * @since   3.0.0
	 * @version 3.0.0
	 */
	public function is_recurring() {
		return ( 0 !== $this->get( 'frequency' ) );
	}

	/**
	 * Determine if the access plan requires payment.
	 *
	 * Automatically accounts for coupons, sales, trials, and whether the plan is marked as free.
	 *
	 * @since 3.0.0
	 * @since 3.30.1 Uses self::get_initial_price().
	 *
	 * @param int $coupon_id LLMS_Coupon ID.
	 * @return bool true if payment required, false otherwise
	 */
	public function requires_payment( $coupon_id = null ) {

		$ret = false;

		if ( ! $this->is_free() ) {

			$ret = ( $this->get_initial_price( array(), $coupon_id, 'float' ) > 0 );

			// Ensure that we still collect payment details if a free trial is used.
			if ( false === $ret ) {
				$price_key = $this->is_on_sale() ? 'sale_price' : 'price';
				$ret       = ( $this->get_price( $price_key, array(), 'float', $coupon_id ) > 0 );
			}
		}

		return apply_filters( 'llms_plan_requires_payment', $ret, $coupon_id, $this );

	}

	/**
	 * Update the visibility term for the access plan
	 *
	 * @param    string $visibility  access plan name
	 * @since    3.8.0
	 * @version  3.8.0
	 */
	public function set_visibility( $visibility ) {
		return $this->set_terms( array( $visibility ), 'llms_access_plan_visibility', false );
	}

	/**
	 * Cleanup data to remove unnecessary defaults
	 *
	 * @param    array $arr   array of data to be serialized
	 * @return   array
	 * @since    3.16.11
	 * @version  3.16.11
	 */
	protected function toArrayAfter( $arr ) {
		unset( $arr['author'] );
		return $arr;
	}

	/**
	 * Don't add custom fields during toArray()
	 *
	 * @param    array $arr  post model array
	 * @return   array
	 * @since    3.16.11
	 * @version  3.16.11
	 */
	protected function toArrayCustom( $arr ) {
		return $arr;
	}

}
