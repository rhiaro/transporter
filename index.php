<?
session_start();
require 'vendor/autoload.php';
$ns = rhiaro\ERH\ns();

$tz = rhiaro\ERH\get_timezone_from_rdf("https://rhiaro.co.uk/tz");
date_default_timezone_set($tz);

$albums = Rhiaro\get_albums("http://localhost/albums?limit=100");

if(isset($_GET['listing'])){
    $listing = Rhiaro\get_photos();
}

if(isset($_POST['transported'])){
    if(isset($_POST['endpoint_key'])){
        $_SESSION['key'] = $_POST['endpoint_key'];
    }
    $endpoint = $_POST['endpoint_uri'];
    $result = Rhiaro\form_to_endpoint($_POST);

    if(is_array($result)){
        $errors = $result;
        unset($result);
    }else{
        unset($_POST);
    }
}
include('templates/index.php');
?>