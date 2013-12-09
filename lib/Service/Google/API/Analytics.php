<?php
/**
 * @package Service\Google\API\Analytics
 */


class Service_Google_API_Analytics extends Service_Google_API_AbstractService implements Core_ModuleInterface {

	const VERSION = '0.1.1';

	protected $client_class_name = 'Service_Google_API_Analytics_Client';
	protected $cache_subfolder = 'analytics';
	
}

class Service_Google_API_Analytics_Client extends Service_Google_API_AbstractClient {

	protected $scope = 'https://www.googleapis.com/auth/analytics';

	function get_firstprofile_id() {
		$accounts = $this->service->management_accounts->listManagementAccounts();

		if (count($accounts->getItems()) > 0) {
			$items = $accounts->getItems();
			$firstAccountId = $items[0]->getId();
	
			$webproperties = $this->service->management_webproperties
				->listManagementWebproperties($firstAccountId);
	
			if (count($webproperties->getItems()) > 0) {
				$items = $webproperties->getItems();
				$firstWebpropertyId = $items[0]->getId();
	
				$profiles = $this->service->management_profiles
					->listManagementProfiles($firstAccountId, $firstWebpropertyId);
	
				if (count($profiles->getItems()) > 0) {
					$items = $profiles->getItems();
					return $items[0]->getId();
	
				} else {
					throw new Service_Google_API_Analytics_NoProfilesFoundForUserException();
				}
			} else {
				throw new Service_Google_API_Analytics_NoWebpropertiesFoundForUserException();
			}
		} else {
			throw new Service_Google_API_Analytics_NoAccountsFoundForUserException();
		}
	}

	function get_data($parms, $profile_id = null) {
		try {
			if (is_null($profile_id)) {
				$profile_id = $this->get_firstprofile_id();
			}
			if (isset($profile_id)) {
				$id = 'ga:' . $profile_id;
				$start_date = $parms['start_date'];
				$end_date = $parms['end_date'];
				$metrics = $parms['metrics'];
				$optParams = null;
				if (isset($parms['optParams'])) {
					$optParams = $parms['optParams'];
				}
 				//$metrics = 'ga:visits,ga:pageviewsPerVisit,ga:avgTimeOnSite,ga:percentNewVisits,ga:visitBounceRate';
 				//$optParams = array('dimensions' => 'ga:source, ga:campaign', 'filters' => 'ga:source==yandexdirect;ga:campaign==apple_ipod', 'segment' => 'gaid::-8', 'sort' => 'ga:visits', 'max-results' => 5, 'start-index' => 1);
				if ($optParams) {
					return $results = $this->service->data_ga->get($id, $start_date, $end_date, $metrics, $optParams);
				}
				else {
					return $results = $this->service->data_ga->get($id, $start_date, $end_date, $metrics);
				}
			}
		}
		catch (apiServiceException $e) {
			print 'There was an API error : ' . $e->getCode() . ' : ' . $e->getMessage();
		} 
		catch (Exception $e) {
			print 'There was a general error : ' . $e->getMessage();
		}
	}

	function page_views_per_visit($parms) {
		$result = null;
		$parms['metrics'] = 'ga:pageviewsPerVisit';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

	function avg_time_on_site($parms) {
		$result = null;
		$parms['metrics'] = 'ga:avgTimeOnSite';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

	function bounces_rate($parms) {
		$result = null;
		$parms['metrics'] = 'ga:bounces, ga:visits';
		$data = $this->get_data($parms);

		if ($data->rows) {
			$result = $data->rows[0][0]/$data->rows[0][1];
		}
		return $result;
	}
	
	function transactions($parms) {
		$result = null;
		$parms['metrics'] = 'ga:transactions';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

	function transactions_revenue($parms) {
		$result = null;
		$parms['metrics'] = 'ga:transactionRevenue';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

	function item_quantity($parms) {
		$result = null;
		$parms['metrics'] = 'ga:itemQuantity';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

	function ctr($parms) {
		$result = null;
		$parms['metrics'] = 'ga:CTR';
		$data = $this->get_data($parms);
		if ($data->rows) {
			$result = $data->rows[0][0];
		}
		return $result;
	}

}



class Service_Google_API_Analytics_NoProfilesFoundForUserException extends Core_Exception {

  public function __construct() {
    parent::__construct("No profiles found for this user.\n");
  }
}

class Service_Google_API_Analytics_NoWebpropertiesFoundForUserException extends Core_Exception {

  public function __construct() {
    parent::__construct("No webproperties found for this user.\n");
  }
}

class Service_Google_API_Analytics_NoAccountsFoundForUserException extends Core_Exception {

  public function __construct() {
    parent::__construct("No accounts found for this user.\n");
  }
}