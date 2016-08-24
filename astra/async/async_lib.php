<?php

defined('MOODLE_INTERNAL') || die();

class mod_astra_async_forbidden_access_exception extends \Exception {
}

// Functions derived from A+ (a-plus/exercise/async_views.py)

/**
 * Handle asynchronous GET or POST HTTP request from the exercise service.
 * @param mod_astra_exercise $exercise
 * @param stdClass $user
 * @param array $postData null if GET
 * @param mod_astra_submission $submission the submission that is graded, or null
 * if creating a new graded submission
 * @return array
 * @throws mod_astra_async_forbidden_access_exception if the request originates from
 * an IP address that does not match the exercise service.
 */
function astra_async_submission_handler(mod_astra_exercise $exercise,
        stdClass $user, $postData, mod_astra_submission $submission = null) {
    // async requests should only originate from the exercise service, check the request IP address
    if ($_SERVER['REMOTE_ADDR'] != astra_get_service_ip($exercise->getServiceUrl())) {
        throw new mod_astra_async_forbidden_access_exception('Access denied from this IP address.');
    }
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // GET requests receive a JSON response about the current state of the exercise for the user
        return astra_get_async_submission_info($exercise, $user);
    }
    // create a new submission if it is not provided in parameters
    if (is_null($submission)) {
        if (!$exercise->isSubmissionAllowed($user)) {
            return array(
                'success' => false,
                'errors'  => array('New submissions are not allowed anymore.'),
            );
        }
        // create a new submission
        $sbmsId = mod_astra_submission::createNewSubmission($exercise, $user->id);
        if ($sbmsId != 0)
            $submission = mod_astra_submission::createFromId($sbmsId);
        else
            return array(
                'success' => false,
                'errors'  => array('Creating a new submission in the database failed.'),
            );
    }
    
    return astra_post_async_submission($exercise, $submission, $user, $postData);
}

/**
 * Grade the submission using the $postData.
 * @param mod_astra_exercise $exercise
 * @param mod_astra_submission $submission
 * @param stdClass $user
 * @param array $postData
 */
function astra_post_async_submission(mod_astra_exercise $exercise,
        mod_astra_submission $submission, stdClass $user, $postData) {
    global $PAGE;
    
    $require = function($name, $optional = false) use ($postData) {
        if (isset($postData[$name]))
            return $postData[$name];
        else if ($optional)
            return null;
        else
            throw new \Exception("POST parameter '$name' is required");
    };
    
    try {
        // read the fields from the $postData array
        $points = $require('points');
        $max_points = $require('max_points');
        $feedback = $require('feedback', true);
        $grading_payload = $require('grading_payload', true);
        $error = $require('error', true);
        
    } catch (\Exception $e) { // required field missing
        $submission->setError();
        // set feedback: exercise service malfunctioning
        $renderer = $PAGE->get_renderer(\mod_astra_exercise_round::MODNAME);
        $ctx = new \stdClass();
        $ctx->error = get_string('servicemalfunction', \mod_astra_exercise_round::MODNAME);
        $submission->setFeedback($renderer->render_from_template(\mod_astra_exercise_round::MODNAME . '/_error_alert', $ctx));
        $submission->save();
        return array(
                'success' => false,
                'errors' => array('Invalid POST data.'),
        );
    }
    // grade the submission
    $submission->grade($points, $max_points, $feedback, $postData);
    if ($error) { // exercise service set an error state
        $submission->setError();
        $submission->save();
    }
    return array(
            'success' => true,
            'errors' => array(),
    );
}

/**
 * Send a JSON response about the current state of the exercise for the given user.
 * @param mod_astra_exercise $exercise
 * @param stdClass $user
 */
function astra_get_async_submission_info(mod_astra_exercise $exercise, stdClass $user) {
    $submissions = $exercise->getSubmissionsForStudent($user->id, false, 'grade DESC, submissiontime ASC');
    // sort so that the best submissions come first
    if ($submissions->valid()) { // not empty
        $points = $submissions->current()->grade; // best points currently
    } else {
        $points = 0;
    }
    $submissionCount = \iterator_count($submissions);
    $submissions->close();
    return array(
            'max_points' => $exercise->getMaxPoints(),
            'max_submissions' => $exercise->getMaxSubmissions(),
            'current_submissions' => $submissionCount,
            'current_points' => $points,
            'is_open' => $exercise->getExerciseRound()->isOpen(),
    );
}

/**
 * Return the IP address of the host of the given URL.
 * @param string $url
 * @return string
 */
function astra_get_service_ip($url) {
    // works only with IPv4
    return gethostbyname(parse_url($url, PHP_URL_HOST));
}

function astra_send_json_response($data, $http_status = null) {
    if ($http_status !== null) {
        http_response_code($http_status);
    }
    echo json_encode($data);
    exit(0);
}
