<?PHP
$data = $_GET;
// $data["created"] = false;
$data["created"] = true;
echo json_encode($data);
?>