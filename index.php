<?php
	function getGUID()
	{
		if (function_exists('com_create_guid')){
			return com_create_guid();
		}
		else {
			mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
			$charid = strtoupper(md5(uniqid(rand(), true)));
			$hyphen = chr(45);// "-"
			$uuid = substr($charid, 0, 8).$hyphen
				.substr($charid, 8, 4).$hyphen
				.substr($charid,12, 4).$hyphen
				.substr($charid,16, 4).$hyphen
				.substr($charid,20,12);
			return $uuid;
		}
	}

	$userid = $_COOKIE["userid"] ?? null;
	if (!$userid)
	{
		$userid = getGUID();
		$returnCookie = setcookie("userid", $userid, time()+604800, "/", "mapshare.meng-milling.dk", 0);
	}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MapShare</title>
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <style>
		#map { height: 100vh; min-height: 100vh; margin:0px; padding:0px; }

		/*Wraperclass for the divicon*/
		.map-label {
		position: absolute;
		bottom: 0;left: -50%;
		display: flex;
		flex-direction: column;
		text-align: center;
		}
		/*Wrap the content of the divicon (text) in this class*/
		.map-label-content {
		order: 1;
		position: relative; left: -50%;
		background-color: #fff;
		border-radius: 5px;
		border-width: 2px;
		border-style: solid;
		border-color: #444;
		padding: 3px;
		white-space: nowrap;
		}
		/*Add this arrow*/
		.map-label-arrow {
		order: 2;
		width: 0px; height: 0px; left: 50%;
		border-style: solid;
		border-color: #444 transparent transparent transparent;
		border-width: 10px 6px 0 6px; /*[first number is height, second/fourth are rigth/left width]*/
		margin-left: -6px;
		}

		/*Instance classes*/
		.map-label.inactive {
		opacity: 0.5;
		}

		.map-label.redborder > .map-label-content {
		border-color: #e00;
		}
		.map-label.redborder > .map-label-arrow {
		border-top-color: #e00;
		}

		.map-label.redbackground > .map-label-content {
		white-space: default;
		color: #fff;
		background-color: #e00;
		}

	</style>
  </head>
  <body style="margin:0; padding:0">
    <div style="z-index: 1000001; position: absolute; top:2px; right:2px; cursor:pointer; background-color: #ffffcc; font-size: 24px; padding:4px;">
		<input type="button" value="Stop" onclick="stopAll()" style="font-size:24px;" />
		<input type="button" value="Rename" onclick="rename()" style="font-size:24px;" />
	</div>
    <div id="namebox" style="z-index: 1000000; padding:4px; position: absolute; margin: auto; top: 0; right: 0; bottom: 0; left: 0; width: 280px; height: 80px; font-size: 24px; background-color:#ffffcc; border:1px solid black; display:none;">
		Enter name:<br />
		<table><tr><td>
		<input type="text" id="nametext" name="nametext" style="font-size:24px; width:200px;">&nbsp;</input><input type="button" value="OK" style="font-size:20px; height:40px;" onclick="setName(nametext.value)" />
	</td><td>
		<div id="errortext" style="color: red;"></div></td></tr></table>
	</div>
	<div id="map"></div>
	<script type="text/javascript">
		var myName = "";
		var myMarker = null;
		var watchId = null;
		var userId = "<?= $userid ?>";
		const cars = new Map();
	
		var map = L.map('map').setView([55, 12], 13);
		L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'}).addTo(map);

		function stopAll()
		{
			fetch("location.php?action=delete&id="+userId)
			.then((response) => {
				return response.json();
			})
				.then((data) => {
				let response = data;
				if (response)
				{
					if (data.success)
					{
						location.reload();
					} 
					else
					{
					}
				}
			});
		}

		function rename()
		{
			namebox.style.display ="block";
		}

		function initGps() {
			if (!navigator.geolocation) {
				return;
			}
		 
			function success(position) {
				var latitude = position.coords.latitude;
				var longitude = position.coords.longitude;
				updateMarker(latitude, longitude);
			}
		 
			function error() {
				alert("Unable to retrieve your location");
			}
			
			const options = {
			  enableHighAccuracy: true,
			  maximumAge: 30000,
			  timeout: 27000
			};

			watchID = navigator.geolocation.watchPosition(success, error, options);

			setInterval(getAllCars, 2000);
		}

		function getAllCars() {
			fetch("location.php?action=list&id="+userId)
			.then((response) => {
				return response.json();
			}).then((data) => {
				let response = data;
				if (response)
				{
					if (data.length > 0)
					{
						for (var i=0; i<data.length; i++)
						{
							var car = data[i];
							var marker = cars[car.id];
							if (marker)
							{
								marker.setLatLng([car.latitude, car.longitude]).update();
							} else
							{
								var pos = new L.LatLng(car.latitude, car.longitude);
								var icon = L.divIcon({
									iconSize:null,
									html:'<div class="map-label"><div class="map-label-content">'+car.name+'</div><div class="map-label-arrow"></div></div>'
								});
								marker = L.marker(pos,{icon: icon}).addTo(map);
								cars.set(car.id, marker);
							}
						}
					} 
				}
			});
		}


		function setName(name)
		{
			fetch("location.php?action=set_name&id="+userId+"&name="+nametext.value)
			.then((response) => {
				return response.json();
			})
				.then((data) => {
				let response = data;
				if (response)
				{
					if (data.success)
					{
						location.reload();
					} 
					else
					{
						errortext.innerText=data.message;
					}
				}
			});
		}

		// Check if user with specified id already exists and get the name
		function getName()
		{
			fetch("location.php?action=get_name&id="+userId)
			.then((response) => {
				return response.json();
			})
				.then((data) => {
				let response = data;
				if (response && data.name)
				{
					myName = data.name;
					initGps();
				} else
				{
					namebox.style.display ="block";
				}
			});
		}
		
		function updateMarker(latitude, longitude) {
			map.setView(new L.LatLng(latitude, longitude), map.getZoom(), { animation: true });   
			if (!myMarker)
			{
				var pos = new L.LatLng(latitude,longitude);
				var icon = L.divIcon({
					iconSize:null,
					html:'<div class="map-label redborder"><div class="map-label-content">'+myName+'</div><div class="map-label-arrow"></div></div>'
				});

				myMarker = L.marker(pos,{icon: icon}).addTo(map);
			} else
			{
				myMarker.setLatLng([latitude, longitude]).update();
			}
			
			fetch("location.php?action=update_position&id="+userId+"&latitude="+latitude+"&longitude="+longitude)
			.then((response) => {
				return response.json();
			})
				.then((data) => {
				let response = data;
				console.log("message:" + response.message);
				console.log("status:" + response.success);
			})
		}
		
		getName();
	</script>
	<?php
	
	?>
  </body>
</html>