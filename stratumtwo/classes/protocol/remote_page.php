<?php
namespace mod_stratumtwo\protocol;

defined('MOODLE_INTERNAL') || die;

class remote_page {
    
    protected $response; // string
    protected $DOMdoc; // \DOMDocument instance
    
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
    
    public function parsePageContent() {
        $page = new \stdClass();
        $page->content = $this->getElementOrBody('exercise');
        return $page;
    }
    
    /**
     * Return HTML string of the element with the given id, or body if no id is given.
     * @param string|null $id ID value of the HTML element that should be returned,
     * null for body
     * @return NULL|string HTML string, null if the given id does not exist
     */
    public function getElementOrBody($id = null) {
        if (is_null($id)) {
            $nodesList = $this->DOMdoc->getElementsByTagName('body');
            $element = $nodesList->item(0); // there should always be exactly one body
        } else {
            $element = $this->DOMdoc->getElementById($id);
        }
        if (is_null($element))
            return null;
        return $this->DOMdoc->saveHTML($element);
    }
}