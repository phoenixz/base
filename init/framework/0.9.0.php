<?php
sql_function_exists('distance', 'DROP FUNCTION distance');

/*
 * This stored procedure was kindly provided by http://derickrethans.nl/spatial-indexes-mysql.html
 *
 * Basically it will return the distance for target lat / long from the specified source lat / long
 */
sql_query('CREATE FUNCTION DISTANCE (latA DOUBLE, lonA DOUBLE, latB DOUBLE, LonB DOUBLE)
               RETURNS DOUBLE DETERMINISTIC
           BEGIN
               SET @RlatA = radians(latA);
               SET @RlonA = radians(lonA);
               SET @RlatB = radians(latB);
               SET @RlonB = radians(LonB);
               SET @deltaLat = @RlatA - @RlatB;
               SET @deltaLon = @RlonA - @RlonB;
               SET @d = SIN(@deltaLat / 2) * SIN(@deltaLat / 2) +
                   COS(@RlatA) * COS(@RlatB) * SIN(@deltaLon / 2) * SIN(@deltaLon / 2);
               RETURN 2 * ASIN(SQRT(@d)) * 6371.01;
           END');
?>
