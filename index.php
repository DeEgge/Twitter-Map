<?php 

  require "credentials.php";
  require "twitteroauth/autoload.php";

  use Abraham\TwitterOAuth\TwitterOAuth;

  $connection = new TwitterOAuth($consumer_key, $consumer_secret, $access_token, $access_token_secret);
  $content = $connection->get("account/verify_credentials");

  $query = "New York"; 
  $tweetList = "";
  $message = "";

  if ($_GET) {

    if (!empty($_GET['user'])) {

      $user = $_GET['user'];

      $tweets = $connection->get("statuses/user_timeline", [
              "count" => 50, 
              "exclude_replies" => true,
              "screen_name" => $user
            ]);

    } else if (!empty($_GET['query'])) {

      $query = $_GET['query'];

      $tweets = $connection->get("search/tweets", [
              "q" => $query,
              "count" => 50, 
              "result_type" => "recent"
            ]);
      $tweets = $tweets->statuses;

    } else {

      $tweets = $connection->get("search/tweets", [
              "q" => $query,
              "count" => 50, 
              "result_type" => "recent"
            ]);
      $tweets = $tweets->statuses;
    }

  } else {

    $tweets = $connection->get("search/tweets", [
              "q" => $query,
              "count" => 50, 
              "result_type" => "recent",
              "geocode" => "40.758896,-73.985130,15mi"
            ]);
    $tweets = $tweets->statuses;

  }

  if (!isset($tweets->errors) && !isset($tweets->error)) {

    //create geojson object as 'var tweets_geojson'
    $geojsonObjectStart = 'var tweets_geojson = {"type": "FeatureCollection", "features": [';
    $geojsonObjectMiddle = "";
    foreach ($tweets as $tweet) {
      if (isset($tweet->geo)) {
        $geojsonObjectMiddle .= '{"type": "Feature", "geometry": {"type": "Point", "coordinates": ['.$tweet->geo->coordinates[1].','.$tweet->geo->coordinates[0].']}, "properties": {"id":"'.$tweet->id_str.'","text":"'.addslashes($tweet->text).'"}},';
      }
    }
    $geojsonObjectEnd = ']}';
    $geojsonObjectComplete = $geojsonObjectStart.$geojsonObjectMiddle.$geojsonObjectEnd;

    foreach ($tweets as $tweet) {
      if (isset($tweet->geo)) {
        $tweetItem = $connection->get("statuses/oembed", [
          "id" => $tweet->id_str,
          "omit_script" => "true"
        ]);
        $tweetList .= "<div class='tweetItem' id='".$tweet->id_str."'>".$tweetItem->html."</div>";
      } else {
        $message = "<p>Sorry, there don't seem to be any geocoded tweets with that user/search term.</p>";
        $tweetItem = $connection->get("statuses/oembed", [
          "id" => $tweet->id_str,
          "omit_script" => "true"
        ]);
        $tweetList .= "<div class='tweetItem' id='".$tweet->id_str."'>".$tweetItem->html."</div>";
      }
    } 
  } else {
      $errors = NULL;
      if (isset($tweets->error)) {
          $errors .= $tweets->error . PHP_EOL;
      }
      if (isset($tweets->errors)) {
          foreach ($tweets->errors as $error) {
              $errors .= $error->message . PHP_EOL;
          }
      }
      die($errors);
  }
  
 ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>My Twitter Map</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/css/bootstrap.min.css" integrity="sha384-/Y6pD6FV/Vv2HJnA6t+vslU6fwYXjCFtcEpHbNJ0lyAFsXTsjBbfaDjzALeQsN6M" crossorigin="anonymous">
    <script src='https://api.tiles.mapbox.com/mapbox-gl-js/v0.39.1/mapbox-gl.js'></script>
    <link href='https://api.tiles.mapbox.com/mapbox-gl-js/v0.39.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox.js/plugins/turf/v2.0.2/turf.min.js'></script>
    <script async="" src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
    <link rel="stylesheet" type="text/css" href="style.css">
  </head>
  <body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
      <a class="navbar-brand" href="#">Tweet Map</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <form class="form-inline my-2 my-lg-0 mr-2">
          <input class="form-control mr-sm-2" type="text" name="user" id="user" placeholder="Search For User" aria-label="Search">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
        <span class="mr-2 ml-2">OR</span>
        <form class="form-inline my-2 my-lg-0">
          <input class="form-control mr-sm-2" type="text" name="query" id="query" placeholder="#hashtag, @mention, text" aria-label="Search">
          <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
        </form>
      </div>
    </nav>

    <div class="container-fluid mr-0 ml-0" id="mapAndTweetList">

      <div class="row mr-0 ml-0">
        <div class="col col-xs-12 col-sm-12 col-md-8 col-lg-8" id="mapCol">
          <div id='map'></div>
        </div>
        <div class="col col-xs-12 col-sm-12 col-md-4 col-lg-4" id="tweetList">
          <?php echo $mesaage.$tweetList; ?>
        </div>
      </div>

    </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta/js/bootstrap.min.js" integrity="sha384-h0AbiXch4ZDo7tp9hKZ4TsHbi047NrKGLO3SEJAg45jXxnGIfYzk4Si90RDIqNm1" crossorigin="anonymous"></script>
    <script type="text/javascript">
      <?php if ($geojsonObjectComplete) {echo str_replace('},]}', '}]}', $geojsonObjectComplete);} ?>

      bounds = turf.extent(tweets_geojson);

      mapboxgl.accessToken = 'pk.eyJ1IjoiZGVlZ2dlIiwiYSI6ImNqM2Jmb29wYjAwN3kycXFrcW03YWlzdXAifQ.lRrvz11b3PTPopgJ888MMQ';

      var map = new mapboxgl.Map({
          container: 'map', // container id
          style: 'mapbox://styles/mapbox/dark-v9', //stylesheet location
          center: [22.812,38.985], // starting position
          zoom: 1.3 // starting zoom
      });

      //add controls
      map.addControl(new mapboxgl.NavigationControl());

      //add points
      map.on('load', function () {

          map.addSource("tweets", {
            "type": "geojson",
            "data": tweets_geojson,
            "cluster": true,
            "clusterMaxZoom": 18
          });

          map.addLayer({
              "id": "tweetClusters",
              "type": "circle",
              "source": "tweets",
              "paint": {
                "circle-color": {
                    "property": "point_count",
                    "type": "interval",
                    "stops": [
                        [0, "#51bbd6"],
                        [5, "#f1f075"],
                        [10, "#f28cb1"],
                    ]
                },
                "circle-radius": {
                    "property": "point_count",
                    "type": "interval",
                    "stops": [
                        [0, 20],
                        [5, 30],
                        [10, 40]
                    ]
                }
              }
          });

          map.addLayer({
              id: "tweetClusterCount",
              type: "symbol",
              source: "tweets",
              filter: ["has", "point_count"],
              layout: {
                  "text-field": "{point_count_abbreviated}",
                  "text-font": ["DIN Offc Pro Medium", "Arial Unicode MS Bold"],
                  "text-size": 12
              }
          });

          map.addLayer({
              id: "tweetUnclustered",
              type: "circle",
              source: "tweets",
              filter: ["!has", "point_count"],
              paint: {
                  "circle-color": "#11b4da",
                  "circle-radius": {
                    'base': 5,
                    'stops': [[5, 5], [22, 180]]
                  },
                  "circle-stroke-width": 1,
                  "circle-stroke-color": "#fff"
              }
          });

          map.on('click', 'tweetUnclustered', function (e) {
              idElement = $("#" + e.features[0].properties.id);
              console.log(idElement);
              $('#tweetList').animate({
                scrollTop:$(idElement).offset().top-56
              },'slow');
              e.preventDefault();

              new mapboxgl.Popup()
                  .setLngLat(e.features[0].geometry.coordinates)
                  .setHTML(e.features[0].properties.description)
                  .addTo(map);
          });

          // Change the cursor to a pointer when the mouse is over the places layer.
          map.on('mouseenter', 'tweetUnclustered', function () {
              map.getCanvas().style.cursor = 'pointer';
          });

          // Change it back to a pointer when it leaves.
          map.on('mouseleave', 'tweetUnclustered', function () {
              map.getCanvas().style.cursor = '';
          });
      });
      function fit() {
        map.fitBounds(bounds, {padding: 100});
        console.log(bounds);
      }
      fit();
    </script>
  </body>
</html>