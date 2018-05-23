<?php
require_once('util.php');
require_once('keys.php');
require_once 'jwt/src/BeforeValidException.php';
require_once 'jwt/src/ExpiredException.php';
require_once 'jwt/src/SignatureInvalidException.php';
require_once 'jwt/src/JWT.php';
require_once 'jwt/src/JWK.php';

use \Firebase\JWT\JWT;
use \Firebase\JWT\JWK;

session_start();

if (empty($_REQUEST)) {
    die_with("Launch must be a POST");
}

$raw_jwt = $_REQUEST['jwt'] ?: $_REQUEST['id_token'];

if (empty($raw_jwt)) {
    die_with("JWT not found");
}

$jwt_parts = explode('.', $raw_jwt);
    //echo json_encode(json_decode(base64_decode($jwt_parts[0]), true), JSON_PRETTY_PRINT);

$jwt_body = json_decode(base64_decode($jwt_parts[1]), true);
$jwt_head = json_decode(base64_decode($jwt_parts[0]), true);
//echo json_encode($jwt_body, JSON_PRETTY_PRINT);

//$jwt = JWT::encode($jwt_body, $privateKey, 'RS256');

// echo "\n\n";
// echo $jwt;
// echo "\n\n";

$aud = is_array($jwt_body['aud']) ? $jwt_body['aud'][0] : $jwt_body['aud'];

// find key to check signature
$public_key_url = $_SESSION['public_keys'][$jwt_body['iss'].':'.$aud];

if (empty($public_key_url)) {
    // Not yet encountered this auth
    include('register.php');
    die;
}


$public_key_set_json = file_get_contents($public_key_url);

$public_key_set = json_decode($public_key_set_json, true);


$public_key;
foreach ($public_key_set['keys'] as $key) {
    if ($key['kid'] == $jwt_head['kid']) {
        $public_key = openssl_pkey_get_details(JWK::parseKey($key));
        break;
    }
}

if (empty($public_key)) {
    echo "Failed to find KID: " . $jwt_head['kid'] . " in keyset from " . $public_key_url;
    die;
}


try {
    $decoded = JWT::decode($raw_jwt, $public_key['key'], array('RS256'));
} catch(Exception $e) {
    var_dump($e);
    die;
}

$decoded_array = json_decode(json_encode($decoded), true);

//echo "Success! Welcome " . $decoded_array['given_name'] . " you are accessing resource " . $decoded_array['http://imsglobal.org/lti/resource_link']['id'];

//echo json_encode($decoded, JSON_PRETTY_PRINT);

?>

<canvas id="breakout" width="800" height="500" style="border:1px solid #000000;">
</canvas>

<script>
    client_id = "<?= $aud; ?>";
    auth_url = "<?= $_SESSION['auth_token_urls'][$decoded_array['iss'].':'.$aud]; ?>";
</script>

<script type="text/javascript" src="js/breakout.js" charset="utf-8"></script>