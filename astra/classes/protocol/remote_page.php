<?php
namespace mod_astra\protocol;

defined('MOODLE_INTERNAL') || die;

/**
 * Class remote_page represents an HTML document that is downloaded from
 * a server. Either HTTP GET or POST is supported for requesting the page.
 *
 * Derived from A+ (a-plus/lib/remote_page.py and a-plus/exercise/protocol/aplus.py).
 */
class remote_page {
    
    protected $url;
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
    
    protected $aplusHeadElements; // \DOMNode[], nodes in document head with aplus attribute
    
    /**
     * Create a remote page: a HTML page whose content and metadata are
     * downloaded from a server.
     * @param string $url URL of the remote page
     * @param bool $post true to set request method to HTTP POST, otherwise GET is used
     * @param array $data POST payload key-value pairs
     * @param array $files array of files to upload. Keys are used as POST data keys and
     * values are objects with fields filename (original base name), filepath (full path)
     * and mimetype.
     * @param string $api_key API key for authorization, null if not used
     * @throws \mod_astra\protocol\remote_page_exception if there are errors
     * in connecting to the server
     */
    public function __construct($url, $post = false, $data = null, $files = null, $api_key = null) {
        $this->url = $url;
        $this->response = self::request($url, $post, $data, $files, $api_key);
        $this->DOMdoc = new \DOMDocument();
        if ($this->DOMdoc->loadHTML($this->response) === false)
            throw new \mod_astra\protocol\remote_page_exception('DOMDocument::loadHTML could not load the response');
    }
    
