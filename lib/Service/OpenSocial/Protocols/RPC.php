<?php
/**
 * Service.OpenSocial.Protocols.RPC
 * 
 * @package Service\OpenSocial\Protocols\RPC
 */
Core::load('Service.OpenSocial');

/**
 * @package Service\OpenSocial\Protocols\RPC
 */
class Service_OpenSocial_Protocols_RPC implements Service_OpenSocial_ModuleInterface {


/**
 * @param Service_OpenSocial_AuthAdapter $auith
 * @param Service_OpenSocial_Format $format
 * @param Net_Agents_HTTP_Agent $agent
 * @return Service_OpenSocial_Protocols_RPC_Protocol
 */
  static public function Protocol(Service_OpenSocial_AuthAdapter $auth, Service_OpenSocial_Format $format, Net_Agents_HTTP_Agent $agent) {
    return new Service_OpenSocial_Protocols_RPC_Protocol($auth, $format, $agent);
  }


}

/**
 * @package Service\OpenSocial\Protocols\RPC
 */
class Service_OpenSocial_Protocols_RPC_Protocol extends Service_OpenSocial_Protocol {


/**
 * @param Service_OpenSocial_Container $container
 * @param array $requests
 * @return Service_OpenSocial_ResultSet
 */
  public function send(Service_OpenSocial_Container $container, array $requests) {
    if (!$container->rpc_endpoint)
      throw new Service_OpenSocial_UnsupportedProtocolException('RPC', $container);

    $results  = new Service_OpenSocial_ResultSet();
    $requests = $this->normalize($requests);

    $response = $this->agent->send($this->make_http_request($requests, $container));

    if ($response->status->is_success)
      foreach ($this->format->decode($response->body) as $item)
        $results[$item->id] = $item->error ?
          new Service_OpenSocial_Error($item->error->code, $item->error->message) :
          $this->make_result($requests[$item->id]->service, $item->data);
    else {
      $error = Service_OpenSocial_Error::from_http_response($response);
      foreach (array_keys($requests) as $id) $results[$id] = $error;
    }
    return $results;
  }



/**
 * Строит OpenSocial.Result или OpenSocial.Collection
 * 
 * @param OpenSocial_Serivce $service
 * @param Net_HTTP_Response $response
 * @return Service_OpenSocial_ResultInterface
 */
  protected function make_result(Service_OpenSocial_Service $service, $data) {
    if (isset($data->list) && is_array($data->list)) {
      $result = new Service_OpenSocial_Collection(
        isset($data->totalResults) ? $data->totalResults : 0,
        isset($data->itemsPerPage) ? $data->itemsPerPage : 0,
        isset($data->startIndex)   ? $data->startIndex   : 0);

      foreach ($data->list as $item)
        $result->append($service->make_resource_for($item));

    } else {
      $result = $service->make_resource_for($data);
    }

    return $result;
  }

/**
 * @param array $requests
 * @return array
 */
  protected function normalize(array $requests) {
    $res = array();
    foreach($requests as $k => $r) $res[$r->id ? $r->id : $k] = $r;
    return $res;
  }

/**
 * @param array $requests
 * @param Service_OpenSocial_Container $container
 * @return Net_HTTP_Request
 */
  protected function make_http_request(array $requests, Service_OpenSocial_Container $container) {
    $r = Net_HTTP::Request()->
      uri($container->rpc_endpoint)->
      method(Net_HTTP::POST)->
      content_type($this->format->content_type)->
      query_parameters(array('format' => $this->format->name))->
      body($this->format->encode($this->make_http_request_body($requests)));

    if ($container->use_method_override) $r->header('X-HTTP-Method-Override', 'POST');

    return $this->auth->authorize_request($r, $container);
  }

/**
 * @param array $requests
 * @return array
 */
  protected function make_http_request_body(array $requests) {
    $res = array();
    foreach ($requests as $r)
      $res[] = (object) array_filter(
        array('method' => $r->service.'.'.$r->operation,
              'id' => $r->id,
              'params' =>
                array_filter(
                  array('userId' => $r->user_id, 'groupId' => $r->group_id, 'appId' => $r->app_id, 'entityId' => $r->resource_id) +
                  $r->params +
                  ($r->has_resource ? array($r->service->rpc_name => $r->resource->fields) : array()))));
    return $res;
  }

}

