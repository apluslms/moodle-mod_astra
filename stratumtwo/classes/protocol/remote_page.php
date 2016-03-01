<?php
namespace mod_stratumtwo\protocol;

defined('MOODLE_INTERNAL') || die;

class remote_page {
    
    protected $response; // string, the whole response from the server
    protected $DOMdoc; // \DOMDocument instance
    protected $metaNodes = null; // cache \DOMNodeList
    
    // fields for storing content and metadata after the response has been parsed
    protected $content; // string, content that is shown to user
    protected $meta = array(); // associative array
    protected $isGraded = false;
    protected $isAccepted = false;
    protected $isWait = false;
    protected $points; // int, if grader returns results synchronously
    
    /**
     * Create a remote page: a HTML page whose content and metadata are
     * downloaded from a server.
     * @param string $url URL of the remote page
     * @param bool $post true to set request method to HTTP POST, otherwise GET is used
     * @param array $data POST payload key-value pairs
     * @param array $files array or files to upload. Keys are used as POST data keys and
     * values should be full filepaths to the files.
     * @throws \mod_stratumtwo\protocol\remote_page_exception if there are errors
     * in connecting to the server
     */
    public function __construct($url, $post = false, $data = null, $files = null) {
        $this->response = $this->request($url, $post, $data, $files);
        $this->DOMdoc = new \DOMDocument();
        if ($this->DOMdoc->loadHTML($this->response) === false)
            throw new \mod_stratumtwo\protocol\remote_page_exception('DOMDocument::loadHTML could not load the response');
    }
    
    /**
     * Send a HTTP request.
     * @param string $url URL target of the HTTP request
     * @param bool $post true to set request method to HTTP POST, otherwise GET is used
     * @param array $data POST payload key-value pairs
     * @param array $files array or files to upload. Keys are used as POST data keys and
     * values should be full filepaths to the files.
     * @param string $api_key API key for authorization, null if not used
     * @throws mod_stratumtwo\protocol\remote_page_exception if there are errors
     * in connecting to the server
     * @return string the response
     */
    protected function request($url, $post = false, $data = null, $files = null, $api_key = null) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true, // response as string
                CURLOPT_HEADER => false, // no header in output
                CURLOPT_FOLLOWLOCATION => true, // follow redirects (Location header)
                CURLOPT_MAXREDIRS => 10,
                
