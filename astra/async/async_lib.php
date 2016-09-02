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
    $ip_check_result = astra_check_request_comes_from_exercise_service($exercise->getServiceUrl());
    if ($ip_check_result !== true) {
        // IP address check failed, log error
        if (empty($ip_check_result['client_ip'])) {
            $event = \mod_astra\event\async_grading_failed::create(array(
                'context' => context_module::instance($exercise->getExerciseRound()->getCourseModule()->id),
                'relateduserid' => $user->id,
                'other' => array(
                    'error' => 'Async interface failed to determine HTTP client IP address.',
                ),
            ));
        } else {
            $event = \mod_astra\event\async_grading_failed::create(array(
                'context' => context_module::instance($exercise->getExerciseRound()->getCourseModule()->id),
                'relateduserid' => $user->id,
                'other' => array(
                    'error' => 'Client IP address does not match the exercise service (URL '. $exercise->getServiceUrl() .'). '.
                    'Client IP was '. $ip_check_result['client_ip'] .'; '.
                    'Service IP resolved to '. implode(', ', $ip_check_result['service_ip']),
                ),
            ));
        }
        $event->trigger();
        
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
 * Check that the client of the HTTP request is the same machine as $service_url refers to.
 * @param string $service_url URL of the machine that is checked against the client of the HTTP request
 * @return true if successful, array if not (keys client_ip, service_ip and result)
 */
function astra_check_request_comes_from_exercise_service($service_url) {
    $client_ip_address = $_SERVER['REMOTE_ADDR'];
    // If there is a proxy server between the client and Moodle,
    // REMOTE_ADDR could be the IP of the proxy.
    // $_SERVER['HTTP_X_FORWARDED_FOR'] might contain the client IP in that case,
    // but the HTTP headers are easily spoofed by malicious clients.
    if (empty($client_ip_address) || filter_var($client_ip_address, FILTER_VALIDATE_IP) === false) {
        // no client IP address
        return array(
                'client_ip' => '',
                'service_ip' => '',
                'result' => false,
        );
    }
    
    $service_host = parse_url($service_url, PHP_URL_HOST);
    // $service_host is an IP address if the $service_url contains an IP address as the host
    
    if (filter_var($service_host, FILTER_VALIDATE_IP) !== false) {
        // it is IP address
        if ($client_ip_address == $service_host) {
            return true;
        } else {
            return array(
                    'client_ip' => $client_ip_address,
                    'service_ip' => $service_host,
                    'result' => false,
            );
        }
    }
    
    // $service_host is a DNS hostname, look up the IP address
    if (filter_var($client_ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        // client IP is IPv4
        $records = dns_get_record($service_host, DNS_A);
        $service_ips = array();
        foreach ($records as $rec) {
            $service_ips[] = $rec['ip'];
        }
    } else {
        // client is IPv6
        $records = dns_get_record($service_host, DNS_AAAA);
        $service_ips = array();
        foreach ($records as $rec) {
            $service_ips[] = $rec['ipv6'];
        }
    }
    
    // compare service IP addresses to the client IP address
    foreach ($service_ips as $serv_ip) {
        if ($serv_ip == $client_ip_address) {
            return true;
        }
    }
    // client IP does not match service IP
    return array(
            'client_ip' => $client_ip_address,
            'service_ip' => $service_ips,
            'result' => false,
    );
}

function astra_send_json_response($data, $http_status = null) {
    if ($http_status !== null) {
        http_response_code($http_status);
    }
    echo json_encode($data);
    exit(0);
}