    /**
     * Send a HTTP request.
     * @param string $url URL target of the HTTP request
     * @param bool $post true to set request method to HTTP POST, otherwise GET is used
     * @param array $data POST payload key-value pairs
     * @param array $files array or files to upload. Keys are used as POST data keys and
     * values are objects with fields filename, filepath and mimetype.
     * @param string $api_key API key for authorization, null if not used
     * @throws \mod_astra\protocol\service_connection_exception if there are errors
     * in connecting to the server
     * @throws \mod_astra\protocol\exercise_service_exception if there is an error
     * in the exercise service
     * @return string the response
     */
    public static function request($url, $post = false, $data = null, $files = null, $api_key = null) {
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true, // response as string
                CURLOPT_HEADER => false, // no header in output
                CURLOPT_FOLLOWLOCATION => true, // follow redirects (Location header)
                CURLOPT_MAXREDIRS => 10,
                
                CURLOPT_SSL_VERIFYPEER => true, // HTTPS certificate and security
                CURLOPT_SSL_VERIFYHOST => 2,
                //CURLOPT_CAPATH => self::CAPATH, // a directory that holds multiple CA certificates
                CURLOPT_CAINFO => self::exercise_service_CA_path(),
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
                //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, 'i_', '&'));
                // avoid array syntax in the POST data since Django does not parse it the way we want
                curl_setopt($ch, CURLOPT_POSTFIELDS, self::build_query($data));
                // request Content-Type: application/x-www-form-urlencoded
            } else {
                $postData = $data;
                if (empty($data)) {
                    $postData = array();
                }
                foreach ($files as $name => $fileobj) {
                    if (function_exists('curl_file_create')) {
                        $postData[$name] = curl_file_create($fileobj->filepath,
                                $fileobj->mimetype, $fileobj->filename);
                    } else {
                        // older PHP than 5.5.0
                        // if any POST data value starts with @-sign, it is assumed to be a filepath
                        $postData[$name] = "@{$fileobj->filepath};type={$fileobj->mimetype}";
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
            throw new \mod_astra\protocol\service_connection_exception($error);
        } else {
            // check HTTP status code
            $resStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($resStatus != 200) {
                // server returned some error message
                $error = "curl HTTP response status: $resStatus";
                throw new \mod_astra\protocol\exercise_service_exception($error);
            }
        }
        return $response; // response as string
    }
    
    /**
     * Return the file path to CA file that is used to verify secure HTTPS
     * connections to the exercise service.
     */
    protected static function exercise_service_CA_path() {
        global $CFG;
        return $CFG->dirroot .'/mod/'. \mod_astra_exercise_round::TABLE .'/exservice_CA.pem';
    }
    
    /**
     * Build URL-encoded query string from $data. This is a replacement for
     * http_build_query. This method does not use array syntax in the result like
     * http_build_query does (when there are multiple values for the same name,
     * for example because an HTML form has multiple checkboxes that can be all selected).
     * @param array $data associative array, null allowed too
     * @return string
     */
    public static function build_query($data) {
        if (empty($data)) {
            return '';
        }
        $q = self::build_query_helper($data);
        if (!empty($q)) { // drop the trailing &
            $q = \substr($q, 0, -1);
        }
        return $q;
    }
    
    private static function build_query_helper($data, $outerKey = null) {
        $q = '';
        foreach ($data as $key => $val) {
            if (\is_array($val)) {
                $q .= self::build_query_helper($val, $key);
            } else {
                if (\is_numeric($key) && $outerKey !== null) {
                    $key = $outerKey;
                }
                $q .= \urlencode($key) .'='. \urlencode($val) .'&';
            }
        }
        return $q;
    }
    
    /**
     * Load the exercise page (usually containing instructions and submission form,
     * or chapter content) from the exercise service.
     * @param \mod_astra_learning_object $learningObject
     * @return \stdClass with fields content and remote_page
     */
    public function loadExercisePage(\mod_astra_learning_object $learningObject) {
        $this->parsePageContent($learningObject);
        $page = new \stdClass();
        $page->content = $this->content;
        $page->remote_page = $this;
        return $page;
    }
    
    /**
     * Load the feedback page for a new submission and store the grading results
     * if the submission was graded synchronously.
     * @param \mod_astra_exercise $exercise
     * @param \mod_astra_submission $submission
     * @param string $noPenalties
     */
    public function loadFeedbackPage(\mod_astra_exercise $exercise,
            \mod_astra_submission $submission, $noPenalties = false) {
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
    
    protected function parsePageContent(\mod_astra_learning_object $lobj) {
        if ($lobj->isSubmittable()) {
            $this->fixFormAction($lobj);
            $this->fixFormMultipleCheckboxes();
        } else {
            // chapter: find embedded exercise elements and add exercise URL to data attributes,
            // AJAX Javascript will load the exercise to the DOM
            $replaceValues = array();
            foreach ($lobj->getChildren() as $childEx) {
                $replaceValues[$childEx->getOrder()] = array(
                        'data-aplus-order' => $childEx->getOrder(),
                        'data-aplus-exercise' => \mod_astra\urls\urls::exercise($childEx),
                );
            }
            $this->findAndReplaceElementAttributes('div', 'data-aplus-exercise', $replaceValues);
        }
        // fix relative URLs (make them absolute with the address of the origin server)
        $this->fixRelativeUrls();
        
        // find tags in <head> that have attribute data-aplus
        $this->aplusHeadElements = $this->findHeadElementsWithAttribute('data-aplus');
        
        // find learning object content
        $this->content = $this->getElementOrBody(array('exercise', 'aplus', 'chapter'),
                array('class' => 'entry-content'));
        
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
     * Return href values of link elements in the document head that have
     * the attribute data-aplus.
     * (The page must be loaded before calling this.)
     * @return array of strings
     */
    public function getInjectedCSS_URLs() {
        $css_urls = array();
        foreach ($this->aplusHeadElements as $element) {
            if ($element->nodeName == 'link' && $element->getAttribute('rel') == 'stylesheet') {
                $href = $element->getAttribute('href');
                if ($href != '') {
                    $css_urls[] = $href;
                }
            }
        }
        return $css_urls;
    }
    
    /**
     * Return src values and inline Javascript code strings of script elements
     * in the document head that have the attribute data-aplus.
     * (The page must be loaded before calling this.)
     * @return array of two arrays: the first array is a list of URLs (strings) to
     * JS code files; the second array is a list of Javascript code strings (inline code).
     */
    public function getInjectedJsUrlsAndInline() {
        $js_urls = array();
        $js_inline_elements = array();
        foreach ($this->aplusHeadElements as $element) {
            if ($element->nodeName == 'script') {
                $src = $element->getAttribute('src');
                if ($src != '') {
                    // link to JS code file
                    $js_urls[] = $src;
                } else {
                    // inline JS code
                    $js_inline_elements[] = $element->textContent; // code inside <script> tags
                }
            }
        }
        return array($js_urls, $js_inline_elements);
    }
    
    /**
     * Return HTML string of the element with the given id or attribute value, or
     * body if no element is found with the given id or attribute in the HTML document.
     * @param array $ids array of ID values to search for; the first hit is returned.
     * Use empty array to avoid searching for IDs.
     * @param array $searchAttrs array of (div) element attributes to search for; the first hit
     * is returned but only if no ID was found. Array keys are attribute names and
     * array values are attribute values.
     * @return NULL|string HTML string, null if the document has no body
     */
    protected function getElementOrBody(array $ids, array $searchAttrs) {
        $element = null;
        // search for id
        foreach ($ids as $id) {
            $element = $this->DOMdoc->getElementById($id);
            if (!is_null($element)) {
                if ($id == 'exercise') {
                    $element->removeAttribute('id');
                    // remove id since this content is inserted to a new div with id=exercise
                }
                return $this->DOMdoc->saveHTML($element);
            }
        }
        
        // search for attributes
        foreach ($this->DOMdoc->getElementsByTagName('div') as $node) {
            if ($node->nodeType == \XML_ELEMENT_NODE) {
                foreach ($searchAttrs as $attrName => $attrValue) {
                    if ($node->getAttribute($attrName) == $attrValue) {
                        return $this->DOMdoc->saveHTML($node);
                    }
                }
            }
        }
        
        // resort to body since no id/attr was found
        // Note: using id is more reliable than parsing content from body
        $nodesList = $this->DOMdoc->getElementsByTagName('body');
        if ($nodesList->length == 0)
            return null;
        $element = $nodesList->item(0); // there should always be exactly one body
        $html = '';
        // create HTML strings of all child nodes under body and concatenate
        // -> do not store the body element itself since this content will be
        // inserted to another HTML document
        foreach ($element->childNodes as $child) {
            $html .= $this->DOMdoc->saveHTML($child);
        }
        
        return $html;
    }
    
    protected function fixFormAction(\mod_astra_exercise $ex) {
        $nodesList = $this->DOMdoc->getElementsByTagName('form');
        // set action to the new submission handler in Moodle
        $formAction = \mod_astra\urls\urls::newSubmissionHandler($ex);
        foreach ($nodesList as $formNode) {
            if ($formNode->nodeType == \XML_ELEMENT_NODE) {
                $formNode->setAttribute('action', $formAction);
            }
        }
    }
    
    /**
     * Add array notation to form checkbox groups.
     * (If there are checkbox inputs that use the same name but the name
     * does not end with brackets []. PHP cannot parse multi-checkbox form input
     * without the array notation.)
     */
    protected function fixFormMultipleCheckboxes() {
        $nodesList = $this->DOMdoc->getElementsByTagName('input');
        $checkboxesByName = array();
        // find checkbox groups (multiple checkboxes with the same name) such that
        // their names do not use the array notation [] yet
        foreach ($nodesList as $inputNode) {
            if ($inputNode->nodeType == \XML_ELEMENT_NODE && 
                    $inputNode->getAttribute('type') == 'checkbox') {
                $name = $inputNode->getAttribute('name');
                if ($name != '' && \strpos(\strrev($name), '][') !== 0) {
                    // name attr not empty and does not already end with []
                    if (!isset($checkboxesByName[$name])) {
                        $checkboxesByName[$name] = array();
                    }
                    $checkboxesByName[$name][] = $inputNode;
                }
            }
        }
        
        // add [] to the checkbox names, if they form a group
        foreach ($checkboxesByName as $name => $inputNodes) {
            if (\count($inputNodes) > 1) {
                foreach ($inputNodes as $inputNode) {
                    $inputNode->setAttribute('name', $name .'[]');
                }
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
    
    /**
     * Find elements of type $tagName that have attribute $attrName, and the value of
     * the attribute is a key in $replaceValues. Then, replace the attributes of the
     * element with the attribute values in $replaceValues. Only the attributes
     * given in $replaceValues are affected.
     * @param string $tagName name of the elements/tags that are searched
     * @param strig $attrName attribute name that is used in the search of elements
     * @param array $replaceValues array of new attribute values, separately for each
     * element. Outer array keys are values of attribute $attrName, the matching inner array
     * is used to replace attribute values. The inner array has attribute names as keys and
     * attribute values as array values.
     */
    protected function findAndReplaceElementAttributes($tagName, $attrName, array $replaceValues) {
        foreach ($this->DOMdoc->getElementsByTagName($tagName) as $node) {
            if ($node->nodeType == \XML_ELEMENT_NODE && $node->hasAttribute($attrName)) {
                if (isset($replaceValues[$node->getAttribute($attrName)])) {
                    $attrsToReplace = $replaceValues[$node->getAttribute($attrName)];
                    foreach ($attrsToReplace as $replaceAttrName => $replaceAttrValue) {
                        $node->setAttribute($replaceAttrName, $replaceAttrValue);
                    }
                }
            }
        }
    }
    
    /**
     * Return an array of DOMNodes that are located inside the document head
     * element and have attribute $attrName.
     * @param string $attrName attribute name to search
     * @return \DOMNode[]
     */
    protected function findHeadElementsWithAttribute($attrName) {
        $elements = array();
        // there should be one head element
        foreach ($this->DOMdoc->getElementsByTagName('head') as $head) {
            foreach ($head->childNodes as $node) {
                if ($node->nodeType == \XML_ELEMENT_NODE && $node->hasAttribute($attrName)) {
                    $elements[] = $node;
                }
            }
        }
        return $elements;
    }
    
    /**
     * Fix relative URLs so that the address points to the origin server.
     * Otherwise, the relative URL would be interpreted as relative inside the
     * Moodle server.
     */
    protected function fixRelativeUrls() {
        // parse remote server domain and base path
        $remoteUrlComponents = \parse_url($this->url);
        $domain = '';
        $path = '';
        if (isset($remoteUrlComponents['scheme'])) {
            $domain .= $remoteUrlComponents['scheme'] .'://';
        }
        if (isset($remoteUrlComponents['host'])) {
            $domain .= $remoteUrlComponents['host'];
        }
        if (isset($remoteUrlComponents['port'])) {
            $domain .= ':'. $remoteUrlComponents['port'];
        }
        
        if (isset($remoteUrlComponents['path'])) {
            $path = $remoteUrlComponents['path'];
        }
        
        $this->_fixRelativeUrls($domain, $path, 'img', 'src');
        $this->_fixRelativeUrls($domain, $path, 'script', 'src');
        $this->_fixRelativeUrls($domain, $path, 'link', 'href');
        $this->_fixRelativeUrls($domain, $path, 'a', 'href');
    }
    
    /**
     * Find relative URLs in the document and make them point to the origin server.
     * @param string $domain domain of the origin server, e.g., 'https://example.com'
     * @param string $path base path to use if original URL paths are relative
     * @param string $tagName search the document for these elements, e.g., 'img'
     * @param string $attrName fix URLs in this attribute of the element, e.g., 'src'
     */
    protected function _fixRelativeUrls($domain, $path, $tagName, $attrName) {
        $pattern = '%^(#|.+://|//)%';
        // recognize absolute URLs (https:// or //) or anchor URLs (#someid)
        foreach ($this->DOMdoc->getElementsByTagName($tagName) as $elem) {
            if ($elem->nodeType == \XML_ELEMENT_NODE && $elem->hasAttribute($attrName)) {
                $value = $elem->getAttribute($attrName);
                if (!empty($value) && \preg_match($pattern, $value) === 0) {
                    // not absolute URL
                    if ($value[0] == '/') { // absolute path
                        $newVal = $domain . $value;
                    } else {
                        $newVal = $domain . $path . $value;
                    }
                    $elem->setAttribute($attrName, $newVal);
                }
            }
        }
    }
}