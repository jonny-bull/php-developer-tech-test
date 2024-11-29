<?php

namespace App\Service\Matchers;

use Monolog\Logger;

abstract class Matcher
{
    private $db;
    private $logger;
    private $matches = [];
    
    /**
     * Construct the class
     * 
     * @param \PDO $db The database connection.
     * @param Logger $logger The logger object.
     */
    public function __construct( \PDO $db, Logger $logger ) 
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Find any companies that match our criteria.
     * 
     * @param int $bedrooms The number of bedrooms.
     * @param string $postcode_prefix The start of the postcode.
     * @param string $survey_type The type of survey requested.
     * @param int $limit The number of companies to return. Defaults to 3.
     */
    public function match( int $bedrooms, string $postcode_prefix, string $survey_type, int $limit = 3 )
    {
        $postcode_reduction = substr( $postcode_prefix, 0, 3 );
        $postcode_clean = preg_replace( '/[0-9]+/', '', $postcode_reduction );

        try {
            // There's nothing in the description about limiting by credits and active status.
            // But also...you probably want that, right?
            $match_query = $this->db->prepare(
                'SELECT DISTINCT companies.*
                FROM company_matching_settings
                JOIN companies on company_id = companies.id
                WHERE company_matching_settings.bedrooms LIKE :bedrooms
                AND company_matching_settings.postcodes LIKE :postcode_clean
                AND company_matching_settings.type = :survey_type
                AND companies.credits > 0
                AND companies.active = 1
                ORDER BY RAND() LIMIT :match_limit'
            );

            // Bind the values to the query.
            $match_query->bindValue( ':bedrooms', '%"' . $bedrooms . '"%', \PDO::PARAM_STR );
            $match_query->bindValue( ':postcode_clean', '%"' . $postcode_clean . '"%', \PDO::PARAM_STR );
            $match_query->bindValue( ':survey_type', $survey_type, \PDO::PARAM_STR );
            $match_query->bindValue( ':match_limit', $limit, \PDO::PARAM_INT );

            // Execute the statement and set our 'matches' property to the results.
            $match_query->execute();
            $this->matches = $match_query->fetchAll();
        } catch ( \PDOException $e ) {
            $this->logger->error(
                'Database error: Unable to match companies',
                [
                    'error' => $e,
                ]
            );
        }
    }

    /**
     * Gets the matches property for this class.
     * 
     * @return array The results, or an empty array.
     */
    public function results(): array
    {
        return $this->matches;
    }

    /**
     * Deducts the appropriate number of credits from companies of the given IDs.
     * 
     * @param array $company_ids The IDs that should have their 
     * @param int $deduct_amount The amount of credits to deduct.
     */
    public function deductCredits( array $company_ids, int $deduct_amount = 1 )
    { 
        // Exit early if there are no IDs to deduct credits from.
        if ( $company_ids === [] ) {
            return;
        }
        
        // Create parameters that match the length of the ID array.
        $company_id_params = str_repeat( '?,', count( $company_ids ) - 1 ) . '?';

        try {
            $query = $this->db->prepare(
                "UPDATE `companies`
                SET `credits` = `credits` - ?
                WHERE `credits` > 0 AND id IN ($company_id_params)"
            );

            // Force the company IDs to integers. Prepend the deduction amount to make it the first parameter.
            $int_company_ids = array_map( 'intval', $company_ids );
            array_unshift( $int_company_ids , $deduct_amount );
            $query->execute( $int_company_ids );
        } catch (\PDOException $e) {
            $this->logger->error(
                'Database error: Unable to deduct credits',
                [
                    'error' => $e,
                    'company_ids' => $company_ids
                ]
            );
        }
    }

    /**
     * Logs a warning if a company has reached zero credits.
     * Log data includes company name and ID.
     * Timestamps are already logged as part of Monolog.
     * 
     * @param array $ids_to_check Limit to the IDs that have just been impacted.
     */
    public function logZeroCredits( array $ids_to_check ): void {
        try {
            // Create parameters that match the length of the ID array. Force IDs to be integers.
            $id_param = str_repeat( '?,', count( $ids_to_check ) - 1 ) . '?';
            $int_ids_to_check = array_map( 'intval', $ids_to_check );

            $query = $this->db->prepare(
                "SELECT *
                FROM `companies`
                WHERE `companies.credits` = 0
                AND id IN ($id_param)"
            );

            $query->execute( $int_ids_to_check );
            $zero_credits = $query->fetchAll( \PDO::FETCH_ASSOC );

            foreach ( $zero_credits as $zero_credit ) {
                $this->logger->warning(
                    'Company has hit zero credits',
                    [
                        'company_id' => $zero_credit['id'],
                        'company_name' => $zero_credit['name']
                    ]
                );
            }
        } catch ( \PDOException $e ) {
            $this->logger->error(
                'Database error: Unable to find companies with zero credits',
                [
                    'error' => $e,
                ]
            );
        }
    }
}
