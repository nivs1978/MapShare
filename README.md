# MapShare
Simple demonstration of PHP site with Leaflet map where you can share your location with others.
This is a work in progress. The code is not complete and may contain bugs. 

##Database
Create a single table "cars":
CREATE TABLE `cars` (
  `id` varchar(36) NOT NULL,
  `name` varchar(20) NOT NULL,
  `latitude` double NOT NULL,
  `longitude` double NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY (`id`)
)