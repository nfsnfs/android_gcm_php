<?php

class Notification {

		private $apiKey; 		
		private $regIdArray; 	
		private $messageData; 	
		private $dryRun;
		private $delayWhileIdle;
		private $timeToLive;
		private $restrictedPackageName;

		/* $response is a JSON object */
		private $response;

		/* Error array('$regId' => string $error) */
		private $errors;

		/* Canonical array('$oldId' => string $newId) */
		private $canonicalIds;


		/* Notification constructor
		 * @param string $key
		 * @param array $idArray
		 * @param array $message
		 * @param bool $dry
		 * @param bool $delay
		 * @param int $ttl
		 * @param string $restricted	 
		 */
		public function __construct($key, $idArray, $message, $dry = false, $delay = true, $ttl = 0, $restricted = '') {
				$this->apiKey = $key;
				$this->regIdArray = $idArray;
				$this->messageData = $message;
				$this->dryRun = $dry;
				$this->delayWhileIdle = $delay;
				$this->timeToLive = $ttl;
				$this->restrictedPackageName = $restricted;
		}

		public function sendNotification() {
				$headers = array("Content-Type:" . "application/json", "Authorization:" . "key=" . $this->apiKey);
				$data = array(
								'data' => $this->messageData,
								'registration_ids' => $this->regIdArray,
								'dry_run' => $this->dryRun,
								'delay_while_idle' => $this->delayWhileIdle,
								'time_to_live' => $this->timeToLive,
							 );

				$ch = curl_init();

				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_URL, "https://android.googleapis.com/gcm/send");
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

				$response = curl_exec($ch);
				curl_close($ch);

				$this->response = json_decode($response);

				/* check errors and warnings */
				if(($this->response->failure != 0) || ($this->response->canonical_ids != 0)) {
						foreach($this->response->results as $i => $result) {
								if(property_exists($result, "error")) {
										printf("Error: '%s' => %s\n", $this->regIdArray[$i], $result->error);
										$this->errors[$this->regIdArray[$i]] = $result->error;
								}
								if(property_exists($result, "registration_id")) {
										printf("Warning: '%s' should be changed to '%s'\n", $this->regIdArray[$i], $result->registration_id);
										$this->canonicalIds[$this->regIdArray[$i]] = $result->registration_id;
								}
						}
				}

		}

		public function getResponse() {
				return $this->response;
		}

		public function getErrors() {
				return $this->errors;
		}

		public function getCanonicalIds() {
				return $this->canonicalIds;
		}
}

?>
