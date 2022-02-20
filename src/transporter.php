<?
namespace Rhiaro;

use EasyRdf_Graph;
use EasyRdf_Resource;
use EasyRdf_Namespace;
use EasyRdf_Literal;
use ML\JsonLD\JsonLD;
use Requests;

// Setting up

function get_albums($url){
    $response = Requests::get($url, array('Accept' => 'application/ld+json'));
    $g = new EasyRdf_Graph($url);
    $g->parse($response->body, 'jsonld');

    if(isset($_SESSION['albums'])){
        unset($_SESSION['albums']);
    }

    $resources = $g->resources();
    foreach($resources as $uri => $resource){
        if($resource->isA('asext:Album')){
            $name = $g->get($uri, 'as:name')->getValue();
            $_SESSION['albums'][$uri] = $name;
        }
    }
    $_SESSION['albums'] = array_reverse($_SESSION['albums']);
    return $_SESSION['albums'];
}

function get_photos(){

    if(!isset($_SESSION['listing']) && isset($_GET['listing'])){
        $g = fetch_listing($_GET['listing']);
        // $listing = $g->resource($_GET['listing']);
        $items = $g->all($_GET['listing'], "as:items");
        $photos = array();
        foreach($items as $item){
            $photos[] = $item->getUri();
        }
        $_SESSION['listing'] = $photos;
        rsort($_SESSION['listing']);
    }

    return $_SESSION['listing'];
}

function fetch_listing($url){
    $response = Requests::get($url, array('Accept' => 'application/ld+json'));
    $g = new EasyRdf_Graph($url);
    $g->parse($response->body, 'jsonld');
    return $g;
}

// Form input processing

function make_tags($input_array){
    $base = "https://rhiaro.co.uk/tags/";
    $tags_string = $input_array["string"];
    unset($input_array["string"]);
    $tags = explode(",", $tags_string);
    foreach($tags as $tag){
        if(strlen(trim($tag)) > 0){
            $input_array[] = $base.urlencode(trim($tag));
        }
    }
    return $input_array;
}

function make_date($date_parts){
    $date_str = make_date_string($date_parts);
    $date = new EasyRdf_Literal($date_str, null, "xsd:dateTime");
    return $date;
}

function make_date_string($date_parts){
    $date_str = $date_parts["year"]."-".$date_parts["month"]."-".$date_parts["day"]."T".$date_parts["time"].$date_parts["zone"];
    return $date_str;
}

function make_payload($form_request){
    global $ns;
    $g = new EasyRdf_Graph();
    $context = $ns->get("as");
    $options = array("compactArrays" => true);

    $endpoint = $form_request["endpoint_uri"];
    $key = $form_request["endpoint_key"];

    $published_date_parts = [
        "year" => $form_request["year"],
        "month" => $form_request["month"],
        "day" => $form_request["day"],
        "time" => $form_request["time"],
        "zone" => $form_request["zone"],
    ];
    $published_date = make_date($published_date_parts);

    $tags = make_tags($form_request["tags"]);
    $wordCount = trim($form_request["wordCount"]);
    $object = trim($form_request["object"]);
    $name = $form_request["name"];
    $summary = "Wrote $wordCount words of $name.";

    $errors = array();
    if(empty($wordCount) || $wordCount == "" || $wordCount == " "){
        $errors["wordCount"] = "empty value not allowed";
    }
    if(!is_numeric($wordCount)){
        $errors["wordCount"] = "must be a number, silly";
    }

    if(empty($errors)){
        $node = $g->newBNode();
        $g->addType($node, "as:Activity");
        $g->addType($node, "asext:Write");
        $g->addLiteral($node, "as:published", $published_date);
        $g->addLiteral($node, "asext:wordCount", $wordCount);
        $g->addLiteral($node, "as:name", $name);
        $g->addLiteral($node, "as:summary", $summary);
        $g->addResource($node, "as:object", $object);
        $g->addResource($node, "as:generator", "https://apps.rhiaro.co.uk/lore");
        foreach($tags as $tag){
            $g->addResource($node, "as:tag", $tag);
        }

        echo $g->dump();
        $jsonld = $g->serialise("jsonld");
        $compacted = JsonLD::compact($jsonld, $context, $options);
        return JsonLD::toString($compacted, true);

    }else{
        return $errors;
    }
}

// Posting

function form_to_endpoint($form_request){
    $endpoint = $form_request["endpoint_uri"];
    $key = $form_request["endpoint_key"];
    $payload = make_payload($form_request);
    if(is_array($payload)){
        // Errors
        return array("errno" => count($payload), "errors" => $payload);
    }else{
        $response = post_to_endpoint($endpoint, $key, $payload);
        if($response->status_code == "201"){
            return $response;
        }else{
            return array("errno" => 1, "errors" => array("status_code" => $response->status_code, "raw" => htmlentities($response->raw)));
        }
    }
}

function post_to_endpoint($endpoint, $key, $payload){
    $headers = array("Content-Type" => "application/ld+json", "Authorization" => $key);
    $response = Requests::post($endpoint, $headers, $payload);
    return $response;
}

?>