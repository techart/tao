<?php
/// <module name="Service.OpenSocial.Protocols.RPC">
Core::load('Service.OpenSocial');

/// <class name="Service.OpenSocial.Protocols.RPC" stereotype="module">
///   <implements interface="Service.OpenSocial.ModuleInterface" />
///   <depends supplier="Service.OpenSocial.Protocols.RPC.Protocol" stereotype="creates" />
class Service_OpenSocial_Protocols_RPC implements Service_OpenSocial_ModuleInterface {

///   <protocol name="creating">

///   <method name="Protocol" returns="Service.OpenSocial.Protocols.RPC.Protocol" scope="class">
///     <args>
///       <arg name="auith" type="Service.OpenSocial.AuthAdapter" />
///       <arg name="format" type="Service.OpenSocial.Format" />
///       <arg name="agent"  type="Net.Agents.HTTP.Agent" />
///     </args>
///     <body>
  static public function Protocol(Service_OpenSocial_AuthAdapter $auth, Service_OpenSocial_Format $format, Net_Agents_HTTP_Agent $agent) {
    return new Service_OpenSocial_Protocols_RPC_Protocol($auth, $format, $agent);
  }
///     </body>
///   </method>

///   </protocol>

}
/// </class>

/// <class name="Service.OpenSocial.Protocols.RPC.Protocol" extends="Service.OpenSocial.Protocol">
class Service_OpenSocial_Protocols_RPC_Protocol extends Service_OpenSocial_Protocol {

///   <protocol name="performing">

///   <method name="send" returns="Service.OpenSocial.ResultSet">
///     <args>
///       <arg name="container" type="Service.OpenSocial.Container" />
///       <arg name="requests" type="array" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>

///   <protocol name="supporting">

///   <method name="result_for" returns="Service.OpenSocial.ResultInterface" access="protected">
///     <brief>Строит OpenSocial.Result или OpenSocial.Collection</brief>
///     <args>
///       <arg name="service"  type="OpenSocial.Serivce" />
///       <arg name="response" type="Net.HTTP.Response" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="normalize" returns="array">
///     <args>
///       <arg name="requests" type="array" />
///     </args>
///     <body>
  protected function normalize(array $requests) {
    $res = array();
    foreach($requests as $k => $r) $res[$r->id ? $r->id : $k] = $r;
    return $res;
  }
///     </body>
///   </method>

///   <method name="make_http_request" returns="Net.HTTP.Request">
///     <args>
///       <arg name="requests" type="array"  />
///       <arg name="container" type="Service.OpenSocial.Container"  />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   <method name="make_http_request_body" returns="array">
///     <args>
///       <arg name="requests" type="array" brief="массив запросов" />
///     </args>
///     <body>
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
///     </body>
///   </method>

///   </protocol>
}
/// </class>

/// </module>
