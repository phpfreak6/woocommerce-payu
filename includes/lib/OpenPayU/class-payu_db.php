<?php
/**
 * Functions for interfacing with the database
 *
 * @class       Payu_DB
 * @author     New
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Payu_DB {

    /**
     * Add/Update the customer database object
     *
     * @access      public
     * @param       int $user_id
     * @param       array $customer_data
     * @return      mixed
     */
    public static function update_customer( $user_id, $customer_data,$db_location ) {
       
	   if ( ! isset( $customer_data ) ) {
            return;
        }
		
        // Set variables related to the form fields we're updating
        $customer_id = isset( $customer_data['customer_id'] ) ? $customer_data['customer_id'] : null;

        if ( isset( $customer_data['card'] ) ) {
            $card_id        = isset( $customer_data['card']['card_id'] ) ? $customer_data['card']['card_id'] : null;
            $card_no        = isset( $customer_data['card']['card_no'] ) ? $customer_data['card']['card_no'] : null;
            $card_exp_month = isset( $customer_data['card']['exp_month'] ) ? $customer_data['card']['exp_month'] : null;
            $card_exp_year  = isset( $customer_data['card']['exp_year'] ) ? $customer_data['card']['exp_year'] : null;
            $card_CVV  		= isset( $customer_data['card']['card_CVV'] ) ? $customer_data['card']['card_CVV'] : null;
            $token_hash  = isset( $customer_data['card']['token_hash'] ) ? $customer_data['card']['token_hash'] : null;
        }

        $default_card = isset( $customer_data['default_card'] ) ? $customer_data['default_card'] : null;
		
        // Grab the current object out of the database and return a useable array
        $currentObject = maybe_unserialize( get_user_meta( $user_id, $db_location , true ) );
		
        // If there is an exising object, append values
        if ( $currentObject ) {
            $newObject = $currentObject;

            // Add a new card to the object
            if ( isset( $customer_data['card'] ) ) {
                $newObject['cards'] = array(
                    'card_id'     => $card_id,
                    'card_no'     => $card_no ,
                    'token_hash'  => $token_hash,
                    'exp_month'	  => $card_exp_month,
                    'exp_year'    => $card_exp_year,
                    'card_CVV'    => $card_CVV
                );
            }

            // Reference a new default card
            if ( isset( $customer_data['default_card'] ) ) {
                $newObject['default_card'] = $default_card;
            }
        }

        // Otherwise, create a new object
        else {
            $newObject = array();

            $newObject['customer_id']   = $customer_id;
            $newObject['cards']         = array();

            // Add a new card to the object
            if ( isset( $customer_data['card'] ) ) {
                $newObject['cards'] = array(
                    'card_id'     => $card_id,
                    'card_no'     => $card_no ,
                    'token_hash'  => $token_hash,
                    'exp_month'	  => $card_exp_month,
                    'exp_year'    => $card_exp_year,
                    'card_CVV'    => $card_CVV
                );
            }
            $newObject['default_card'] = $default_card;
        }

        // Add to the database
        return update_user_meta( $user_id, $db_location , $newObject );
    }

    /**
     * Delete from the customer database object
     *
     * @access      public
     * @param       int $user_id
     * @param       array $customer_data
     * @return      mixed
     */
    public static function delete_customer( $user_id, $customer_data ) {
      
        if ( ! isset( $customer_data ) ) {
            return false;
        }

        // Grab the current object out of the database and return a useable array
        $currentObject = maybe_unserialize( get_user_meta( $user_id, $db_location , true ) );

        // If the object exists already, do work
        if ( $currentObject ) {
			
            $newObject = $currentObject;

            // If a card id is passed, delete the card from the database object
            if ( isset( $customer_data['card'] ) ) {
                unset( $newObject['cards'][ recursive_array_search( $customer_data['card'], $newObject['cards'] ) ] );
            }

            // Add to the database
            return update_user_meta( $user_id, $db_location , $newObject );
			
        } else {
			
            return false;
			
        }
    }
}
