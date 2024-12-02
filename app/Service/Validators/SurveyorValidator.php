<?php

namespace App\Service\Validators;

class SurveyorValidator extends Validator
{
    /**
     * Fields required by the form.
     * 
     * @param array
     */
    private array $required_fields = [
        'first_name',
        'email',
        'phone',
        'address_line_1',
        'town',
        'postcode',
        'type',
        'bedrooms',
        'property_value',
        'survey_type',
    ];

    /**
     * The valid types of survey.
     * 
     * @param array
     */
    private array $valid_survey_types = [
        'building', 
        'homebuyer', 
        'valuation'
    ];

    /**
     * The valid postcode prefixes.
     * 
     * @param array
     */
    private array $valid_postcode_areas = [
        'B',
        'BS',
        'CF'
    ];

    /**
     * The minimum number of bedrooms for a valid submission.
     * 
     * @param int
     */
    private int $valid_min_bedrooms = 1;

    /**
     * The maximum number of bedrooms for a valid submission.
     * 
     * @param int
     */
    private int $valid_max_bedrooms = 5;

    /**
     * Runs each individual check against the form.
     * 
     * @return bool Whether the form data passes all of our validation checks or not.
     */
    public function validate_form(): bool {
        // Only return true if all of our validation checks pass.
        if (
            $this->validate_required_fields( $this->form_data, $this->required_fields ) === true &&
            $this->validate_postcode_prefix( $this->form_data, $this->valid_postcode_areas ) === true &&
            $this->validate_survey_type( $this->form_data, $this->valid_survey_types ) === true &&
            $this->validate_max_bedrooms( $this->form_data, $this->valid_min_bedrooms, $this->valid_max_bedrooms ) === true
        ) {
            return true;
        }
        
        // Return false by default.
        return false;
    }

    /**
     * Check if the postcode prefix is valid.
     * 
     * @param array $form_data The data submitted to the form.
     * @param array $valid_postcode_areas Valid postcode prefixes.
     * @return bool Whether or not the data is valid.
     */
    private function validate_postcode_prefix( array $form_data, array $valid_postcode_areas ): bool 
    {
        if ( array_key_exists( 'postcode', $form_data ) === false ) {
            return false;
        }

        // Get the first three characters only and switch to upper case.
        $postcode = strtoupper( substr( $form_data['postcode'], 0, 3 ) );
        
        // Make sure we don't get any false positives (CFG, BA etc).
        // Match the letters at the start of the string and ensure they are immediately followed by a number.
        $valid_regex = '/^(?:' . implode( '[0-9]|', $valid_postcode_areas ) . '[0-9])/';
        if ( preg_match( $valid_regex, $postcode ) === 1 ) {
            return true;
        }

        return true;
    }

    /**
     * Check if the survey name is valid.
     * 
     * @param array $form_data The data submitted to the form.
     * @param array $valid_survey_types Valid survey types.
     * @return bool Whether or not the data is valid.
     */
    private function validate_survey_type( array $form_data, array $valid_survey_types ): bool 
    {
        return $this->validate_value_in_array(
            'survey_type',
            $form_data,
            $valid_survey_types
        );
    }

    /**
     * Check if the bedroom value is within our expected range.
     * 
     * @param array $form_data The data submitted to the form.
     * @param int $min_bedrooms Minimum number of bedrooms.
     * @param int $max_bedrooms Maximum number of bedrooms.
     * @return bool Whether or not the data is valid.
     */
    private function validate_max_bedrooms( array $form_data, int $min_bedrooms, int $max_bedrooms ): bool
    {
        $bedrooms = array_map('strval', range( $min_bedrooms, $max_bedrooms ) );

        return $this->validate_value_in_array(
            'bedrooms',
            $form_data,
            array_map('strval', range( $min_bedrooms, $max_bedrooms ) )
        );
    }
}
