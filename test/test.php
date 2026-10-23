<?php
/**
 * Cliente PHP cURL autogenerado para consumir la API
 * Endpoint: http://localhost/myflow/api/v1/ordenes-produccion/index.php
 */

require_once '../data/Data.php';
require_once '../client/ApiClientPHP.php';

$data = new Data();
$api = new ApiClientPHP();
$response = $api->create($data->getData());
