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
    protected $aplusHeadElements; // \DOMNode[], nodes in document head with aplus attribute
    protected $astrajQueryScriptElements; // \DOMNode[], script elements in the document with data-astra-jquery attribute
    protected $response_headers = array();
    
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
     * @param null|string $stamp timestamp string for If-Modified-Since request header.
     * Only usable in HTTP GET requests.
     * @throws \mod_astra\protocol\remote_page_exception if there are errors
     * in connecting to the server
     * @throws \mod_astra\protocol\remote_page_not_modified if the $stamp argument is used
     * and the remote page has not been modified since that time
     */
    public function __construct($url, $post = false, $data = null, $files = null, $api_key = null,
            $stamp = null) {
        $this->url = $url;
        list($this->response, $this->response_headers) =
                self::request($url, $post, $data, $files, $api_key, $stamp);
        libxml_use_internal_errors(true); // Disable libxml errors
        // libxml (DOMDocument) prints a lot of warnings when it does not recognize (valid) HTML5 elements or
        // sees unexpected <p> end tags... Disable error reporting to avoid useless spam.
        $this->DOMdoc = new \DOMDocument();
        if ($this->DOMdoc->loadHTML($this->response) === false) {
            $message = '';
            foreach (libxml_get_errors() as $error) {
                switch ($error->level) {
                    case LIBXML_ERR_WARNING:
                        $message .= "Warning $error->code: ";
                        break;
                    case LIBXML_ERR_ERROR:
                        $message .= "Error $error->code: ";
                        break;
                    case LIBXML_ERR_FATAL:
                        $message .= "Fatal Error $error->code: ";
                        break;
                }
                $message .= trim($error->message);
                $message .= "\n";
            }
            libxml_clear_errors();
            throw new \mod_astra\protocol\remote_page_exception("DOMDocument::loadHTML could not load the response\n" .
                    $message);
        }
        libxml_clear_errors();
    }
    
    /**
     * Send a HTTP request.
     * @param string $url URL target of the HTTP request
     * @param bool $post true to set request method to HTTP POST, otherwise GET is used
     * @param array $data POST payload key-value pairs
     * @param array $files array or files to upload. Keys are used as POST data keys and
     * values are objects with fields filename, filepath and mimetype.
     * @param string $api_key API key for authorization, null if not used
     * @param null|string $stamp timestamp string for If-Modified-Since request header.
     * Only usable in HTTP GET requests.
     * @throws \mod_astra\protocol\service_connection_exception if there are errors
     * in connecting to the server
     * @throws \mod_astra\protocol\exercise_service_exception if there is an error
     * in the exercise service
     * @throws \mod_astra\protocol\remote_page_not_modified if the $stamp argument is used
     * and the remote page has not been modified since that time
     * @return array of two elements: the response (string) and an array of response headers (header names as keys)
     */
    public static function request($url, $post = false, $data = null, $files = null, $api_key = null,
            $stamp = null) {
        $response_headers = array();
        // callback for storing HTTP response headers
        $header_function = static function($curl_handle, $header) use (&$response_headers) {
            // array[HEADER] = VALUE
            $parts = explode(':', $header, 2);
            if (count($parts) == 2) {
                $response_headers[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            // else no colon (:) in the header, possibly the status line (HTTP 200)
            
            return strlen($header);
        };
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true, // response as string
                CURLOPT_HEADER => false, // no header in output
                CURLOPT_FOLLOWLOCATION => true, // follow redirects (Location header)
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_HEADERFUNCTION => $header_function, // save the HTTP response headers
                
                CURLOPT_SSL_VERIFYPEER => true, // HTTPS certificate and security
                CURLOPT_SSL_VERIFYHOST => 2,
        ));
        // CA certificates for HTTPS
        curl_setopt_array($ch, self::server_CA_certificate_curl_options());
        
        $request_headers = array();
        
        if (!is_null($api_key)) {
            $request_headers[] = 'Authorization: key='. $api_key; // HTTP request header for API key
        }
        if (!empty($stamp) && !$post) {
            $request_headers[] = 'If-Modified-Since: '. $stamp;
        }
        
        if (!empty($request_headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
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
            if ($resStatus == 304) {
                // not modified ($stamp argument was used)
                $expires = self::parseExpires(isset($response_headers['expires']) ?
                        $response_headers['expires'] : null);
                throw new \mod_astra\protocol\remote_page_not_modified($expires);
            } else if ($resStatus != 200) {
                // server returned some error message
                $error = "curl HTTP response status: $resStatus";
                throw new \mod_astra\protocol\exercise_service_exception($error);
            }
        }
        return array($response, $response_headers); // response as string, array of headers
    }
    
    /**
     * Return a cURL options array for setting the CA certificate location(s).
     * The CA certificates are used to verify the peer certificate in HTTPS connections.
     * The locations used may be configured in the admin settings of the Astra plugin.
     */
    protected static function server_CA_certificate_curl_options() {
        global $CFG;
        // typical defaults for Ubuntu
        //return array(CURLOPT_CAINFO => '/etc/ssl/certs/ca-certificates.crt');
        //return array(CURLOPT_CAPATH => '/etc/ssl/certs');
        
        $cainfo = get_config(\mod_astra_exercise_round::MODNAME, 'curl_cainfo');
        // use CAINFO if it is set, otherwise CAPATH
        if (empty($cainfo)) {
            $capath = get_config(\mod_astra_exercise_round::MODNAME, 'curl_capath');
            if (empty($capath)) {
                // Moodle sysadmin has set no values, try values from a Moodle function
                require_once($CFG->libdir .'/filelib.php');
                $cainfo = \curl::get_cacert();
                if (empty($cainfo)) {
                    return array();
                } else {
                    return array(CURLOPT_CAINFO => $cainfo);
                }
            }
            return array(CURLOPT_CAPATH => $capath);
        } else {
            return array(CURLOPT_CAINFO => $cainfo);
        }
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
     * @return \mod_astra\protocol\exercise_page the exercise page
     */
    public function loadExercisePage(\mod_astra_learning_object $learningObject) {
        $page = new \mod_astra\protocol\exercise_page($learningObject);
        $this->parsePageContent($learningObject, $page);
        return $page;
    }
    
    /**
     * Load the feedback page for a new submission and store the grading results
     * if the submission was graded synchronously.
     * @param \mod_astra_exercise $exercise
     * @param \mod_astra_submission $submission
     * @param string $noPenalties
     * @return \mod_astra\protocol\exercise_page the feedback page
     */
    public function loadFeedbackPage(\mod_astra_exercise $exercise,
            \mod_astra_submission $submission, $noPenalties = false) {
        $page = new \mod_astra\protocol\exercise_page($exercise);
        $this->parsePageContent($exercise, $page);
        if ($page->is_loaded) {
            $feedback = $page->content;
            if ($page->is_accepted) {
                if ($page->is_graded) {
                    $servicePoints = $page->points;
                    if (isset($page->meta['max_points'])) {
                        $serviceMaxPoints = $page->meta['max_points'];
                    } else {
                        $serviceMaxPoints = $exercise->getMaxPoints();
                    }
                    
                    $submission->grade($servicePoints, $serviceMaxPoints, $feedback, null, $noPenalties);
                } else {
                    $submission->setWaiting();
                    $submission->setFeedback($feedback);
                    $submission->save();
                }
            } else if ($page->is_rejected) {
                $submission->setRejected();
                $submission->setFeedback($feedback);
                $submission->save();
            } else {
                $submission->setError();
                $submission->setFeedback($feedback);
                $submission->save();
            }
        }
        return $page;
    }
    
    protected function parsePageContent(\mod_astra_learning_object $lobj, \mod_astra\protocol\exercise_page $page) {
        if ($lobj->isSubmittable()) {
            $this->fixFormAction($lobj);
            $this->fixFormMultipleCheckboxes();
        } else {
            // chapter: find embedded exercise elements and add exercise URL to data attributes,
            // AJAX Javascript will load the exercise to the DOM
            $replaceValues = array();
            foreach ($lobj->getChildren() as $childEx) {
                $replaceValues[] = array(
                        'id' => 'chapter-exercise-'. $childEx->getOrder(),
                        'data-aplus-exercise' => \mod_astra\urls\urls::exercise($childEx),
                );
            }
            $this->findAndReplaceElementAttributes('div', 'data-aplus-exercise', $replaceValues);
        }
        // fix relative URLs (make them absolute with the address of the origin server)
        $this->fixRelativeUrls();
        
        // find tags in <head> that have attribute data-aplus
        $this->aplusHeadElements = $this->findHeadElementsWithAttribute('data-aplus');
        
        // find script tags in the document with attribute data-astra-jquery="$" (attribute value is optional)
        $this->astrajQueryScriptElements = $this->findScriptElementsWithAttribute('data-astra-jquery');
        // remove the script tags from the document, their contents shall be inserted again later
        // as Moodle page requirements (not done in this class)
        foreach ($this->astrajQueryScriptElements as $scriptElem) {
            $scriptElem->parentNode->removeChild($scriptElem);
        }
        
        $page->is_loaded = true;
        
        // save CSS and JS code or elements that should be injected to the final page
        // (these values are strings)
        $page->injected_css_urls = $this->getInjectedCSS_URLs();
        $page->injected_js_urls_and_inline = $this->getInjectedJsUrlsAndInline();
        $page->inline_jquery_scripts = $this->getInlinejQueryScripts();
        
        // find learning object content
        $page->content = $this->getElementOrBody(array('exercise', 'aplus', 'chapter'),
                array('class' => 'entry-content'));
        
        // parse metadata
        $maxPoints = $this->getMeta('max-points');
        if ($maxPoints !== null)
            $page->meta['max_points'] = $maxPoints;
        $maxPoints = $this->getMeta('max_points'); // underscore preferred
        if ($maxPoints !== null)
            $page->meta['max_points'] = $maxPoints;
        
        $page->meta['status'] = $this->getMeta('status');
        if ($page->meta['status'] === 'accepted') {
            $page->is_accepted = true; // accepted for async grading
            $meta_wait = $this->getMeta('wait');
            if (!empty($meta_wait) || $meta_wait === '0') {
                // if the remote page has non-empty attribute value for wait, we should wait
                // PHP thinks empty("0") === true
                $page->is_wait = true;
            }
        } else if ($page->meta['status'] === 'rejected') {
            $page->is_rejected = true;
        }
        
        $page->meta['points'] = $this->getMeta('points');
        if ($page->meta['points'] !== null) {
            $page->points = (int) $page->meta['points'];
            $page->is_graded = true;
            $page->is_accepted = true;
            $page->is_wait = false;
        }
        
        $metaTitle = $this->getMeta('DC.Title');
        if ($metaTitle)
            $page->meta['title'] = $metaTitle;
        else
            $page->meta['title'] = $this->getTitle();
        
        $page->meta['description'] = $this->getMeta('DC.Description');
        
        $page->last_modified = $this->getHeader('Last-Modified');
        $page->expires = $this->getExpires();
    }
    
    /**
     * Return the value of the given HTTP response header, i.e.,
     * header returned by the server when this page was retrieved.
     * 
     * @param string $name name of the HTTP header, e.g., 'Last-Modified'
     * @return mixed|boolean the value of the header, or false if not found
     */
    public function getHeader($name) {
        $name = strtolower($name);
        if (isset($this->response_headers[$name])) {
            return $this->response_headers[$name];
        } else {
            return false;
        }
    }
    
    /**
     * Parse the value of the Expires HTTP header.
     * @param string $expires_header date string
     * @return int Unix timestamp
     */
    public static function parseExpires($expires_header) {
        if ($expires_header && ($val = strtotime($expires_header))) {
            // the Expires header exists and can be parsed
            return $val;
        }
        return 0;
    }
    
    /**
     * Return the Unix timestamp corresponding to the Expires HTTP response header.
     * @return int
     */
    public function getExpires() {
        return self::parseExpires($this->getHeader('Expires'));
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
     * Return inline JavaScript code strings of script elements in the document
     * that have the attribute data-astra-jquery. The JS code is expected to
     * use the jQuery JS library with the name given as the value of the data attribute,
     * by default "$" is assumed.
     * The page must be loaded before calling this method.
     * @return an array of arrays: one array for each found script element;
     * the nested arrays consist of two elements: inline JS code and the name for jQuery
     */
    public function getInlinejQueryScripts() {
        $js_codes = array();
        foreach ($this->astrajQueryScriptElements as $elem) {
            $attrVal = $elem->getAttribute('data-astra-jquery');
            if (!$attrVal)
                $attrVal = '$'; // default
            
            $js_codes[] = array($elem->textContent, $attrVal); // inline code and the name for jQuery
        }
        return $js_codes;
    }
    
    /**
     * Return HTML string of the contents of the element with the given id or attribute value, or
     * body if no element is found with the given id or attribute in the HTML document.
     * The contents of an element refer to its inner HTML, excluding the outer element itself.
     * 
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
                return self::DOMinnerHTML($element);
            }
        }
        
        // search for attributes
        foreach ($this->DOMdoc->getElementsByTagName('div') as $node) {
            if ($node->nodeType == \XML_ELEMENT_NODE) {
                foreach ($searchAttrs as $attrName => $attrValue) {
                    if ($node->getAttribute($attrName) == $attrValue) {
                        return self::DOMinnerHTML($node);
                    }
                }
            }
        }
        
        // resort to body since no id/attr was found
        // Note: using id is more reliable than parsing content from body
        $nodesList = $this->DOMdoc->getElementsByTagName('body');
        if ($nodesList->length == 0) {
            return null;
        }
        $element = $nodesList->item(0); // there should always be exactly one body
        return self::DOMinnerHTML($element);
    }
    
    /**
     * Return HTML of the inner contents of a DOMNode (or DOMElement).
     * The element itself is not included, only its children (and their children, etc.).
     * DOMDocument->saveHTML($element) gives the HTML including the element itself.
     * Source: http://stackoverflow.com/a/2087136
     * 
     * @param \DOMNode $element
     * @return string
     */
    public static function DOMinnerHTML(\DOMNode $element) {
        $innerHTML = "";
        $children  = $element->childNodes;
        
        foreach ($children as $child) {
            $innerHTML .= $element->ownerDocument->saveHTML($child);
        }
        
        return $innerHTML;
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
    protected function findAndReplaceElementAttributesWithMatchingKey($tagName, $attrName, array $replaceValues) {
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
     * Find elements of type $tagName that have attribute $attrName. Then,
     * replace the attributes of the element with the attribute values in $replaceValues.
     * Only the attributes given in $replaceValues are affected.
     * 
     * @param string $tagName name of the elements/tags that are searched
     * @param string $attrName attribute name that is used in the search of elements
     * @param array $replaceValues array of new attribute values, separately for each
     * element. The outer array is traversed in the same order as $tagName elements with
     * attribute $attrName are found in the document, while the corresponding inner array
     * is used to replace attribute values. The inner array has attribute names as keys and
     * attribute values as array values.
     */
    protected function findAndReplaceElementAttributes($tagName, $attrName, array $replaceValues) {
        $length = count($replaceValues);
        if ($length == 0) {
            return;
        }
        $i = 0;
        foreach ($this->DOMdoc->getElementsByTagName($tagName) as $node) {
            if ($node->nodeType == \XML_ELEMENT_NODE && $node->hasAttribute($attrName)) {
                $attrsToReplace = $replaceValues[$i];
                foreach ($attrsToReplace as $replaceAttrName => $replaceAttrValue) {
                    if (substr($replaceAttrName, 0, strlen('?')) === '?') {
                        // if the attribute name for replacing has been prefixed with a question mark,
                        // only replace the attribute if the element had the attribute previously
                        $replaceAttrName = substr($replaceAttrName, 1); // drop the first ?
                        if ($node->hasAttribute($replaceAttrName)) {
                            $node->setAttribute($replaceAttrName, $replaceAttrValue);
                        }
                    } else {
                        $node->setAttribute($replaceAttrName, $replaceAttrValue);
                    }
                }
                
                $i += 1;
                if ($i >= $length) {
                    return;
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
     * Return an array of DOMNodes that are script elements with the given attribute.
     * @param string $attrName attribute name to search for
     * @return \DOMNode[]
     */
    protected function findScriptElementsWithAttribute($attrName) {
        $elements = array();
        foreach ($this->DOMdoc->getElementsByTagName('script') as $scriptElem) {
            if ($scriptElem->hasAttribute($attrName)) {
                $elements[] = $scriptElem;
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
        if (empty($path)) {
            $path = '/';
        } else if (mb_substr($path, -1) !== '/') { // does not end with /
            // remove the last part in path, e.g., "chapter.html" in "/course/module/chapter.html"
            $path = dirname($path) . '/';
        }
        
        $tags_attrs = array(
                'img' => 'src',
                'script' => 'src',
                'iframe' => 'src',
                'link' => 'href',
                'a' => 'href',
                'video' => 'poster',
                'source' => 'src',
        );
        foreach ($tags_attrs as $tag => $attr) {
            $this->_fixRelativeUrls($domain, $path, $tag, $attr);
        }
    }
    
    /**
     * Find relative URLs in the document and make them point to the origin server.
     * @param string $domain domain of the origin server, e.g., 'https://example.com'
     * @param string $path base path to use if original URL paths are relative
     * @param string $tagName search the document for these elements, e.g., 'img'
     * @param string $attrName fix URLs in this attribute of the element, e.g., 'src'
     */
    protected function _fixRelativeUrls($domain, $path, $tagName, $attrName) {
        global $DB;
        
        $pattern = '%^(#|.+://|//)%';
        $chapter_pattern = '%(\.\./)?(?P<roundkey>[\w-]+)/(?P<chapterkey>[\w-]+)(\.html)?(?P<anchor>#.+)?$%';
        // recognize absolute URLs (https:// or //) or anchor URLs (#someid)
        foreach ($this->DOMdoc->getElementsByTagName($tagName) as $elem) {
            if ($elem->nodeType == \XML_ELEMENT_NODE && $elem->hasAttribute($attrName)) {
                $value = $elem->getAttribute($attrName);
                if (empty($value)) {
                    continue;
                }
                
                if ($elem->hasAttribute('data-aplus-chapter')) {
                    // Custom transform for RST chapter to chapter links
                    // (the link must refer to Moodle, not the exercise service)
                    $matches = array();
                    if (preg_match($chapter_pattern, $value, $matches)) {
                        // find the chapter with the remote key and the exercise round key
                        $chapter_record = $DB->get_record_sql(
                                \mod_astra_learning_object::getSubtypeJoinSQL(\mod_astra_chapter::TABLE) .
                                ' JOIN {'. \mod_astra_exercise_round::TABLE .'} round ON round.id = lob.roundid ' .
                                ' WHERE lob.remotekey = ? AND round.remotekey = ?',
                                array($matches['chapterkey'], $matches['roundkey']));
                        if ($chapter_record) {
                            $chapter = new \mod_astra_chapter($chapter_record);
                            $url = \mod_astra\urls\urls::exercise($chapter);
                            // keep the original URL anchor if it exists (#someid at the end)
                            if (isset($matches['anchor'])) {
                                $url .= $matches['anchor'];
                            }
                            // replace the URL with the Moodle URL of the chapter
                            $elem->setAttribute($attrName, $url);
                        }
                    }
                    // if the reg exp does not match, we cannot fix the URL at all
                    
                } else if (\preg_match($pattern, $value) === 0) {
                    // not absolute URL
                    
                    if ($elem->hasAttribute('data-aplus-path')) {
                        // Custom transform for RST generated exercises.
                        // add the mooc-grader course key to the URL template
                        $fix_path = str_replace('{course}', explode('/', $path)[1],
                                $elem->getAttribute('data-aplus-path'));
                        $fix_value = $value;
                        if (mb_substr($value, 0, strlen('../')) === '../') { // $value starts with ../
                            $fix_value = mb_substr($value, 2); // remote .. from the start
                        }
                        
                        $newVal = $domain . $fix_path . $fix_value;
                        
                    } else if ($value[0] == '/') { // absolute path
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