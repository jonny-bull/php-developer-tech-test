<?php

namespace App\Service\Validators;

use Monolog\Logger;

abstract class Validator
{
    private $required_fields = [];
    protected $logger;
    protected $form_data;

    public function __construct(array $form_data, Logger $logger)
    {
        $this->form_data = $form_data;
        $this->logger = $logger;
    }

    /**
     * Used to check if a form's post data is valid.
     * Should be overwritten by custom validators per form.
     * 
     * @return bool Whether the form checks pass or not.
     */
    public function validate_form(): bool 
    {
        return false;
    }

    /**
     * Checks the array of required fields exist within our form data.
     * 
     * @param array $form_data The form data to look through.
     * @param array $required_fields The required fields to check.
     */
    protected function validate_required_fields( array $form_data, array $required_fields = [] ): bool 
    {
        // Return early if there are no fields keys to check.
        if ( $required_fields === [] ) {
            return true;
        }

        foreach( $this->required_fields as $required_field ) {
            // Exit immediately if we have one missing field.
            if ( in_array( $required_field, $form_data ) === false ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Catch all validation check to make sure a given form field is in a given array.
     * 
     * @param string $form_field The field to find in the form data.
     * @param array $form_data The form data to look through.
     * @param array $values_to_match Possible valid array values.
     * @return bool Whether or not the field exists and has the required output.
     */
    protected function validate_value_in_array( string $form_field, array $form_data, array $values_to_match ): bool {
        if ( 
            array_key_exists( $form_field, $form_data ) === true &&
            (
                $values_to_match === [] ||
                in_array( $form_data[ $form_field ], $values_to_match ) === true
            )
        ) {
            return true;
        }

        return false;

    }
}
