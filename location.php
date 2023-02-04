<?php
header("Content-Type: application/json");
$dbname = '<db_name>';
$dbuser = '<db_user>';
$dbpass = '<db_password>';
$dbhost = '<db_host>';
$dbhandle = mysqli_connect($dbhost, $dbuser, $dbpass) or die("Unable to connect to '$dbhost'");
$selected = mysqli_select_db($dbhandle, $dbname) or die("Could not open the database '$dbname'");
$action = $_GET["action"] ?? null;
$json_response = null;
$id = $_GET["id"] ?? null;

function checkGuid($guid)
{
    return boolval(preg_match("/^(\{)?[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}(?(1)\})$/i", $guid));
}

function carExists($db, $id)
{
    $result = mysqli_query($db, "SELECT id FROM cars where id = '{$id}';");
    return mysqli_num_rows($result) > 0;
}

if (!$id)
{
    $json_response = (object)  ['message' => 'User ID missing', 'success' => false];
} else if (!checkGuid($id))
{
    $json_response = (object)  ['message' => 'Not a valid guid', 'success' => false];
} else
{
    if ($action == "delete")
    {
        $sql = "delete from cars where id='{$id}'";
        if (mysqli_query($dbhandle, $sql) === TRUE) {
            $json_response = (object)  ['message' => 'Deleted', 'success' => true];
        } else {
            $json_response = (object)  ['message' => 'Unable to delete', 'success' => false];
        }
    }
    else if ($action == "get_name")
    {
        $result = mysqli_query($dbhandle, "SELECT name FROM cars where id = '{$id}'");
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($row)
        {
            $json_response = (object)  ['name' => $row['name']];
        }
        else
        {
            $json_response = (object)  ['name' => null];
        }
    }
    else if ($action == "set_name")
    {
        $name = $_GET["name"] ?? null;
        if ($name)
        {
            $name_fixed = preg_replace('/[^\p{L}\p{N} ]+/', '', $name);
            $sql = "";
            if (carExists($dbhandle, $id))
            {
                $sql = "update cars set name='{$name_fixed}' where id='{$id}';";
            }
            else
            {
                $sql = "INSERT INTO cars (id, name, latitude, longitude, timestamp) VALUES ('".$id."','".$name_fixed."',0.0, 0.0, UTC_TIMESTAMP())";
            }
            if (mysqli_query($dbhandle, $sql) === TRUE) {
                $json_response = (object)  ['message' => 'Name set', 'success' => true];
            } else {
                $json_response = (object)  ['message' => 'Error setting name', 'success' => false];
            }
    } else
        {
            $json_response = (object)  ['message' => 'Name missing', 'success' => false];
        }
    }
    else if ($action == "list")
    {
        $json_response = array();
        $result = mysqli_query($dbhandle, "SELECT * FROM cars where id <> '{$id}' ");
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $row_array['name'] = $row['name'];
            $row_array['latitude'] = $row['latitude'];
            $row_array['longitude'] = $row['longitude'];
            $row_array['timestamp'] = $row['timestamp'];
            array_push($json_response, $row_array);
        }
    } 
    
    else if ($action == "update_position")
    {
        $latitude = $_GET["latitude"] ?? null;
        $longitude = $_GET["longitude"] ?? null;
        if (!$latitude)
        {
            $json_response = (object)  ['message' => 'Latitude missing', 'success' => false];
        } else if (!$longitude)
        {
            $json_response = (object)  ['message' => 'Longitude missing', 'success' => false];
        } else
        {
            $latitude = doubleval($latitude);
            $longitude = doubleval($longitude);
            if (carExists($dbhandle, $id))
            {
                $sql = "update cars set latitude={$latitude}, longitude={$longitude} where id='{$id}';";
                if (mysqli_query($dbhandle, $sql) === TRUE) {
                    $json_response = (object)  ['message' => 'Position updated','success' => true];
                } else {
                    $json_response = (object)  ['message' => 'Unable to update position','success' => false];
                }
            } else {
                $json_response = (object)  ['message' => 'Car not found in database!','success' => false];
            }
        }
    } else{
        $json_response = (object)  ['message' => 'Unknown action provided', 'success' => false];
    }
}
echo json_encode($json_response);
?>