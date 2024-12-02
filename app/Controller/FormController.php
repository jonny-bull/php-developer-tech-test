<?php

namespace App\Controller;

use App\Service\Matchers\SurveyorMatcher;
use App\Service\Matchers\Matcher;
use App\Service\Validators\SurveyorValidator;

class FormController extends Controller
{
    public function index()
    {
        $this->render('form.twig');
    }

    /**
     * Handles the form submission.
     */
    public function submit()
    {
        $matcher = new SurveyorMatcher($this->db(), $this->logger());

        // Validate form response.
        $validator = new SurveyorValidator($_POST, $this->logger());

        // Exit early if validation fails, saving a few database hits.
        if ( $validator->validate_form() === false ) {
            $this->render('results.twig', [
                'matchedCompanies'  => [],
            ]);
            
            return;
        }

        // Look up results.
        $matches = $matcher->match( $_POST[ 'bedrooms' ], $_POST[ 'postcode' ], $_POST['survey_type'] );
        $results = $matcher->results();

        // If there are results, deduct credits from the effected companies.
        // If any of the companies now have zero credits, log that.
        if ( $results !== [] ) {
            $company_ids = array_column( $results, 'id' );

            $matcher->deductCredits( $company_ids );
            $matcher->logZeroCredits( $company_ids );
        }

        // Render the template and any results we have.
        $this->render('results.twig', [
            'matchedCompanies'  => $matcher->results(),
        ]);
    }
}