                CURLOPT_SSL_VERIFYPEER => true, // HTTPS certificate and security
                CURLOPT_SSL_VERIFYHOST => 2,
                //CURLOPT_CAPATH => self::CAPATH,
                //CURLOPT_CAINFO => self::stratum_CA_file_path(), //TODO
        ));
        
        if (!is_null($api_key)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Authorization: key='. $api_key, // HTTP request header for API key
            ));
        }
        
        if ($post) {
            // make the request HTTP POST instead of GET and add post data key-value pairs
            curl_setopt($ch, CURLOPT_POST, true);
            if (empty($files)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, 'i_', '&'));
                // request Content-Type: application/x-www-form-urlencoded
            } else {
                $postData = $data;
                if (empty($data)) {
                    $postData = array();
                }
                foreach ($files as $name => $filepath) {
                    if (function_exists('curl_file_create')) {
                        $postData[$name] = curl_file_create($filepath); //TODO MIME type and original filename
                    } else {
                        // older PHP than 5.5.0
                        // if any POST data value starts with @-sign, it is assumed to be a filepath
                        $postData[$name] = '@'. $filepath;
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                // request Content-Type: multipart/form-data
            }
        }
        
        $response = curl_exec($ch);
        if ($response === false) {
            // curl failed
            $error = curl_error($ch);
            curl_close($ch);
            throw new \mod_stratumtwo\protocol\remote_page_exception($error);
        } else {
            // check HTTP status code
            $resStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($resStatus != 200) {
                // server returned some error message
                $error = "curl HTTP response status: $resStatus";
                throw new \mod_stratumtwo\protocol\remote_page_exception($error);
            }
        }
        return $response; // response as string
    }
    
    /**
     * Load the exercise page (usually containing instructions and submission form)
     * from the exercise service
     * @param \mod_stratumtwo_exercise $exercise
     * @return \stdClass with field content
     */
    public function loadExercisePage(\mod_stratumtwo_exercise $exercise) {
        $this->parsePageContent($exercise);
        $page = new \stdClass();
        $page->content = $this->content;
        return $page;
    }
    
    /**
     * Load the feedback page for a new submission and store the grading results
     * if the submission was graded synchronously.
     * @param \mod_stratumtwo_exercise $exercise
     * @param \mod_stratumtwo_submission $submission
     * @param string $noPenalties
     */
    public function loadFeedbackPage(\mod_stratumtwo_exercise $exercise,
            \mod_stratumtwo_submission $submission, $noPenalties = false) {
        $this->parsePageContent($exercise);
        $feedback = $this->content;
        if ($this->isAccepted) {
            if ($this->isGraded) {
                $servicePoints = $this->points;
                if (isset($this->meta['max_points'])) {
                    $serviceMaxPoints = $this->meta['max_points'];
                } else {
                    $serviceMaxPoints = $exercise->getMaxPoints();
                }
                
                $submission->grade($servicePoints, $serviceMaxPoints, $feedback, null, $noPenalties);
            } else {
                $submission->setWaiting();
                $submission->save();
            }
        } else {
            $submission->setError();
            $submission->save();
        }
    }
    
    protected function parsePageContent(\mod_stratumtwo_exercise $ex) {
        $this->fixFormAction($ex);
        $this->content = $this->getElementOrBody('exercise');
        
        // parse metadata
        $maxPoints = $this->getMeta('max-points');
        if ($maxPoints !== null)
            $this->meta['max_points'] = $maxPoints;
        $maxPoints = $this->getMeta('max_points'); // underscore preferred
        if ($maxPoints !== null)
            $this->meta['max_points'] = $maxPoints;
        
        $this->meta['status'] = $this->getMeta('status');
        if ($this->meta['status'] === 'accepted') {
            $this->isAccepted = true; // accepted for async grading
            if ($this->getMeta('wait'))
                $this->isWait = true;
        }
        
        $this->meta['points'] = $this->getMeta('points');
        if ($this->meta['points'] !== null) {
            $this->points = (int) $this->meta['points'];
            $this->isGraded = true;
            $this->isAccepted = true;
            $this->isWait = false;
        }
        
        $metaTitle = $this->getMeta('DC.Title');
        if ($metaTitle)
            $this->meta['title'] = $metaTitle;
        else
            $this->meta['title'] = $this->getTitle();
        
        $this->meta['description'] = $this->getMeta('DC.Description');
    }
    
    /**
     * Return HTML string of the element with the given id, or body if no id is given or
     * the id does not exist in the HTML document.
     * @param string|null $id ID value of the HTML element that should be returned,
     * null for body
     * @return NULL|string HTML string, null if the document has no body
     */
    protected function getElementOrBody($id = null) {
        $element = null;
        if (!is_null($id)) {
            $element = $this->DOMdoc->getElementById($id);
            if (!is_null($element)) {
                if ($element->getAttribute('id') == 'exercise') {
                    $element->removeAttribute('id');
                    // remove id since this content is inserted to a new div with id=exercise
                }
                return $this->DOMdoc->saveHTML($element);
            }
        }
        
        // resort to body since the id content was not found
        // Note: using id is more reliable than parsing content from body
        $nodesList = $this->DOMdoc->getElementsByTagName('body');
        if ($nodesList->length == 0)
            return null;
        $element = $nodesList->item(0); // there should always be exactly one body
        $html = '';
        // create HTML strings of all child nodes under body and concatenate
        // -> do not store the body element itself since this content will be
        // inserted to another HTML document
        foreach ($element->getElementsByTagName('*') as $child) {
            $html .= $this->DOMdoc->saveHTML($child);
        }
        if ($html === '') {
            // the loop above does not include text directly under body, so
            // let's copy all text content if we have no other content yet
            $html = $element->textContent;
        }
        return $html;
    }
    
    protected function fixFormAction(\mod_stratumtwo_exercise $ex) {
        $nodesList = $this->DOMdoc->getElementsByTagName('form');
        // set action to the new submission handler in Moodle
        $formAction = \mod_stratumtwo\urls\urls::newSubmissionHandler($ex);
        foreach ($nodesList as $formNode) {
            if ($formNode->nodeType == \XML_ELEMENT_NODE) {
                $formNode->setAttribute('action', $formAction);
            }
        }
    }
    
    /**
     * Return the value of a meta element.
     * @param string $name name attribute value of the meta element
     * @return string|null the value, or null if it is not set
     */
    protected function getMeta($name) {
        if (!isset($this->metaNodes))
            $this->metaNodes = $this->DOMdoc->getElementsByTagName('meta');
        foreach ($this->metaNodes as $node) {
            if ($node->nodeType == \XML_ELEMENT_NODE && $node->getAttribute('name') == $name) {
                if ($node->hasAttribute('value'))
                    return $node->getAttribute('value');
                else if ($node->hasAttribute('content'))
                    return $node->getAttribute('content');
                else
                    return null;
            }
        }
        return null;
    }
    
    protected function getTitle() {
        $titleNodes = $this->DOMdoc->getElementsByTagName('title');
        foreach ($titleNodes as $node) { // these is usually exactly one title element
            return $node->textContent; 
        }
        return null;
    }
}