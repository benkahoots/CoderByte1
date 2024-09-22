<?php

    // Declaration of HTTP Request Type Template Interface
    interface HttpType {
        const GET       = 'GET';
        const PUT       = 'PUT';
        const POST      = 'POST';
        const PATCH     = 'PATCH';
        const DELETE    = 'DELETE';
        const OPTIONS   = 'OPTIONS';
    }

    // Demo Constant URL used for our requests
    const URL = 'https://corednacom.corewebdna.com/assessment-endpoint.php';

    /**
     * Represents an HTTP response.
     */
    class HttpResponse {
        /**
         * The HTTP status code.
         *
         * @var int
         */
        private $statusCode;

        /**
         * The response payload.
         *
         * @var string
         */
        private $payload;

        /**
         * The HTTP headers.
         *
         * @var array
         */
        private $headers;

        /**
         * Constructs a new HttpResponse object.
         *
         * @param int $statusCode The HTTP status code.
         * @param string $payload The response payload.
         * @param array $headers The HTTP headers.
         */
        function __construct(int $statusCode_, string $payload_, array $headers_) {
            $this->statusCode = $statusCode_;
            $this->payload = $payload_;
            $this->headers = $headers_;
        }

        /**
         * Gets the HTTP status code.
         *
         * @return int The HTTP status code.
         */
        public function getStatusCode(): int {
            return $this->statusCode;
        }

        /**
         * Gets the response payload.
         *
         * @return string The response payload.
         */
        public function getPayload(): string {
            return $this->payload;
        }

        /**
         * Gets the HTTP headers.
         *
         * @return array The HTTP headers.
         */
        public function getHeaders(): array {
            return $this->headers;
        }
    }

    /**
     * A class for making HTTP requests.
     */
    class HttpClient {

        /**
         * Sends an HTTP request.
         *
         * @param string $url The URL to send the request to.
         * @param array $payload The request payload.
         * @param string $method The HTTP method to use.
         * @param array $headers The HTTP headers to include.
         *
         * @return HttpResponse The HTTP response.
         *
         * @throws Exception If the request fails.
         */
        function sendHttpRequest(string $url, array $payload = [], string $method, array $headers = []): HttpResponse {

            // Get Bearer Authentication Token
            $authResponse = $this->sendAuthRequest(URL);
            $bearerToken = $authResponse->getPayload();
            $headers['Authorization'] = "Bearer $bearerToken";

            // Build Context for Request
            $options = [
                'http' => [
                    'method' => $method,
                    'header'=>  $this->buildHeaders($headers),
                ]
            ];

            // Try Encode Payload if it exists
            if(count($payload) !== 0) {
                try {
                    $options['http']['content'] = json_encode($payload);
                } catch (Exception $e) {
                    throw new Exception("Failed to encode payload for Http Request.");
                }
            }

            // Execute request
            $context = stream_context_create($options);
            $responseData = file_get_contents($url, false, $context);

            // Handle Failed Response
            if ($responseData === false) {
                throw new Exception("Http Request Failed to Send: $responseData");
            }

            // Check Status code to not be between 4XX and 5XX
            $this->evaluateResponseCodes($http_response_header);

            // Return Response Object
            return new HttpResponse( ($this->retrieveStatusCode($http_response_header)), $responseData, ($this->decodeResponseHeaders($http_response_header)) );
        }


        /**
         * Sends an authentication request.
         *
         * @param string $url The URL to send the request to.
         *
         * @return HttpResponse The HTTP response.
         *
         * @throws Exception If the request fails.
         */
        function sendAuthRequest(string $url): HttpResponse {

            // Pre-defined headers for bearer request
            $headers = array(
                "Content-Type" => "application/json",
                "Accept" => "application/json"
            );
            
            // Build Options for Request
            $options = [
                'http' => [
                    'method' => HttpType::OPTIONS,
                    'header'=>  $this->buildHeaders($headers),
                ]
            ];

            // Execute Request
            $context =  stream_context_create($options);
            $responseData = file_get_contents($url, false, $context);  


            // Handle Failed Response
            if ($responseData === false) {
                throw new Exception("Http Request Failed to Send: $reponseData");
            }

            // Check Status code to not be between 4XX and 5XX
            $this->evaluateResponseCodes($http_response_header);

            // Return Response Object
            return new HttpResponse( ($this->retrieveStatusCode($http_response_header)), $responseData, ($this->decodeResponseHeaders($http_response_header)) );
        }

        
        /**
         * Evaluates the response code.
         *
         * @param array $http_response_header The HTTP response headers.
         *
         * @throws Exception If the response code is not OK.
         */
        private function evaluateResponseCodes(array $http_response_header) {
            $statusCode = $this->retrieveStatusCode($http_response_header);

            // Throw Exception at any Non 'OK' Response Code
            if ($statusCode >= 400 && $statusCode < 600) {
                throw new Exception("Error Status Code - $statusCode." );
            }
        }

        /**
         * Retrieves the status code from the HTTP response headers.
         *
         * @param array $http_response_header The HTTP response headers.
         *
         * @return int The status code.
         */
        private function retrieveStatusCode(array $http_response_header): int {
            // Throw an error if not valid array data
            return ((int) (explode(' ', $http_response_header[0])[1]));
        }

        /**
         * Decodes the HTTP response headers.
         *
         * @param array $http_response_header The HTTP response headers.
         *
         * @return array The decoded HTTP response headers.
         */
        private function decodeResponseHeaders($http_response_header): array {
            $headers = array();
            foreach ($http_response_header as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $headers[trim($parts[0])] = trim($parts[1]);
                }
            }
            return $headers;
        }

        /**
         * Builds the HTTP headers.
         *
         * @param array $headers The HTTP headers.
         *
         * @return string The HTTP headers as a string.
         */
        private function buildHeaders(array $headers): string {
            $headerLines = [];
            foreach ($headers as $name => $value) {
                $headerLines[] = "$name: $value";
            }
            return implode("\r\n", $headerLines);
        }

    }


    
    $httpClient = new HttpClient();
    $sample_payload = array(
        'name' => 'Ben Tegoni',
        'email' => 'tegoni.ben@gmail.com',
        'url' => 'https://github.com/benkahoots/CoderByte1'
    );
    $headers = array(
        "Content-Type" => "application/json",
        "Accept" => "application/json"
    );

    /*
        Demo Usage
    */
    $response = $httpClient->sendHttpRequest(URL, $sample_payload, HttpType::POST, $headers);
    
    echo 'Status Code: ' . $response->getStatusCode(), "</br></br>" ;
    echo 'Payload: ' . json_encode($response->getPayload()), "</br></br>" ; // Only JSON Encoding here due to requirements in Part 1 being returned as Associative Arrays
    echo 'Headers: ' . json_encode($response->getHeaders()), "</br></br>" ; // Only JSON Encoding here due to requirements in Part 1 being returned as Associative Arrays
?>